<script setup lang="ts">
import { PictureInPicture2 } from '@lucide/vue';
import Hls from 'hls.js';
import { onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    src: string;
    poster?: string | null;
    startAt?: number;
}>();

const emit = defineEmits<{
    timeupdate: [currentTime: number, duration: number];
    pause: [currentTime: number];
    play: [];
    ended: [];
}>();

const video = useTemplateRef('video');
const levels = ref<{ index: number; label: string }[]>([]);
const selectedLevel = ref(-1);
const failed = ref(false);
const canFloat =
    typeof document !== 'undefined' && document.pictureInPictureEnabled;

function toggleFloating(): void {
    if (document.pictureInPictureElement) {
        void document.exitPictureInPicture();
    } else {
        void video.value?.requestPictureInPicture();
    }
}
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
        <div class="relative overflow-hidden rounded-xl bg-black">
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
                @play="emit('play')"
                @ended="emit('ended')"
            />
            <slot name="overlay" />
        </div>

        <p v-if="failed" class="text-sm text-destructive">
            Não foi possível carregar o vídeo. Recarregue a página e tente
            novamente.
        </p>

        <div
            v-if="canFloat || levels.length > 1"
            class="flex flex-wrap items-center gap-2 text-sm"
        >
            <Button
                v-if="canFloat"
                type="button"
                variant="ghost"
                size="sm"
                class="text-muted-foreground"
                data-test="floating-video-button"
                @click="toggleFloating"
            >
                <PictureInPicture2 /> Assistir em janela flutuante
            </Button>
            <template v-if="levels.length > 1">
                <label
                    for="video-quality"
                    class="ml-auto text-muted-foreground"
                >
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
            </template>
        </div>
    </div>
</template>
