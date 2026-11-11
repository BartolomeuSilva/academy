<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Check, GraduationCap, Play } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { formatTotalDuration } from '@/lib/format';
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

const completed = computed(
    () =>
        props.course.lessons_count > 0 &&
        props.course.completed_lessons_count >= props.course.lessons_count,
);

const action = computed(() => {
    if (completed.value) {
        return 'Rever curso';
    }

    return props.course.started ? 'Continuar curso' : 'Começar curso';
});
</script>

<template>
    <article
        class="group flex flex-col overflow-hidden rounded-xl border bg-card text-card-foreground transition-shadow hover:shadow-md"
        data-test="catalog-course"
    >
        <Link
            :href="show(course.slug)"
            class="relative block aspect-[2/3] overflow-hidden bg-muted"
            tabindex="-1"
            aria-hidden="true"
        >
            <img
                v-if="course.cover_url"
                :src="course.cover_url"
                alt=""
                loading="lazy"
                class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <span
                v-else
                class="flex size-full flex-col items-center justify-center gap-2 p-4 text-center"
            >
                <GraduationCap class="size-10 text-muted-foreground" />
                <span class="text-sm font-medium text-muted-foreground">{{
                    course.title
                }}</span>
            </span>
            <span
                v-if="completed"
                class="absolute top-3 left-3 flex items-center gap-1 rounded-md bg-sidebar-accent px-2 py-1 text-xs font-semibold text-sidebar-accent-foreground shadow-sm"
            >
                Concluído <Check class="size-3.5" />
            </span>
            <span
                v-else-if="course.started"
                class="absolute top-3 left-3 rounded-md bg-sidebar-accent px-2 py-1 text-xs font-semibold text-sidebar-accent-foreground shadow-sm"
            >
                Em andamento
            </span>
            <span
                class="absolute right-3 bottom-3 flex size-10 items-center justify-center rounded-full bg-sidebar-accent text-sidebar-accent-foreground shadow-md transition-transform group-hover:scale-110"
            >
                <Play class="size-4 fill-current" />
            </span>
        </Link>

        <div class="flex flex-1 flex-col gap-2 p-4">
            <p
                class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
            >
                {{ course.category ?? 'Treinamento' }} ·
                {{ course.lessons_count }}
                {{ course.lessons_count === 1 ? 'aula' : 'aulas' }}
            </p>
            <h3 class="leading-snug font-semibold">
                <Link :href="show(course.slug)" class="hover:underline">{{
                    course.title
                }}</Link>
            </h3>
            <p class="line-clamp-3 text-sm text-muted-foreground">
                {{ course.summary }}
            </p>

            <div class="mt-auto space-y-3 pt-2">
                <div v-if="course.started" class="space-y-1">
                    <p
                        class="flex justify-between text-xs text-muted-foreground"
                    >
                        <span
                            >{{ course.completed_lessons_count }} de
                            {{ course.lessons_count }} aulas</span
                        >
                        <span class="font-semibold text-foreground"
                            >{{ percentage }}%</span
                        >
                    </p>
                    <div
                        class="h-1.5 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-label="`Progresso em ${course.title}`"
                        :aria-valuenow="percentage"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{ width: `${percentage}%` }"
                        />
                    </div>
                </div>
                <p v-else class="text-xs text-muted-foreground">
                    {{ formatTotalDuration(course.duration_seconds) }} de vídeo
                </p>
                <Button
                    class="w-full bg-brand text-brand-foreground hover:bg-brand/90"
                    as-child
                >
                    <Link
                        :href="show(course.slug)"
                        data-test="course-card-action"
                    >
                        {{ action }} <ArrowRight />
                    </Link>
                </Button>
            </div>
        </div>
    </article>
</template>
