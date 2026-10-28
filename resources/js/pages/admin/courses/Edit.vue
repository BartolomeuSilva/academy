<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import CourseContentEditor from '@/components/CourseContentEditor.vue';
import CourseCoverCard from '@/components/CourseCoverCard.vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { destroy, edit, index, update } from '@/routes/admin/courses';
import type { AdminCourse, AdminModule } from '@/types';

const props = defineProps<{
    course: AdminCourse;
    modules: AdminModule[];
}>();

defineOptions({
    layout: (props: { course: AdminCourse }) => ({
        breadcrumbs: [
            { title: 'Cursos', href: index() },
            { title: props.course.title, href: edit(props.course.id) },
        ],
    }),
});

function removeCourse(close: () => void): void {
    router.delete(destroy.url(props.course.id), { onSuccess: close });
}
</script>

<template>
    <Head :title="course.title" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-xl font-semibold tracking-tight">
                {{ course.title }}
            </h1>
            <StatusBadge :status="course.status" />
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <CourseContentEditor :course-id="course.id" :modules="modules" />

            <aside class="space-y-6">
                <section
                    class="rounded-xl border bg-card p-4 text-card-foreground"
                >
                    <h2 class="mb-4 font-medium">Detalhes</h2>

                    <Form
                        v-bind="update.form(course.id)"
                        :options="{ preserveScroll: true }"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="title">Título</Label>
                            <Input
                                id="title"
                                name="title"
                                :default-value="course.title"
                                required
                            />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="slug">Endereço (slug)</Label>
                            <Input
                                id="slug"
                                name="slug"
                                :default-value="course.slug"
                                required
                            />
                            <InputError :message="errors.slug" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="summary">Resumo</Label>
                            <Input
                                id="summary"
                                name="summary"
                                :default-value="course.summary"
                                maxlength="300"
                                required
                            />
                            <InputError :message="errors.summary" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Descrição</Label>
                            <Textarea
                                id="description"
                                name="description"
                                :default-value="course.description ?? ''"
                                rows="5"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <select
                                id="status"
                                name="status"
                                :value="course.status"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30"
                            >
                                <option value="draft">Rascunho</option>
                                <option value="published">Publicado</option>
                            </select>
                            <InputError :message="errors.status" />
                        </div>

                        <Button
                            type="submit"
                            :disabled="processing"
                            data-test="save-course-button"
                        >
                            Salvar
                        </Button>
                    </Form>
                </section>

                <CourseCoverCard
                    :course-id="course.id"
                    :cover-url="course.cover_url"
                />

                <section
                    class="space-y-3 rounded-xl border border-destructive/30 p-4"
                >
                    <h2 class="font-medium">Excluir curso</h2>
                    <p class="text-sm text-muted-foreground">
                        Remove o curso, os módulos, as aulas e todos os
                        arquivos. Esta ação não pode ser desfeita.
                    </p>
                    <ConfirmDialog
                        :title="`Excluir o curso “${course.title}”?`"
                        description="Todo o conteúdo e os arquivos do curso serão excluídos permanentemente."
                        confirm-label="Excluir curso"
                        @confirm="removeCourse"
                    >
                        <Button variant="destructive" size="sm">
                            <Trash2 /> Excluir curso
                        </Button>
                    </ConfirmDialog>
                </section>
            </aside>
        </div>
    </div>
</template>
