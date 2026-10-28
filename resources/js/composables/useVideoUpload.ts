import { computed, onBeforeUnmount, ref } from 'vue';
import { postJson } from '@/lib/http';
import { complete, parts } from '@/routes/admin/videos';
import { store } from '@/routes/admin/lessons/video';

type StartResponse = {
    video_id: number;
    part_size: number;
    part_count: number;
};
type UploadedPart = { part_number: number; etag: string };

const CONCURRENCY = 3;
const MAX_ATTEMPTS = 4;
const SIGN_BATCH = 20;

/**
 * Uploads a video straight to S3/MinIO in parts, with retries per part and
 * resuming from the parts already sent when the admin retries after an error.
 */
export function useVideoUpload(lessonId: number) {
    const file = ref<File | null>(null);
    const uploading = ref(false);
    const error = ref<string | null>(null);
    const uploadedBytes = ref(0);

    let session: StartResponse | null = null;
    let completed = new Map<number, UploadedPart>();
    let inFlight = new Map<number, XMLHttpRequest>();
    const partProgress = new Map<number, number>();
    let cancelled = false;

    const progress = computed(() =>
        file.value && file.value.size > 0
            ? Math.min(
                  100,
                  Math.round((uploadedBytes.value / file.value.size) * 100),
              )
            : 0,
    );

    const canResume = computed(
        () => !uploading.value && error.value !== null && session !== null,
    );

    function warnBeforeLeaving(event: BeforeUnloadEvent): void {
        event.preventDefault();
    }

    async function start(selected: File): Promise<boolean> {
        file.value = selected;
        session = null;
        completed = new Map();
        partProgress.clear();
        uploadedBytes.value = 0;

        return run();
    }

    async function resume(): Promise<boolean> {
        return run();
    }

    async function run(): Promise<boolean> {
        if (!file.value) {
            return false;
        }

        uploading.value = true;
        cancelled = false;
        error.value = null;
        window.addEventListener('beforeunload', warnBeforeLeaving);

        try {
            session ??= await postJson<StartResponse>(store.url(lessonId), {
                filename: file.value.name,
                size: file.value.size,
                content_type: file.value.type || null,
            });

            await uploadMissingParts(file.value, session);

            if (cancelled) {
                return false;
            }

            await postJson(complete.url(session.video_id), {
                parts: [...completed.values()],
            });

            session = null;

            return true;
        } catch (exception) {
            if (!cancelled) {
                error.value =
                    exception instanceof Error
                        ? exception.message
                        : 'O envio falhou. Tente novamente.';
            }

            return false;
        } finally {
            uploading.value = false;
            window.removeEventListener('beforeunload', warnBeforeLeaving);
        }
    }

    async function uploadMissingParts(
        source: File,
        upload: StartResponse,
    ): Promise<void> {
        const pending = Array.from(
            { length: upload.part_count },
            (_, index) => index + 1,
        ).filter((partNumber) => !completed.has(partNumber));

        for (
            let offset = 0;
            offset < pending.length && !cancelled;
            offset += SIGN_BATCH
        ) {
            const batch = pending.slice(offset, offset + SIGN_BATCH);
            const { urls } = await postJson<{ urls: Record<string, string> }>(
                parts.url(upload.video_id),
                { part_numbers: batch },
            );

            const queue = [...batch];
            const workers = Array.from({ length: CONCURRENCY }, async () => {
                while (queue.length > 0 && !cancelled) {
                    const partNumber = queue.shift()!;
                    const start = (partNumber - 1) * upload.part_size;
                    const blob = source.slice(
                        start,
                        Math.min(start + upload.part_size, source.size),
                    );
                    const etag = await putWithRetries(
                        urls[String(partNumber)],
                        blob,
                        partNumber,
                    );

                    completed.set(partNumber, {
                        part_number: partNumber,
                        etag,
                    });
                }
            });

            await Promise.all(workers);
        }
    }

    async function putWithRetries(
        url: string,
        blob: Blob,
        partNumber: number,
    ): Promise<string> {
        for (let attempt = 1; ; attempt++) {
            try {
                return await put(url, blob, partNumber);
            } catch (exception) {
                if (cancelled || attempt >= MAX_ATTEMPTS) {
                    throw exception;
                }

                await new Promise((resolve) =>
                    setTimeout(resolve, 1000 * 2 ** attempt),
                );
            }
        }
    }

    function put(url: string, blob: Blob, partNumber: number): Promise<string> {
        return new Promise((resolve, reject) => {
            const request = new XMLHttpRequest();
            inFlight.set(partNumber, request);

            request.upload.onprogress = (event) =>
                setPartProgress(partNumber, event.loaded);
            request.onload = () => {
                inFlight.delete(partNumber);
                const etag = request.getResponseHeader('ETag');

                if (request.status >= 200 && request.status < 300 && etag) {
                    setPartProgress(partNumber, blob.size);
                    resolve(etag);
                } else {
                    setPartProgress(partNumber, 0);
                    reject(
                        new Error(
                            'O armazenamento recusou uma parte do vídeo. Tente novamente.',
                        ),
                    );
                }
            };
            request.onerror = () => {
                inFlight.delete(partNumber);
                setPartProgress(partNumber, 0);
                reject(
                    new Error(
                        'A conexão caiu durante o envio. Verifique a internet e tente novamente.',
                    ),
                );
            };
            request.onabort = () => {
                inFlight.delete(partNumber);
                reject(new Error('Envio cancelado.'));
            };

            request.open('PUT', url);
            request.send(blob);
        });
    }

    function setPartProgress(partNumber: number, bytes: number): void {
        partProgress.set(partNumber, bytes);
        uploadedBytes.value = [...partProgress.values()].reduce(
            (total, value) => total + value,
            0,
        );
    }

    function cancel(): number | null {
        cancelled = true;
        inFlight.forEach((request) => request.abort());
        inFlight = new Map();

        const videoId = session?.video_id ?? null;
        session = null;
        error.value = null;
        file.value = null;

        return videoId;
    }

    onBeforeUnmount(() => {
        if (uploading.value) {
            cancel();
        }
    });

    return {
        file,
        uploading,
        error,
        progress,
        canResume,
        start,
        resume,
        cancel,
    };
}
