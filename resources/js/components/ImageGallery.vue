<script setup lang="ts">
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';

defineProps<{
    images: { id: number; url: string }[];
}>();

const viewed = ref<string | null>(null);
</script>

<template>
    <ul
        v-if="images.length"
        class="flex flex-wrap gap-2"
        data-test="post-images"
    >
        <li v-for="(image, index) in images" :key="image.id">
            <button
                type="button"
                class="block size-28 overflow-hidden rounded-lg ring-1 ring-border focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:size-36"
                :aria-label="`Ampliar imagem ${index + 1}`"
                @click="viewed = image.url"
            >
                <img
                    :src="image.url"
                    alt=""
                    loading="lazy"
                    class="size-full object-cover transition-transform hover:scale-105"
                />
            </button>
        </li>
    </ul>
    <Dialog
        :open="viewed !== null"
        @update:open="(open) => !open && (viewed = null)"
    >
        <DialogContent class="sm:max-w-4xl">
            <DialogTitle class="sr-only">Imagem</DialogTitle>
            <DialogDescription class="sr-only"
                >Imagem ampliada</DialogDescription
            >
            <img
                v-if="viewed"
                :src="viewed"
                alt="Imagem ampliada"
                class="max-h-[80vh] w-full rounded-md object-contain"
            />
        </DialogContent>
    </Dialog>
</template>
