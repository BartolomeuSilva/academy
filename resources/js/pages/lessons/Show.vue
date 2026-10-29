<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    CircleCheck,
    Circle,
    Download,
    FileText,
    Paperclip,
    Pencil,
    VideoOff,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import CourseOutline from '@/components/CourseOutline.vue';
import LessonComments from '@/components/LessonComments.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import VideoPlayer from '@/components/VideoPlayer.vue';
import { useLessonProgress } from '@/composables/useLessonProgress';
import { formatBytes } from '@/lib/format';
import { edit as editLesson } from '@/routes/admin/lessons';
import { show } from '@/routes/lessons';
import type {
    LessonComments as LessonCommentsData,
    LessonCourse,
    LessonDetails,
    LessonMaterial,
    LessonProgressState,
    LessonVideo,
    OutlineLesson,
    OutlineModule,
} from '@/types';

const AUTOPLAY_SECONDS = 5;

const props = defineProps<{
    course: LessonCourse;
    lesson: LessonDetails;
    video: LessonVideo | null;
    progress: LessonProgressState;
    attachments: LessonMaterial[];
    modules: OutlineModule[];
    previous_lesson: OutlineLesson | null;
    next_lesson: OutlineLesson | null;
    comments: LessonCommentsData;
}>();

const lessonProgress = useLessonProgress(
    props.lesson.id,
    props.progress.completed,
);
const { completed, saving, error } = lessonProgress;

watch(completed, () => router.reload({ only: ['modules'] }));

const viewedImage = ref<LessonMaterial | null>(null);

const isAdmin = computed(() => usePage().props.auth.user?.role === 'admin');

const currentModule = computed(() =>
    props.modules.find((module) =>
        module.lessons.some((lesson) => lesson.id === props.lesson.id),
    ),
);

const allLessons = computed(() =>
    props.modules.flatMap((module) => module.lessons),
);
const completedCount = computed(
    () => allLessons.value.filter((lesson) => lesson.completed).length,
);

const countdown = ref<number | null>(null);
let countdownTimer: ReturnType<typeof setInterval> | undefined;

function stopCountdown(): void {
    clearInterval(countdownTimer);
    countdown.value = null;
}

function goToNextLesson(): void {
    stopCountdown();

    if (props.next_lesson) {
        router.visit(show([props.course.slug, props.next_lesson.slug]));
    }
}

function onEnded(): void {
    lessonProgress.onEnded();

    if (!props.next_lesson) {
        return;
    }

    countdown.value = AUTOPLAY_SECONDS;
    countdownTimer = setInterval(() => {
        countdown.value = (countdown.value ?? 1) - 1;

        if (countdown.value <= 0) {
            goToNextLesson();
        }
    }, 1000);
}

onBeforeUnmount(stopCountdown);
</script>

