<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CirclePlay, GraduationCap } from '@lucide/vue';
import { computed } from 'vue';
import CourseRow from '@/components/CourseRow.vue';
import { Button } from '@/components/ui/button';
import { formatTotalDuration } from '@/lib/format';
import { show } from '@/routes/courses';
import type { CatalogCourse, FeaturedCourse } from '@/types';

const UNCATEGORIZED = 'Outros cursos';

const props = defineProps<{
    featured: FeaturedCourse | null;
    courses: CatalogCourse[];
}>();

const page = usePage();
const firstName = computed(() => page.props.auth.user?.name.split(' ')[0]);

const spotlight = computed(() =>
    props.featured
        ? props.courses.find((course) => course.id === props.featured?.id)
        : undefined,
);

const inProgress = computed(() =>
    props.courses.filter(
        (course) =>
            course.started &&
            course.completed_lessons_count < course.lessons_count,
    ),
);

const rows = computed(() => {
    const hasCategories = props.courses.some((course) => course.category);
    const groups = new Map<string, CatalogCourse[]>();

    for (const course of props.courses) {
        const title = hasCategories
            ? (course.category ?? UNCATEGORIZED)
            : 'Todos os cursos';

        groups.set(title, [...(groups.get(title) ?? []), course]);
    }

    const uncategorized = groups.get(UNCATEGORIZED);
    groups.delete(UNCATEGORIZED);

    if (uncategorized) {
        groups.set(UNCATEGORIZED, uncategorized);
    }

    return [...groups].map(([title, courses]) => ({ title, courses }));
});
</script>

<template>
    <Head title="Cursos" />

    <div class="flex min-w-0 flex-1 flex-col gap-10 px-4 py-6">
        <section
            v-if="spotlight && featured"
            class="relative overflow-hidden rounded-2xl bg-neutral-900 text-white"
            aria-labelledby="spotlight-heading"
            data-test="featured-course"
        >
            <picture>
                <source
                    v-if="featured.banner_mobile_url"
                    media="(max-width: 639px)"
                    :srcset="featured.banner_mobile_url"
                />
                <img
                    :src="featured.banner_url"
                    alt=""
                    class="absolute inset-0 size-full object-cover"
                />
            </picture>
            <div
                class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-black/10 sm:bg-gradient-to-r sm:from-black/90 sm:via-black/60 sm:to-transparent"
            />
            <div
                class="relative flex min-h-[28rem] max-w-xl flex-col justify-end gap-4 p-6 sm:min-h-96 sm:justify-center sm:p-10"
            >
                <p
                    v-if="spotlight.category"
                    class="text-xs font-semibold tracking-widest text-white/80 uppercase"
                >
                    {{ spotlight.category }}
                </p>
                <h1
                    id="spotlight-heading"
                    class="text-3xl font-bold tracking-tight sm:text-5xl"
                >
                    {{ spotlight.title }}
                </h1>
                <p class="line-clamp-3 text-white/90">
                    {{ spotlight.summary }}
                </p>
                <p class="text-sm text-white/80">
                    {{ spotlight.lessons_count }}
                    {{ spotlight.lessons_count === 1 ? 'aula' : 'aulas' }}
                    · {{ formatTotalDuration(spotlight.duration_seconds) }}
                </p>
                <div>
                    <Button
                        size="lg"
                        class="bg-white text-neutral-900 hover:bg-white/90"
                        as-child
                    >
                        <Link
                            :href="show(spotlight.slug)"
                            data-test="featured-course-link"
                        >
                            <CirclePlay />
                            {{
                                spotlight.started
                                    ? 'Continuar curso'
                                    : 'Começar curso'
                            }}
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <header v-else class="space-y-1">
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
            <CourseRow
                v-if="inProgress.length"
                title="Continue assistindo"
                :courses="inProgress"
                data-test="continue-row"
            />
            <CourseRow
                v-for="row in rows"
                :key="row.title"
                :title="row.title"
                :courses="row.courses"
            />
        </template>
    </div>
</template>
