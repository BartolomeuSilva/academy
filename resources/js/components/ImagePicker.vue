<script setup lang="ts">
import { ImagePlus, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, useTemplateRef } from 'vue';
import { Button } from '@/components/ui/button';

const MAX_IMAGES = 4;

const props = defineProps<{
    /** The form's validation errors; the first one about the images is shown. */
    errors: Record<string, string>;
    /** Icon-only button, e.g. inside a chat composer. */
    compact?: boolean;
}>();

const error = computed(
    () =>
        props.errors.images ??
        Object.entries(props.errors).find(([key]) =>
            key.startsWith('images.'),
        )?.[1],
);

const input = useTemplateRef('input');
const previews = ref<{ name: string; url: string }[]>([]);
const tooMany = ref(false);

function revoke(): void {
    previews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
}

function onChange(): void {
    const files = Array.from(input.value?.files ?? []);
    revoke();
    tooMany.value = files.length > MAX_IMAGES;
    previews.value = files.map((file) => ({
        name: file.name,
        url: URL.createObjectURL(file),
    }));
}

function clear(): void {
    if (input.value) {
        input.value.value = '';
    }

    onChange();
}

/** Empty the picker after the post is sent. */
defineExpose({ clear });

onBeforeUnmount(revoke);
</script>

<template>
    <div class="space-y-2">
        <div class="flex flex-wrap items-center gap-2">
            <Button
                type="button"
                :variant="compact ? 'ghost' : 'outline'"
                :size="compact ? 'icon' : 'sm'"
                :aria-label="compact ? 'Adicionar imagens' : undefined"
                data-test="add-images-button"
                @click="input?.click()"
            >
                <ImagePlus />
                <template v-if="!compact">Adicionar imagens</template>
            </Button>
            <span v-if="!compact" class="text-xs text-muted-foreground"
                >Até {{ MAX_IMAGES }} imagens de até 5 MB.</span
            >
            <Button
                v-if="previews.length"
                type="button"
                variant="ghost"
                size="sm"
                class="text-muted-foreground"
                @click="clear"
            >
                <X /> Remover imagens
            </Button>
        </div>
        <input
            ref="input"
            type="file"
            name="images[]"
            accept="image/png,image/jpeg,image/webp,image/gif"
            multiple
            class="sr-only"
            aria-label="Imagens"
            tabindex="-1"
            data-test="images-input"
            @change="onChange"
        />
        <ul v-if="previews.length" class="flex flex-wrap gap-2">
            <li
                v-for="preview in previews"
                :key="preview.url"
                class="size-20 overflow-hidden rounded-md ring-1 ring-border"
                data-test="image-preview"
            >
                <img
                    :src="preview.url"
                    :alt="preview.name"
                    class="size-full object-cover"
                />
            </li>
        </ul>
        <p v-if="tooMany" class="text-sm text-destructive">
            Escolha no máximo {{ MAX_IMAGES }} imagens.
        </p>
        <p v-else-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
</template>