<template>
    <Head :title="lesson.title" />

    <div
        class="grid flex-1 items-start gap-6 px-4 py-6 lg:grid-cols-[minmax(0,1fr)_22rem]"
    >
        <div class="flex min-w-0 flex-col gap-5">
            <VideoPlayer
                v-if="video"
                :key="lesson.id"
                :src="video.playlist_url"
                :poster="video.poster_url"
                :start-at="progress.start_at_seconds"
                @timeupdate="lessonProgress.onTimeUpdate"
                @pause="lessonProgress.onPause"
                @play="stopCountdown"
                @ended="onEnded"
            >
                <template #overlay>
                    <div
                        v-if="countdown !== null && next_lesson"
                        class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-black/80 p-4 text-center text-white"
                        role="status"
                        data-test="autoplay-overlay"
                    >
                        <p class="text-sm text-white/80">
                            Próxima aula em {{ countdown }} s
                        </p>
                        <p class="text-lg font-semibold">
                            {{ next_lesson.title }}
                        </p>
                        <div class="flex gap-2">
                            <Button @click="goToNextLesson">
                                Assistir agora
                            </Button>
                            <Button
                                variant="secondary"
                                data-test="cancel-autoplay-button"
                                @click="stopCountdown"
                            >
                                Cancelar
                            </Button>
                        </div>
                    </div>
                </template>
            </VideoPlayer>
            <div
                v-else
                class="flex aspect-video flex-col items-center justify-center gap-2 rounded-xl border border-dashed text-muted-foreground"
            >
                <VideoOff class="size-8" aria-hidden="true" />
                <p class="text-sm">Esta aula ainda não tem vídeo.</p>
            </div>

            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 space-y-1">
                    <p class="text-sm text-muted-foreground">
                        {{ course.title
                        }}<template v-if="currentModule">
                            · {{ currentModule.title }}</template
                        >
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-semibold tracking-tight">
                            {{ lesson.title }}
                        </h1>
                        <StatusBadge
                            v-if="lesson.status !== 'published'"
                            :status="lesson.status"
                        />
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Button v-if="isAdmin" variant="ghost" as-child>
                        <Link
                            :href="editLesson(lesson.id)"
                            data-test="edit-lesson-link"
                        >
                            <Pencil /> Editar aula
                        </Link>
                    </Button>
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
                    <nav
                        v-if="previous_lesson || next_lesson"
                        aria-label="Navegação entre aulas"
                        class="flex gap-2"
                    >
                        <Button
                            v-if="previous_lesson"
                            variant="outline"
                            size="icon"
                            as-child
                        >
                            <Link
                                :href="
                                    show([course.slug, previous_lesson.slug])
                                "
                                aria-label="Aula anterior"
                            >
                                <ChevronLeft />
                            </Link>
                        </Button>
                        <Button v-if="next_lesson" as-child>
                            <Link
                                :href="show([course.slug, next_lesson.slug])"
                                data-test="next-lesson-link"
                            >
                                Próxima aula <ChevronRight />
                            </Link>
                        </Button>
                    </nav>
                </div>
                <p
                    v-if="error"
                    class="w-full text-sm text-destructive"
                    role="alert"
                >
                    {{ error }}
                </p>
            </div>

            <Tabs default-value="sobre" class="gap-4">
                <TabsList class="w-full justify-start sm:w-fit">
                    <TabsTrigger value="sobre" data-test="tab-about"
                        >Sobre</TabsTrigger
                    >
                    <TabsTrigger value="materiais" data-test="tab-materials">
                        Materiais
                        <span
                            v-if="attachments.length"
                            class="text-muted-foreground"
                            >({{ attachments.length }})</span
                        >
                    </TabsTrigger>
                    <TabsTrigger value="comentarios" data-test="tab-comments">
                        Comentários
                        <span
                            v-if="comments.total"
                            class="text-muted-foreground"
                            >({{ comments.total }})</span
                        >
                    </TabsTrigger>
                    <TabsTrigger
                        value="aulas"
                        class="lg:hidden"
                        data-test="tab-lessons"
                    >
                        Aulas
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="sobre" class="space-y-6">
                    <section v-if="lesson.description_html" class="space-y-2">
                        <h2 class="font-semibold">Sobre a aula</h2>
                        <!-- eslint-disable-next-line vue/no-v-html -- sanitized Markdown rendered by the server -->
                        <div
                            class="rich-text"
                            v-html="lesson.description_html"
                        />
                    </section>
                    <section class="space-y-2">
                        <h2 class="font-semibold">Sobre o curso</h2>
                        <!-- eslint-disable-next-line vue/no-v-html -- sanitized Markdown rendered by the server -->
                        <div
                            v-if="course.description_html"
                            class="rich-text"
                            v-html="course.description_html"
                        />
                        <p v-else class="text-sm text-muted-foreground">
                            {{ course.summary }}
                        </p>
                    </section>
                </TabsContent>

                <TabsContent value="materiais">
                    <div
                        v-if="attachments.length === 0"
                        class="flex flex-col items-center gap-2 py-8 text-center text-muted-foreground"
                    >
                        <Paperclip class="size-8" aria-hidden="true" />
                        <p class="text-sm">Esta aula não tem materiais.</p>
                    </div>
                    <ul v-else class="grid gap-2 sm:grid-cols-2">
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
                                        attachment.kind === 'pdf'
                                            ? 'PDF'
                                            : 'Imagem'
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
                </TabsContent>

                <TabsContent value="comentarios" :unmount-on-hide="false">
                    <LessonComments
                        :lesson-id="lesson.id"
                        :comments="comments"
                    />
                </TabsContent>

                <TabsContent value="aulas" class="lg:hidden">
                    <div class="overflow-hidden rounded-xl border bg-card">
                        <CourseOutline
                            :course-slug="course.slug"
                            :modules="modules"
                            :current-lesson-id="lesson.id"
                        />
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <aside
            class="hidden overflow-hidden rounded-xl border bg-card text-card-foreground lg:sticky lg:top-4 lg:flex lg:max-h-[calc(100vh-2rem)] lg:flex-col"
            aria-label="Aulas do curso"
        >
            <div class="space-y-2 border-b p-4">
                <p class="font-semibold">{{ course.title }}</p>
                <ProgressBar
                    :completed="completedCount"
                    :total="allLessons.length"
                    :label="`Progresso em ${course.title}`"
                />
                <p class="text-xs text-muted-foreground">
                    {{ completedCount }} de {{ allLessons.length }}
                    {{
                        allLessons.length === 1
                            ? 'aula concluída'
                            : 'aulas concluídas'
                    }}
                </p>
            </div>
            <div class="overflow-y-auto">
                <CourseOutline
                    :course-slug="course.slug"
                    :modules="modules"
                    :current-lesson-id="lesson.id"
                />
            </div>
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
