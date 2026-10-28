<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CirclePlay, Pencil } from '@lucide/vue';
import { computed } from 'vue';
import CourseOutline from '@/components/CourseOutline.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatTotalDuration } from '@/lib/format';
import { edit as editCourse } from '@/routes/admin/courses';
import { index, show } from '@/routes/courses';
import { show as showLesson } from '@/routes/lessons';
import type {
    CourseDetails,
    CourseProgress,
    OutlineModule,
    ResumeLesson,
} from '@/types';

const props = defineProps<{
    course: CourseDetails;
    modules: OutlineModule[];
    progress: CourseProgress;
    resume: ResumeLesson | null;
}>();

const isAdmin = computed(() => usePage().props.auth.user?.role === 'admin');

defineOptions({
    layout: (props: { course: CourseDetails }) => ({
        breadcrumbs: [
            { title: 'Cursos', href: index() },
            { title: props.course.title, href: show(props.course.slug) },
        ],
    }),
});

const resumeLabel = computed(() => {
    if (!props.resume?.started) {
        return 'Começar curso';
    }

    return props.progress.completed === props.progress.total
        ? 'Rever curso'
        : 'Continuar de onde parei';
});
</script>

<template>
    <Head :title="course.title" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <section
            class="grid gap-6 rounded-xl border bg-card p-4 text-card-foreground md:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] md:p-6"
        >
            <div class="aspect-video overflow-hidden rounded-lg bg-muted">
                <img
                    v-if="course.cover_url"
                    :src="course.cover_url"
                    alt=""
                    class="size-full object-cover"
                />
            </div>

            <div class="flex flex-col gap-4">
                <div class="space-y-1">
                    <StatusBadge
                        v-if="course.status !== 'published'"
                        :status="course.status"
                    />
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ course.title }}
                    </h1>
                    <p class="text-muted-foreground">{{ course.summary }}</p>
                </div>

                <p class="text-sm text-muted-foreground">
                    {{ progress.total }}
                    {{ progress.total === 1 ? 'aula' : 'aulas' }}
                    · {{ formatTotalDuration(progress.duration_seconds) }}
                </p>

                <div class="space-y-1">
                    <ProgressBar
                        :completed="progress.completed"
                        :total="progress.total"
                        label="Progresso no curso"
                    />
                    <p class="text-sm text-muted-foreground">
                        {{ progress.completed }} de {{ progress.total }}
                        {{
                            progress.total === 1
                                ? 'aula concluída'
                                : 'aulas concluídas'
                        }}
                    </p>
                </div>

                <div v-if="resume || isAdmin">
                    <div class="flex flex-wrap gap-2">
                        <Button v-if="resume" as-child>
                            <Link
                                :href="
                                    showLesson([
                                        course.slug,
                                        resume.lesson.slug,
                                    ])
                                "
                                data-test="resume-course-link"
                            >
                                <CirclePlay /> {{ resumeLabel }}
                            </Link>
                        </Button>
                        <Button v-if="isAdmin" variant="outline" as-child>
                            <Link
                                :href="editCourse(course.id)"
                                data-test="edit-course-link"
                            >
                                <Pencil /> Editar curso
                            </Link>
                        </Button>
                    </div>
                    <p
                        v-if="resume?.started"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        Próxima aula: {{ resume.lesson.title }}
                    </p>
                </div>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <section
                v-if="course.description_html"
                class="space-y-2 rounded-xl border bg-card p-4 text-card-foreground md:p-6"
            >
                <h2 class="font-semibold">Sobre o curso</h2>
                <!-- eslint-disable-next-line vue/no-v-html -- sanitized Markdown rendered by the server -->
                <div class="rich-text" v-html="course.description_html" />
            </section>

            <section
                class="space-y-3 rounded-xl border bg-card p-4 text-card-foreground md:p-6"
                :class="{ 'lg:col-span-2': !course.description_html }"
            >
                <h2 class="font-semibold">Conteúdo</h2>
                <CourseOutline
                    v-if="modules.length"
                    :course-slug="course.slug"
                    :modules="modules"
                />
                <p v-else class="text-sm text-muted-foreground">
                    Este curso ainda não tem aulas publicadas.
                </p>
            </section>
        </div>
    </div>
</template>
