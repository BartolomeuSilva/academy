<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    CircleCheck,
    Circle,
    Download,
    FileText,
    VideoOff,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import CourseOutline from '@/components/CourseOutline.vue';
import LessonComments from '@/components/LessonComments.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import VideoPlayer from '@/components/VideoPlayer.vue';
import { useLessonProgress } from '@/composables/useLessonProgress';
import { formatBytes } from '@/lib/format';
import { index, show as showCourse } from '@/routes/courses';
import { show } from '@/routes/lessons';
import type {
    LessonComments as LessonCommentsData,
    LessonDetails,
    LessonMaterial,
    LessonProgressState,
    LessonVideo,
    OutlineLesson,
    OutlineModule,
} from '@/types';

type CourseRef = { slug: string; title: string };

const props = defineProps<{
    course: CourseRef;
    lesson: LessonDetails;
    video: LessonVideo | null;
    progress: LessonProgressState;
    attachments: LessonMaterial[];
    modules: OutlineModule[];
    previous_lesson: OutlineLesson | null;
    next_lesson: OutlineLesson | null;
    comments: LessonCommentsData;
}>();

defineOptions({
    layout: (props: { course: CourseRef; lesson: LessonDetails }) => ({
        breadcrumbs: [
            { title: 'Cursos', href: index() },
            { title: props.course.title, href: showCourse(props.course.slug) },
            {
                title: props.lesson.title,
                href: show([props.course.slug, props.lesson.slug]),
            },
        ],
    }),
});

const lessonProgress = useLessonProgress(
    props.lesson.id,
    props.progress.completed,
);
const { completed, saving, error } = lessonProgress;

watch(completed, () => router.reload({ only: ['modules'] }));

const viewedImage = ref<LessonMaterial | null>(null);
</script>

<template>
    <Head :title="lesson.title" />

    <div class="grid flex-1 gap-6 p-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="flex min-w-0 flex-col gap-6">
            <VideoPlayer
                v-if="video"
                :key="lesson.id"
                :src="video.playlist_url"
                :poster="video.poster_url"
                :start-at="progress.start_at_seconds"
                @timeupdate="lessonProgress.onTimeUpdate"
                @pause="lessonProgress.onPause"
                @ended="lessonProgress.onEnded"
            />
            <div
                v-else
                class="flex aspect-video flex-col items-center justify-center gap-2 rounded-xl border border-dashed text-muted-foreground"
            >
                <VideoOff class="size-8" aria-hidden="true" />
                <p class="text-sm">Esta aula ainda não tem vídeo.</p>
            </div>

            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-1">
                    <StatusBadge
                        v-if="lesson.status !== 'published'"
                        :status="lesson.status"
                    />
                    <h1 class="text-xl font-semibold tracking-tight">
                        {{ lesson.title }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ course.title }}
                    </p>
                </div>

                <div class="flex flex-col items-end gap-1">
                    <Button
                        :variant="completed ? 'secondary' : 'outline'"
                        :disabled="saving"
                        :aria-pressed="completed"
                        data-test="toggle-completed-button"
                        @click="lessonProgress.toggleCompleted"
                    >
                        <CircleCheck v-if="completed" class="text-primary" />
                        <Circle v-else />
                        {{
                            completed
                                ? 'Aula concluída'
                                : 'Marcar como concluída'
                        }}
                    </Button>
                    <p
                        v-if="error"
                        class="text-sm text-destructive"
                        role="alert"
                    >
                        {{ error }}
                    </p>
                </div>
            </div>

            <nav
                v-if="previous_lesson || next_lesson"
                aria-label="Navegação entre aulas"
                class="flex flex-wrap justify-between gap-2"
            >
                <Button v-if="previous_lesson" variant="ghost" as-child>
                    <Link :href="show([course.slug, previous_lesson.slug])">
                        <ChevronLeft /> Aula anterior
                    </Link>
                </Button>
                <span v-else />
                <Button v-if="next_lesson" as-child>
                    <Link
                        :href="show([course.slug, next_lesson.slug])"
                        data-test="next-lesson-link"
                    >
                        Próxima aula <ChevronRight />
                    </Link>
                </Button>
            </nav>

            <section
                v-if="lesson.description_html"
                class="space-y-2 rounded-xl border bg-card p-4 text-card-foreground md:p-6"
            >
                <h2 class="font-semibold">Sobre a aula</h2>
                <!-- eslint-disable-next-line vue/no-v-html -- sanitized Markdown rendered by the server -->
                <div class="rich-text" v-html="lesson.description_html" />
            </section>

            <section
                v-if="attachments.length"
                class="space-y-3 rounded-xl border bg-card p-4 text-card-foreground md:p-6"
            >
                <h2 class="font-semibold">Materiais</h2>
                <ul class="grid gap-2 sm:grid-cols-2">
                    <li
                        v-for="attachment in attachments"
                        :key="attachment.id"
                        class="flex items-center gap-3 rounded-lg border p-2"
                        data-test="lesson-material"
                    >
                        <button
                            v-if="attachment.kind === 'image'"
                            type="button"
                            class="size-12 shrink-0 overflow-hidden rounded-md bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            :aria-label="`Ampliar ${attachment.original_name}`"
                            @click="viewedImage = attachment"
                        >
                            <img
                                :src="attachment.url"
                                alt=""
                                loading="lazy"
                                class="size-full object-cover"
                            />
                        </button>
                        <a
                            v-else
                            :href="attachment.url"
                            target="_blank"
                            rel="noopener"
                            class="flex size-12 shrink-0 items-center justify-center rounded-md bg-muted"
                            :aria-label="`Abrir ${attachment.original_name}`"
                        >
                            <FileText class="size-5" aria-hidden="true" />
                        </a>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ attachment.original_name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    attachment.kind === 'pdf' ? 'PDF' : 'Imagem'
                                }}
                                · {{ formatBytes(attachment.size) }}
                            </p>
                        </div>

                        <Button variant="ghost" size="icon" as-child>
                            <a
                                :href="attachment.download_url"
                                :aria-label="`Baixar ${attachment.original_name}`"
                            >
                                <Download />
                            </a>
                        </Button>
                    </li>
                </ul>
            </section>

            <LessonComments :lesson-id="lesson.id" :comments="comments" />
        </div>

        <aside
            class="h-fit space-y-3 rounded-xl border bg-card p-3 text-card-foreground lg:sticky lg:top-4"
        >
            <Link
                :href="showCourse(course.slug)"
                class="block px-2 text-sm font-semibold hover:underline"
            >
                {{ course.title }}
            </Link>
            <CourseOutline
                :course-slug="course.slug"
                :modules="modules"
                :current-lesson-id="lesson.id"
            />
        </aside>

        <Dialog
            :open="viewedImage !== null"
            @update:open="(open) => !open && (viewedImage = null)"
        >
            <DialogContent class="sm:max-w-4xl">
                <DialogTitle>{{ viewedImage?.original_name }}</DialogTitle>
                <DialogDescription class="sr-only">
                    Visualização do material da aula
                </DialogDescription>
                <img
                    v-if="viewedImage"
                    :src="viewedImage.url"
                    :alt="viewedImage.original_name"
                    class="max-h-[75vh] w-full rounded-md object-contain"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
