<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ImageUp, Trash2 } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    title: string;
    field: 'cover' | 'banner';
    imageUrl: string | null;
    uploadUrl: string;
    removeUrl: string;
    hint: string;
    previewClass: string;
    error?: string;
}>();

const fileInput = useTemplateRef('fileInput');
const uploading = ref(false);
const progress = ref<number | null>(null);
const uploadError = ref<string | undefined>();

function upload(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    router.post(
        props.uploadUrl,
        { [props.field]: file },
        {
            preserveScroll: true,
            onStart: () => {
                uploading.value = true;
                uploadError.value = undefined;
            },
            onProgress: (event) => (progress.value = event?.percentage ?? null),
            onError: (errors) => (uploadError.value = errors[props.field]),
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
    router.delete(props.removeUrl, { preserveScroll: true });
}
</script>

<template>
    <section
        class="space-y-3 rounded-xl border bg-card p-4 text-card-foreground"
    >
        <h2 class="font-medium">{{ title }}</h2>

        <div class="overflow-hidden rounded-lg bg-muted" :class="previewClass">
            <img
                v-if="imageUrl"
                :src="imageUrl"
                :alt="title"
                class="size-full object-cover"
            />
            <div
                v-else
                class="flex size-full items-center justify-center text-sm text-muted-foreground"
            >
                Sem imagem
            </div>
        </div>

        <p class="text-xs text-muted-foreground">{{ hint }}</p>

        <div
            v-if="progress !== null"
            class="h-1.5 overflow-hidden rounded bg-muted"
        >
            <div class="h-full bg-primary" :style="{ width: `${progress}%` }" />
        </div>
        <InputError :message="uploadError ?? error" />

        <div class="flex flex-wrap gap-2">
            <input
                ref="fileInput"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                :data-test="`${field}-input`"
                @change="upload"
            />
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="uploading"
                @click="fileInput?.click()"
            >
                <ImageUp /> {{ imageUrl ? 'Trocar' : 'Enviar' }}
                {{ title.toLowerCase() }}
            </Button>
            <Button
                v-if="imageUrl"
                type="button"
                variant="ghost"
                size="sm"
                :disabled="uploading"
                @click="remove"
            >
                <Trash2 /> Remover
            </Button>
        </div>

        <slot />
    </section>
</template>
