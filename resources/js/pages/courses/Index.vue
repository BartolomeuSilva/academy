<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { GraduationCap } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import { formatTotalDuration } from '@/lib/format';
import { index, show } from '@/routes/courses';
import type { CatalogCourse } from '@/types';

defineProps<{
    courses: CatalogCourse[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Cursos', href: index() }],
    },
});

function progressLabel(course: CatalogCourse): string {
    if (course.completed_lessons_count === 0) {
        return 'Não iniciado';
    }

    if (course.completed_lessons_count === course.lessons_count) {
        return 'Concluído';
    }

    return `${Math.round((course.completed_lessons_count / course.lessons_count) * 100)}% concluído`;
}
</script>

<template>
    <Head title="Cursos" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Cursos"
            description="Todos os treinamentos disponíveis para você."
        />

        <div
            v-if="courses.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-10 text-center"
        >
            <GraduationCap class="size-10 text-muted-foreground" />
            <p class="font-medium">Nenhum curso disponível ainda</p>
            <p class="text-sm text-muted-foreground">
                Assim que um treinamento for publicado, ele aparecerá aqui.
            </p>
        </div>

        <ul v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li v-for="course in courses" :key="course.id">
                <Link
                    :href="show(course.slug)"
                    class="group flex h-full flex-col overflow-hidden rounded-xl border bg-card text-card-foreground transition-shadow hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    data-test="catalog-course"
                >
                    <div class="aspect-video bg-muted">
                        <img
                            v-if="course.cover_url"
                            :src="course.cover_url"
                            alt=""
                            class="size-full object-cover"
                        />
                        <div
                            v-else
                            class="flex size-full items-center justify-center"
                        >
                            <GraduationCap
                                class="size-10 text-muted-foreground"
                                aria-hidden="true"
                            />
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col gap-3 p-4">
                        <div class="flex-1 space-y-1">
                            <h3 class="font-semibold group-hover:underline">
                                {{ course.title }}
                            </h3>
                            <p
                                class="line-clamp-3 text-sm text-muted-foreground"
                            >
                                {{ course.summary }}
                            </p>
                        </div>

                        <p class="text-xs text-muted-foreground">
                            {{ course.lessons_count }}
                            {{ course.lessons_count === 1 ? 'aula' : 'aulas' }}
                            · {{ formatTotalDuration(course.duration_seconds) }}
                        </p>

                        <div class="space-y-1">
                            <ProgressBar
                                :completed="course.completed_lessons_count"
                                :total="course.lessons_count"
                                :label="`Progresso em ${course.title}`"
                            />
                            <p class="text-xs text-muted-foreground">
                                {{ progressLabel(course) }}
                            </p>
                        </div>
                    </div>
                </Link>
            </li>
        </ul>
    </div>
</template>
