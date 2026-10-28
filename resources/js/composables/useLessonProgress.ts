import { onBeforeUnmount, onMounted, ref } from 'vue';
import { putJson } from '@/lib/http';
import { update } from '@/routes/lessons/progress';

const SAVE_EVERY_SECONDS = 15;

type ProgressResponse = {
    last_position_seconds: number;
    completed: boolean;
};

/**
 * Save the playback position of a lesson every 15 s of playback, on pause and
 * when leaving the page, and toggle its completion manually.
 */
export function useLessonProgress(
    lessonId: number,
    initiallyCompleted: boolean,
) {
    const completed = ref(initiallyCompleted);
    const saving = ref(false);
    const error = ref<string | null>(null);

    let currentPosition: number | null = null;
    let savedPosition: number | null = null;
    let lastSaveAt = 0;

    async function send(
        body: { position_seconds?: number; completed?: boolean },
        keepalive = false,
    ): Promise<void> {
        try {
            const response = await putJson<ProgressResponse>(
                update.url(lessonId),
                body,
                { keepalive },
            );
            completed.value = response.completed;
            error.value = null;
        } catch {
            error.value = 'Não foi possível salvar seu progresso.';
        }
    }

    function savePosition(keepalive = false): void {
        if (currentPosition === null || currentPosition === savedPosition) {
            return;
        }

        savedPosition = currentPosition;
        lastSaveAt = Date.now();
        void send({ position_seconds: currentPosition }, keepalive);
    }

    function onTimeUpdate(seconds: number): void {
        currentPosition = Math.floor(seconds);

        if (Date.now() - lastSaveAt >= SAVE_EVERY_SECONDS * 1000) {
            savePosition();
        }
    }

    function onPause(seconds: number): void {
        currentPosition = Math.floor(seconds);
        savePosition();
    }

    async function toggleCompleted(): Promise<void> {
        saving.value = true;
        await send({ completed: !completed.value });
        saving.value = false;
    }

    const saveOnLeave = () => savePosition(true);

    onMounted(() => {
        lastSaveAt = Date.now();
        window.addEventListener('pagehide', saveOnLeave);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('pagehide', saveOnLeave);
        saveOnLeave();
    });

    return {
        completed,
        saving,
        error,
        onTimeUpdate,
        onPause,
        onEnded: () => savePosition(),
        toggleCompleted,
    };
}
