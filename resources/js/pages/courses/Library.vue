<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { GraduationCap } from '@lucide/vue';
import CourseGrid from '@/components/CourseGrid.vue';
import { library } from '@/routes/courses';
import type { CatalogCourse } from '@/types';

defineProps<{
    courses: CatalogCourse[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Meus cursos', href: library() }],
    },
});
</script>

<template>
    <Head title="Meus cursos" />

    <div class="flex min-w-0 flex-1 flex-col gap-6 p-4">
        <header
            class="rounded-xl border bg-card px-6 py-5 text-card-foreground"
        >
            <h1 class="text-3xl font-semibold tracking-tight">Meus cursos</h1>
        </header>

        <section
            aria-labelledby="library-heading"
            class="space-y-4 rounded-xl border bg-card p-4 text-card-foreground sm:p-6"
        >
            <h2 id="library-heading" class="text-xl font-semibold">
                Cursos e treinamentos
            </h2>

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
            <CourseGrid v-else :courses="courses" filterable />
        </section>
    </div>
</template>
