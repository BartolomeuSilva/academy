<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CirclePlay, GraduationCap } from '@lucide/vue';
import { computed } from 'vue';
import { show } from '@/routes/courses';
import type { CatalogCourse } from '@/types';

const props = defineProps<{
    course: CatalogCourse;
}>();

const percentage = computed(() =>
    props.course.lessons_count > 0
        ? Math.round(
              (props.course.completed_lessons_count /
                  props.course.lessons_count) *
                  100,
          )
        : 0,
);

const progressLabel = computed(() => {
    if (percentage.value === 100) {
        return 'Concluído';
    }

    return percentage.value === 0
        ? 'Em andamento'
        : `${percentage.value}% concluído`;
});
</script>

<template>
    <Link
        :href="show(course.slug)"
        class="group block space-y-2 rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:ring-offset-background focus-visible:outline-none"
        data-test="catalog-course"
    >
        <div
            class="relative aspect-[2/3] overflow-hidden rounded-xl bg-muted shadow-sm ring-1 ring-border transition-transform duration-300 group-hover:-translate-y-1 group-hover:shadow-lg"
        >
            <img
                v-if="course.cover_url"
                :src="course.cover_url"
                alt=""
                loading="lazy"
                class="size-full object-cover"
            />
            <div
                v-else
                class="flex size-full flex-col items-center justify-center gap-2 p-4 text-center"
            >
                <GraduationCap
                    class="size-10 text-muted-foreground"
                    aria-hidden="true"
                />
                <span class="text-sm font-medium text-muted-foreground">
                    {{ course.title }}
                </span>
            </div>
            <div
                class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
            >
                <CirclePlay
                    class="size-12 text-white drop-shadow"
                    aria-hidden="true"
                />
            </div>
            <div
                v-if="course.started"
                class="absolute inset-x-0 bottom-0 h-1.5 bg-black/40"
                aria-hidden="true"
            >
                <div
                    class="h-full bg-primary"
                    :style="{ width: `${Math.max(percentage, 3)}%` }"
                />
            </div>
        </div>
        <div class="space-y-0.5 px-0.5">
            <h3 class="line-clamp-1 text-sm font-semibold">
                {{ course.title }}
            </h3>
            <p class="line-clamp-2 text-xs text-muted-foreground">
                {{ course.summary }}
            </p>
            <p
                v-if="course.started"
                class="text-xs font-medium text-muted-foreground"
            >
                {{ progressLabel }}
            </p>
        </div>
    </Link>
</template>
