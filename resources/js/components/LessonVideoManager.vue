<script setup lang="ts">
import type { ReloadOptions } from '@inertiajs/core';
import { router, usePoll } from '@inertiajs/vue3';
import { AlertCircle, LoaderCircle, Upload, Video, X } from '@lucide/vue';
import { computed, useTemplateRef, watch } from 'vue';
import { Button } from '@/components/ui/button';
import VideoPlayer from '@/components/VideoPlayer.vue';
import { useVideoUpload } from '@/composables/useVideoUpload';
import { formatBytes, formatDuration } from '@/lib/format';
import { destroy } from '@/routes/admin/videos';
import type { AdminLessonVideo, AdminPendingVideo } from '@/types';

const props = defineProps<{
    lessonId: number;
    video: AdminLessonVideo | null;
    pendingVideo: AdminPendingVideo | null;
}>();

const fileInput = useTemplateRef('fileInput');
const upload = useVideoUpload(props.lessonId);

const reloadOptions: ReloadOptions = {
    only: ['lesson', 'video', 'pending_video'],
};

const encoding = computed(
    () =>
        props.pendingVideo?.status === 'queued' ||
        props.pendingVideo?.status === 'processing',
);
const abandonedUpload = computed(
    () =>
        props.pendingVideo?.status === 'uploading' &&
        !upload.uploading.value &&
        !upload.canResume.value,
);

const { start: startPolling, stop: stopPolling } = usePoll(
    5000,
    reloadOptions,
    { autoStart: false },
);

watch(encoding, (isEncoding) => (isEncoding ? startPolling() : stopPolling()), {
    immediate: true,
});

async function choose(event: Event): Promise<void> {
    const selected = (event.target as HTMLInputElement).files?.[0];

    if (fileInput.value) {
        fileInput.value.value = '';
    }

    if (selected && (await upload.start(selected))) {
        router.reload(reloadOptions);
    }
}

async function resume(): Promise<void> {
    if (await upload.resume()) {
        router.reload(reloadOptions);
    }
}

function cancel(): void {
    const videoId = upload.cancel() ?? props.pendingVideo?.id;

    if (videoId) {
        router.delete(destroy.url(videoId), { preserveScroll: true });
    }
}

function dismiss(): void {
    if (props.pendingVideo) {
        router.delete(destroy.url(props.pendingVideo.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <section
        class="space-y-4 rounded-xl border bg-card p-4 text-card-foreground"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-medium">Vídeo da aula</h2>
                <p class="text-sm text-muted-foreground">
                    MP4, MOV, MKV ou WEBM de até 2 GB e 1 hora. O vídeo é
                    convertido para 480p, 720p e 1080p (sem aumentar a resolução
                    original).
                </p>
            </div>

            <input
                ref="fileInput"
                type="file"
                accept="video/mp4,video/quicktime,video/x-matroska,video/webm,.mp4,.m4v,.mov,.mkv,.webm"
                class="hidden"
                data-test="video-input"
                @change="choose"
            />
            <Button
                v-if="!upload.uploading.value && !encoding"
                type="button"
                variant="outline"
                size="sm"
                @click="fileInput?.click()"
            >
                <Upload /> {{ video ? 'Substituir vídeo' : 'Enviar vídeo' }}
            </Button>
        </div>

        <div
            v-if="upload.uploading.value || upload.canResume.value"
            class="space-y-2 rounded-lg border p-3"
            data-test="video-upload-progress"
        >
            <div class="flex items-center justify-between gap-2 text-sm">
                <span class="truncate">
                    {{ upload.file.value?.name }}
                    <span class="text-muted-foreground">
                        ({{ formatBytes(upload.file.value?.size ?? 0) }})
                    </span>
                </span>
                <span class="tabular-nums">{{ upload.progress.value }}%</span>
            </div>
            <div class="h-2 overflow-hidden rounded bg-muted">
                <div
                    class="h-full bg-primary transition-[width]"
                    :style="{ width: `${upload.progress.value}%` }"
                />
            </div>
            <p v-if="upload.error.value" class="text-sm text-destructive">
                {{ upload.error.value }}
            </p>
            <p v-else class="text-xs text-muted-foreground">
                Não feche esta página até o envio terminar.
            </p>
            <div class="flex gap-2">
                <Button
                    v-if="upload.canResume.value"
                    type="button"
                    size="sm"
                    @click="resume"
                >
                    Tentar novamente
                </Button>
                <Button type="button" size="sm" variant="ghost" @click="cancel">
                    <X /> Cancelar envio
                </Button>
            </div>
        </div>

        <div
            v-else-if="encoding"
            class="flex items-center gap-3 rounded-lg border p-3 text-sm"
            data-test="video-encoding"
        >
            <LoaderCircle class="size-5 shrink-0 animate-spin" />
            <div>
                <p class="font-medium">
                    {{
                        pendingVideo?.status === 'queued'
                            ? 'Na fila para conversão'
                            : 'Convertendo o vídeo'
                    }}
                </p>
                <p class="text-muted-foreground">
                    {{ pendingVideo?.original_name }} — isso pode levar alguns
                    minutos. Você pode sair desta página.
                </p>
            </div>
        </div>

        <div
            v-else-if="pendingVideo?.status === 'failed'"
            class="flex items-start gap-3 rounded-lg border border-destructive/40 p-3 text-sm"
            role="alert"
        >
            <AlertCircle class="size-5 shrink-0 text-destructive" />
            <div class="flex-1">
                <p class="font-medium">
                    A conversão de {{ pendingVideo.original_name }} falhou
                </p>
                <p class="text-muted-foreground">
                    {{ pendingVideo.error_message }}
                </p>
            </div>
            <Button type="button" size="sm" variant="ghost" @click="dismiss">
                Dispensar
            </Button>
        </div>

        <div
            v-else-if="abandonedUpload"
            class="flex items-center justify-between gap-3 rounded-lg border p-3 text-sm"
        >
            <span>
                O envio de {{ pendingVideo?.original_name }} foi interrompido.
                Envie o arquivo novamente.
            </span>
            <Button type="button" size="sm" variant="ghost" @click="dismiss">
                Descartar
            </Button>
        </div>

        <div v-if="video" class="space-y-2">
            <VideoPlayer :src="video.playlist_url" :poster="video.poster_url" />
            <p class="text-xs text-muted-foreground">
                {{ video.original_name }}
                <template v-if="video.duration_seconds">
                    · {{ formatDuration(video.duration_seconds) }}
                </template>
                · {{ video.renditions.join(', ') }}
            </p>
        </div>

        <div
            v-else-if="!upload.uploading.value && !encoding"
            class="flex flex-col items-center gap-2 rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            <Video class="size-8" />
            Nenhum vídeo enviado. A aula só pode ser publicada depois que o
            vídeo estiver pronto.
        </div>
    </section>
</template>
