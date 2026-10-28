<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Eye, Trash2 } from '@lucide/vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import LessonAttachmentsManager from '@/components/LessonAttachmentsManager.vue';
import LessonVideoManager from '@/components/LessonVideoManager.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { edit as editCourse, index as courses } from '@/routes/admin/courses';
import { destroy, edit, update } from '@/routes/admin/lessons';
import { show as showLesson } from '@/routes/lessons';
import type {
    AdminAttachment,
    AdminLesson,
    AdminLessonVideo,
    AdminPendingVideo,
} from '@/types';

const props = defineProps<{
    lesson: AdminLesson;
    video: AdminLessonVideo | null;
    pending_video: AdminPendingVideo | null;
    attachments: AdminAttachment[];
}>();

defineOptions({
    layout: (props: { lesson: AdminLesson }) => ({
        breadcrumbs: [
            { title: 'Cursos', href: courses() },
            {
                title: props.lesson.course.title,
                href: editCourse(props.lesson.course.id),
            },
            { title: props.lesson.title, href: edit(props.lesson.id) },
        ],
    }),
});

function removeLesson(close: () => void): void {
    router.delete(destroy.url(props.lesson.id), { onSuccess: close });
}
</script>

<template>
    <Head :title="lesson.title" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <p class="text-sm text-muted-foreground">
                {{ lesson.module.title }}
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-xl font-semibold tracking-tight">
                    {{ lesson.title }}
                </h1>
                <StatusBadge :status="lesson.status" />
                <Button variant="outline" size="sm" class="ml-auto" as-child>
                    <Link
                        :href="showLesson([lesson.course.slug, lesson.slug])"
                        data-test="preview-lesson-link"
                    >
                        <Eye /> Ver como aluno
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-6">
                <LessonVideoManager
                    :lesson-id="lesson.id"
                    :video="video"
                    :pending-video="pending_video"
                />

                <LessonAttachmentsManager
                    :lesson-id="lesson.id"
                    :attachments="attachments"
                />
            </div>

            <aside class="space-y-6">
                <section
                    class="rounded-xl border bg-card p-4 text-card-foreground"
                >
                    <h2 class="mb-4 font-medium">Detalhes</h2>

                    <Form
                        v-bind="update.form(lesson.id)"
                        :options="{ preserveScroll: true }"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="title">Título</Label>
                            <Input
                                id="title"
                                name="title"
                                :default-value="lesson.title"
                                required
                            />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="slug">Endereço (slug)</Label>
                            <Input
                                id="slug"
                                name="slug"
                                :default-value="lesson.slug"
                                required
                            />
                            <InputError :message="errors.slug" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Descrição</Label>
                            <Textarea
                                id="description"
                                name="description"
                                :default-value="lesson.description ?? ''"
                                rows="6"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <select
                                id="status"
                                name="status"
                                :value="lesson.status"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30"
                            >
                                <option value="draft">Rascunho</option>
                                <option value="published" :disabled="!video">
                                    Publicado{{
                                        video ? '' : ' (envie o vídeo primeiro)'
                                    }}
                                </option>
                            </select>
                            <InputError :message="errors.status" />
                        </div>

                        <Button
                            type="submit"
                            :disabled="processing"
                            data-test="save-lesson-button"
                        >
                            Salvar
                        </Button>
                    </Form>
                </section>

                <section
                    class="space-y-3 rounded-xl border border-destructive/30 p-4"
                >
                    <h2 class="font-medium">Excluir aula</h2>
                    <p class="text-sm text-muted-foreground">
                        Remove a aula e todos os anexos. Esta ação não pode ser
                        desfeita.
                    </p>
                    <ConfirmDialog
                        :title="`Excluir a aula “${lesson.title}”?`"
                        description="A aula e os anexos serão excluídos permanentemente."
                        confirm-label="Excluir aula"
                        @confirm="removeLesson"
                    >
                        <Button variant="destructive" size="sm">
                            <Trash2 /> Excluir aula
                        </Button>
                    </ConfirmDialog>
                </section>
            </aside>
        </div>
    </div>
</template>
