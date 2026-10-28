<script setup lang="ts">
import Hls from 'hls.js';
import { onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue';

const props = defineProps<{
    src: string;
    poster?: string | null;
    startAt?: number;
}>();

const emit = defineEmits<{
    timeupdate: [currentTime: number, duration: number];
    pause: [currentTime: number];
    ended: [];
}>();

const video = useTemplateRef('video');
const levels = ref<{ index: number; label: string }[]>([]);
const selectedLevel = ref(-1);
const failed = ref(false);
let hls: Hls | null = null;

function load(): void {
    const element = video.value;

    if (!element) {
        return;
    }

    hls?.destroy();
    hls = null;
    levels.value = [];
    failed.value = false;

    if (Hls.isSupported()) {
        hls = new Hls({ startPosition: props.startAt ?? -1 });
        hls.on(Hls.Events.MANIFEST_PARSED, (_, data) => {
            levels.value = data.levels
                .map((level, index) => ({ index, label: `${level.height}p` }))
                .reverse();
        });
        hls.on(Hls.Events.ERROR, (_, data) => {
            if (data.fatal) {
                failed.value = true;
            }
        });
        hls.loadSource(props.src);
        hls.attachMedia(element);
    } else if (element.canPlayType('application/vnd.apple.mpegurl')) {
        element.src = props.src;

        if (props.startAt) {
            element.addEventListener(
                'loadedmetadata',
                () => (element.currentTime = props.startAt ?? 0),
                { once: true },
            );
        }
    } else {
        failed.value = true;
    }
}

function changeLevel(event: Event): void {
    selectedLevel.value = Number((event.target as HTMLSelectElement).value);

    if (hls) {
        hls.currentLevel = selectedLevel.value;
    }
}

onMounted(load);
watch(() => props.src, load);
onBeforeUnmount(() => hls?.destroy());
</script>

<template>
    <div class="space-y-2">
        <div class="overflow-hidden rounded-xl bg-black">
            <video
                ref="video"
                class="aspect-video w-full"
                controls
                playsinline
                preload="metadata"
                :poster="poster ?? undefined"
                data-test="video-player"
                @timeupdate="
                    emit(
                        'timeupdate',
                        ($event.target as HTMLVideoElement).currentTime,
                        ($event.target as HTMLVideoElement).duration,
                    )
                "
                @pause="
                    emit(
                        'pause',
                        ($event.target as HTMLVideoElement).currentTime,
                    )
                "
                @ended="emit('ended')"
            />
        </div>

        <p v-if="failed" class="text-sm text-destructive">
            Não foi possível carregar o vídeo. Recarregue a página e tente
            novamente.
        </p>

        <div
            v-if="levels.length > 1"
            class="flex items-center justify-end gap-2 text-sm"
        >
            <label for="video-quality" class="text-muted-foreground">
                Qualidade
            </label>
            <select
                id="video-quality"
                :value="selectedLevel"
                class="h-8 rounded-md border border-input bg-transparent px-2 text-sm dark:bg-input/30"
                @change="changeLevel"
            >
                <option :value="-1">Automática</option>
                <option
                    v-for="level in levels"
                    :key="level.index"
                    :value="level.index"
                >
                    {{ level.label }}
                </option>
            </select>
        </div>
    </div>
</template>
