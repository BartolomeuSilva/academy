<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import CoursePoster from '@/components/CoursePoster.vue';
import { Button } from '@/components/ui/button';
import type { CatalogCourse } from '@/types';

const props = defineProps<{
    title: string;
    courses: CatalogCourse[];
}>();

const headingId = `row-${props.title.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;
const scroller = useTemplateRef('scroller');

function scroll(direction: 1 | -1): void {
    const element = scroller.value;

    element?.scrollBy({
        left: direction * element.clientWidth * 0.8,
        behavior: 'smooth',
    });
}
</script>

<template>
    <section :aria-labelledby="headingId" class="space-y-3">
        <div class="flex items-center justify-between gap-2">
            <h2 :id="headingId" class="text-lg font-semibold">{{ title }}</h2>
            <div class="hidden gap-1 md:flex">
                <Button
                    variant="ghost"
                    size="icon"
                    :aria-label="`Rolar ${title} para a esquerda`"
                    @click="scroll(-1)"
                >
                    <ChevronLeft />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    :aria-label="`Rolar ${title} para a direita`"
                    @click="scroll(1)"
                >
                    <ChevronRight />
                </Button>
            </div>
        </div>
        <ul
            ref="scroller"
            class="-mx-4 flex snap-x snap-mandatory scroll-px-4 [scrollbar-width:none] gap-4 overflow-x-auto px-4 pt-1 pb-3 [&::-webkit-scrollbar]:hidden"
        >
            <li
                v-for="course in courses"
                :key="course.id"
                class="w-36 shrink-0 snap-start sm:w-44 lg:w-48"
            >
                <CoursePoster :course="course" />
            </li>
        </ul>
    </section>
</template>
