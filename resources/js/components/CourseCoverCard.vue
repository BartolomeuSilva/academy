<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ImageUp, Trash2 } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { destroy, store } from '@/routes/admin/courses/cover';

const props = defineProps<{
    courseId: number;
    coverUrl: string | null;
}>();

const fileInput = useTemplateRef('fileInput');
const uploading = ref(false);
const progress = ref<number | null>(null);
const error = ref<string | undefined>();

function upload(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    router.post(
        store.url(props.courseId),
        { cover: file },
        {
            preserveScroll: true,
            onStart: () => {
                uploading.value = true;
                error.value = undefined;
            },
            onProgress: (event) => (progress.value = event?.percentage ?? null),
            onError: (errors) => (error.value = errors.cover),
            onFinish: () => {
                uploading.value = false;
                progress.value = null;

                if (fileInput.value) {
                    fileInput.value.value = '';
                }
            },
        },
    );
}

function remove(): void {
    router.delete(destroy.url(props.courseId), { preserveScroll: true });
}
</script>

<template>
    <section
        class="space-y-3 rounded-xl border bg-card p-4 text-card-foreground"
    >
        <h2 class="font-medium">Capa</h2>

        <div class="aspect-video overflow-hidden rounded-lg bg-muted">
            <img
                v-if="coverUrl"
                :src="coverUrl"
                alt="Capa do curso"
                class="size-full object-cover"
            />
            <div
                v-else
                class="flex size-full items-center justify-center text-sm text-muted-foreground"
            >
                Sem capa
            </div>
        </div>

        <p class="text-xs text-muted-foreground">
            JPG, PNG ou WEBP até 5 MB. Proporção recomendada 16:9.
        </p>

        <div
            v-if="progress !== null"
            class="h-1.5 overflow-hidden rounded bg-muted"
        >
            <div class="h-full bg-primary" :style="{ width: `${progress}%` }" />
        </div>
        <InputError :message="error" />

        <div class="flex gap-2">
            <input
                ref="fileInput"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                data-test="cover-input"
                @change="upload"
            />
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="uploading"
                @click="fileInput?.click()"
            >
                <ImageUp /> {{ coverUrl ? 'Trocar capa' : 'Enviar capa' }}
            </Button>
            <Button
                v-if="coverUrl"
                type="button"
                variant="ghost"
                size="sm"
                :disabled="uploading"
                @click="remove"
            >
                <Trash2 /> Remover
            </Button>
        </div>
    </section>
</template>
