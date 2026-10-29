<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
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
import { index } from '@/routes/courses';
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

    <div class="flex min-w-0 flex-1 flex-col">
        <div
            class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b px-4 py-3 text-sm"
        >
            <Link
                :href="index()"
                class="flex items-center gap-1 font-medium text-muted-foreground hover:text-foreground"
                data-test="back-to-catalog-link"
            >
                <ArrowLeft class="size-4" /> Meus cursos
            </Link>
            <span
                class="hidden h-4 w-px bg-border sm:block"
                aria-hidden="true"
            />
            <span
                class="min-w-0 flex-1 truncate text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                {{ course.title }}
            </span>
            <div
                class="flex items-center gap-2"
                role="progressbar"
                :aria-label="`Progresso em ${course.title}`"
                :aria-valuenow="completedCount"
                aria-valuemin="0"
                :aria-valuemax="allLessons.length"
                :aria-valuetext="`${completedCount} de ${allLessons.length} aulas concluídas`"
            >
                <div
                    v-if="allLessons.length <= 40"
                    class="flex gap-0.5"
                    aria-hidden="true"
                >
                    <span
                        v-for="item in allLessons"
                        :key="item.id"
                        class="h-1.5 w-2.5 rounded-full"
                        :class="[
                            item.completed ? 'bg-primary' : 'bg-primary/15',
                            item.id === lesson.id
                                ? 'ring-2 ring-primary ring-offset-1 ring-offset-background'
                                : '',
                        ]"
                    />
                </div>
                <span class="text-xs text-muted-foreground tabular-nums">
                    {{ completedCount }}/{{ allLessons.length }}
                </span>
            </div>
        </div>

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
                            <CircleCheck
                                v-if="completed"
                                class="text-primary"
                            />
                            <Circle v-else />
                            {{
                                completed
                                    ? 'Aula concluída'
                                    : 'Marcar como concluída'
                            }}
                        </Button>
                    </div>
                    <p
                        v-if="error"
                        class="w-full text-sm text-destructive"
                        role="alert"
                    >
                        {{ error }}
                    </p>
                </div>

                <nav
                    v-if="previous_lesson || next_lesson"
                    aria-label="Navegação entre aulas"
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <Link
                        v-if="previous_lesson"
                        :href="show([course.slug, previous_lesson.slug])"
                        class="group flex items-center gap-3 rounded-xl border bg-card p-3 text-card-foreground hover:bg-accent"
                        data-test="previous-lesson-link"
                    >
                        <ChevronLeft
                            class="size-5 shrink-0 text-muted-foreground group-hover:text-foreground"
                        />
                        <span class="min-w-0">
                            <span
                                class="block text-[11px] font-medium tracking-wider text-muted-foreground uppercase"
                                >Anterior</span
                            >
                            <span class="block truncate text-sm font-medium">{{
                                previous_lesson.title
                            }}</span>
                        </span>
                    </Link>
                    <span v-else class="hidden sm:block" />
                    <Link
                        v-if="next_lesson"
                        :href="show([course.slug, next_lesson.slug])"
                        class="group flex items-center justify-end gap-3 rounded-xl border bg-card p-3 text-right text-card-foreground hover:bg-accent"
                        data-test="next-lesson-link"
                    >
                        <span class="min-w-0">
                            <span
                                class="block text-[11px] font-medium tracking-wider text-muted-foreground uppercase"
                                >Próxima</span
                            >
                            <span class="block truncate text-sm font-medium">{{
                                next_lesson.title
                            }}</span>
                        </span>
                        <ChevronRight
                            class="size-5 shrink-0 text-muted-foreground group-hover:text-foreground"
                        />
                    </Link>
                </nav>

                <Tabs default-value="sobre" class="gap-4">
                    <TabsList class="w-full justify-start sm:w-fit">
                        <TabsTrigger value="sobre" data-test="tab-about"
                            >Sobre</TabsTrigger
                        >
                        <TabsTrigger
                            value="materiais"
                            data-test="tab-materials"
                        >
                            Materiais
                            <span
                                v-if="attachments.length"
                                class="text-muted-foreground"
                                >({{ attachments.length }})</span
                            >
                        </TabsTrigger>
                        <TabsTrigger
                            value="comentarios"
                            data-test="tab-comments"
                        >
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
                        <section
                            v-if="lesson.description_html"
                            class="space-y-2"
                        >
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
                                    <FileText
                                        class="size-5"
                                        aria-hidden="true"
                                    />
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
                <div
                    class="flex items-baseline justify-between gap-2 border-b p-4"
                >
                    <p
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Conteúdo
                    </p>
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
    </div>
</template>
