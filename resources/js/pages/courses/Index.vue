<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CirclePlay, GraduationCap } from '@lucide/vue';
import { computed } from 'vue';
import { show } from '@/routes/courses';
import type { CatalogCourse } from '@/types';

const props = defineProps<{
    courses: CatalogCourse[];
}>();

const page = usePage();
const firstName = computed(() => page.props.auth.user?.name.split(' ')[0]);

const inProgress = computed(() =>
    props.courses.filter(
        (course) =>
            course.started &&
            course.completed_lessons_count < course.lessons_count,
    ),
);

function percentage(course: CatalogCourse): number {
    return course.lessons_count > 0
        ? Math.round(
              (course.completed_lessons_count / course.lessons_count) * 100,
          )
        : 0;
}

function progressLabel(course: CatalogCourse): string {
    if (course.completed_lessons_count === course.lessons_count) {
        return 'Concluído';
    }

    return `${percentage(course)}% concluído`;
}
</script>

<template>
    <Head title="Cursos" />

    <div class="flex flex-1 flex-col gap-10 px-4 py-8">
        <header class="space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight">
                Olá, {{ firstName }}!
            </h1>
            <p class="text-muted-foreground">
                Escolha um treinamento para começar ou continuar.
            </p>
        </header>

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

        <template v-else>
            <section
                v-if="inProgress.length"
                aria-labelledby="continue-heading"
                class="space-y-4"
            >
                <h2 id="continue-heading" class="text-lg font-semibold">
                    Continue assistindo
                </h2>
                <ul
                    class="-mx-4 flex snap-x gap-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 lg:grid-cols-3"
                >
                    <li
                        v-for="course in inProgress"
                        :key="course.id"
                        class="w-72 shrink-0 snap-start sm:w-auto"
                    >
                        <Link
                            :href="show(course.slug)"
                            class="group flex items-center gap-3 rounded-xl border bg-card p-2 text-card-foreground transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            data-test="continue-course"
                        >
                            <div
                                class="relative aspect-video w-28 shrink-0 overflow-hidden rounded-lg bg-muted"
                            >
                                <img
                                    v-if="course.cover_url"
                                    :src="course.cover_url"
                                    alt=""
                                    class="size-full object-cover"
                                />
                                <div
                                    class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <CirclePlay
                                        class="size-8 text-white"
                                        aria-hidden="true"
                                    />
                                </div>
                            </div>
                            <div class="min-w-0 flex-1 space-y-2 pr-2">
                                <p class="truncate text-sm font-medium">
                                    {{ course.title }}
                                </p>
                                <div
                                    role="progressbar"
                                    :aria-label="`Progresso em ${course.title}`"
                                    :aria-valuenow="percentage(course)"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    class="h-1.5 overflow-hidden rounded-full bg-primary/15"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary"
                                        :style="{
                                            width: `${percentage(course)}%`,
                                        }"
                                    />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ progressLabel(course) }}
                                </p>
                            </div>
                        </Link>
                    </li>
                </ul>
            </section>

            <section aria-labelledby="all-heading" class="space-y-4">
                <h2 id="all-heading" class="text-lg font-semibold">
                    Todos os cursos
                </h2>
                <ul class="grid gap-x-5 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="course in courses" :key="course.id">
                        <Link
                            :href="show(course.slug)"
                            class="group block space-y-3 rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:ring-offset-background focus-visible:outline-none"
                            data-test="catalog-course"
                        >
                            <div
                                class="relative aspect-video overflow-hidden rounded-xl bg-muted shadow-sm ring-1 ring-border"
                            >
                                <img
                                    v-if="course.cover_url"
                                    :src="course.cover_url"
                                    alt=""
                                    class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
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
                                <div
                                    class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <CirclePlay
                                        class="size-12 text-white drop-shadow"
                                        aria-hidden="true"
                                    />
                                </div>
                                <div
                                    v-if="course.started"
                                    class="absolute inset-x-0 bottom-0 h-1 bg-black/30"
                                    aria-hidden="true"
                                >
                                    <div
                                        class="h-full bg-primary"
                                        :style="{
                                            width: `${Math.max(percentage(course), 3)}%`,
                                        }"
                                    />
                                </div>
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-semibold group-hover:underline">
                                    {{ course.title }}
                                </h3>
                                <p
                                    class="line-clamp-2 text-sm text-muted-foreground"
                                >
                                    {{ course.summary }}
                                </p>
                                <p
                                    v-if="course.started"
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    {{ progressLabel(course) }}
                                </p>
                            </div>
                        </Link>
                    </li>
                </ul>
            </section>
        </template>
    </div>
</template>
