<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProgressRing from '@/components/ProgressRing.vue';
import { show } from '@/routes/courses';
import type { CatalogCourse } from '@/types';

const props = defineProps<{
    courses: CatalogCourse[];
}>();

function percentage(completed: number, total: number): number {
    return total > 0 ? Math.round((completed / total) * 100) : 0;
}

const totals = computed(() =>
    props.courses.reduce(
        (sum, course) => ({
            completed: sum.completed + course.completed_lessons_count,
            lessons: sum.lessons + course.lessons_count,
        }),
        { completed: 0, lessons: 0 },
    ),
);
</script>

<template>
    <section
        aria-labelledby="progress-heading"
        class="space-y-3"
        data-test="progress-overview"
    >
        <h2 id="progress-heading" class="text-lg font-semibold">
            Seu progresso
        </h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div
                class="flex items-center gap-4 rounded-xl border bg-accent/50 p-4"
            >
                <ProgressRing
                    size="lg"
                    :percentage="percentage(totals.completed, totals.lessons)"
                />
                <div class="min-w-0">
                    <p
                        class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Geral
                    </p>
                    <p class="font-semibold">
                        {{ totals.completed }} de {{ totals.lessons }} aulas
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ courses.length }}
                        {{
                            courses.length === 1
                                ? 'curso iniciado'
                                : 'cursos iniciados'
                        }}
                    </p>
                </div>
            </div>

            <Link
                v-for="course in courses"
                :key="course.id"
                :href="show(course.slug)"
                class="group flex items-center gap-4 rounded-xl border bg-card p-4 text-card-foreground hover:bg-accent/60 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                data-test="progress-course"
            >
                <ProgressRing
                    size="lg"
                    :percentage="
                        percentage(
                            course.completed_lessons_count,
                            course.lessons_count,
                        )
                    "
                />
                <div class="min-w-0">
                    <p
                        v-if="course.category"
                        class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        {{ course.category }}
                    </p>
                    <p class="line-clamp-2 font-semibold">{{ course.title }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ course.completed_lessons_count }} de
                        {{ course.lessons_count }} aulas ·
                        <span
                            class="font-medium text-foreground group-hover:underline"
                        >
                            {{
                                course.completed_lessons_count >=
                                course.lessons_count
                                    ? 'Rever'
                                    : 'Continuar'
                            }}
                            →
                        </span>
                    </p>
                </div>
            </Link>
        </div>
    </section>
</template>
