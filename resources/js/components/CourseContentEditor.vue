<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { GripVertical, Paperclip, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { order as lessonOrder } from '@/routes/admin/courses/lessons';
import {
    order as moduleOrder,
    store as storeModule,
} from '@/routes/admin/courses/modules';
import { edit as editLesson } from '@/routes/admin/lessons';
import {
    destroy as destroyModule,
    update as updateModule,
} from '@/routes/admin/modules';
import { store as storeLesson } from '@/routes/admin/modules/lessons';
import type { AdminModule } from '@/types';

const props = defineProps<{
    courseId: number;
    modules: AdminModule[];
}>();

const modules = ref<AdminModule[]>(clone(props.modules));

watch(
    () => props.modules,
    (value) => (modules.value = clone(value)),
);

function clone(value: AdminModule[]): AdminModule[] {
    return value.map((module) => ({ ...module, lessons: [...module.lessons] }));
}

const visitOptions = { preserveScroll: true, preserveState: true } as const;

function saveModuleOrder(): void {
    router.put(
        moduleOrder.url(props.courseId),
        { ids: modules.value.map((module) => module.id) },
        visitOptions,
    );
}

function saveLessonOrder(): void {
    router.put(
        lessonOrder.url(props.courseId),
        {
            modules: modules.value.map((module) => ({
                id: module.id,
                lesson_ids: module.lessons.map((lesson) => lesson.id),
            })),
        },
        visitOptions,
    );
}

const newModuleForm = useForm({ title: '' });

function addModule(): void {
    newModuleForm.post(storeModule.url(props.courseId), {
        preserveScroll: true,
        onSuccess: () => newModuleForm.reset(),
    });
}

const newLessonTitles = ref<Record<number, string>>({});
const lessonErrors = ref<Record<number, string | undefined>>({});

function addLesson(moduleId: number): void {
    router.post(
        storeLesson.url(moduleId),
        { title: newLessonTitles.value[moduleId] ?? '' },
        {
            preserveScroll: true,
            onSuccess: () => {
                newLessonTitles.value[moduleId] = '';
                lessonErrors.value[moduleId] = undefined;
            },
            onError: (errors) => (lessonErrors.value[moduleId] = errors.title),
        },
    );
}

const renamingModuleId = ref<number | null>(null);
const renameForm = useForm({ title: '' });

function startRename(module: AdminModule): void {
    renamingModuleId.value = module.id;
    renameForm.title = module.title;
    renameForm.clearErrors();
}

function saveRename(moduleId: number): void {
    renameForm.put(updateModule.url(moduleId), {
        preserveScroll: true,
        onSuccess: () => (renamingModuleId.value = null),
    });
}

function removeModule(moduleId: number, close: () => void): void {
    router.delete(destroyModule.url(moduleId), {
        preserveScroll: true,
        onSuccess: close,
    });
}
</script>

<template>
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold">Conteúdo</h2>
            <p class="text-sm text-muted-foreground">
                Arraste módulos e aulas para reordenar. Aulas podem ser movidas
                entre módulos.
            </p>
        </div>

        <p
            v-if="modules.length === 0"
            class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            Nenhum módulo ainda. Adicione o primeiro abaixo.
        </p>

        <VueDraggable
            v-model="modules"
            :animation="150"
            handle="[data-module-handle]"
            class="space-y-4"
            @end="saveModuleOrder"
        >
            <div
                v-for="module in modules"
                :key="module.id"
                class="rounded-xl border bg-card text-card-foreground"
                data-test="module-card"
            >
                <div class="flex items-center gap-2 border-b p-3">
                    <button
                        type="button"
                        data-module-handle
                        class="cursor-grab text-muted-foreground hover:text-foreground"
                        aria-label="Arrastar módulo"
                    >
                        <GripVertical class="size-5" />
                    </button>

                    <form
                        v-if="renamingModuleId === module.id"
                        class="flex flex-1 items-start gap-2"
                        @submit.prevent="saveRename(module.id)"
                    >
                        <div class="flex-1">
                            <Input
                                v-model="renameForm.title"
                                v-focus
                                aria-label="Nome do módulo"
                            />
                            <InputError :message="renameForm.errors.title" />
                        </div>
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="renameForm.processing"
                        >
                            Salvar
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            @click="renamingModuleId = null"
                        >
                            Cancelar
                        </Button>
                    </form>

                    <template v-else>
                        <h3 class="flex-1 font-medium">{{ module.title }}</h3>
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            aria-label="Renomear módulo"
                            @click="startRename(module)"
                        >
                            <Pencil />
                        </Button>
                        <ConfirmDialog
                            :title="`Excluir o módulo “${module.title}”?`"
                            description="Todas as aulas e anexos deste módulo serão excluídos permanentemente."
                            @confirm="(close) => removeModule(module.id, close)"
                        >
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                aria-label="Excluir módulo"
                            >
                                <Trash2 />
                            </Button>
                        </ConfirmDialog>
                    </template>
                </div>

                <p
                    v-if="module.lessons.length === 0"
                    class="px-3 pt-3 text-sm text-muted-foreground"
                >
                    Nenhuma aula. Adicione abaixo ou arraste de outro módulo.
                </p>
                <VueDraggable
                    v-model="module.lessons"
                    :animation="150"
                    group="lessons"
                    handle="[data-lesson-handle]"
                    class="min-h-10 divide-y"
                    @end="saveLessonOrder"
                >
                    <div
                        v-for="lesson in module.lessons"
                        :key="lesson.id"
                        class="flex items-center gap-2 px-3 py-2"
                    >
                        <button
                            type="button"
                            data-lesson-handle
                            class="cursor-grab text-muted-foreground hover:text-foreground"
                            aria-label="Arrastar aula"
                        >
                            <GripVertical class="size-4" />
                        </button>
                        <Link
                            :href="editLesson(lesson.id)"
                            class="min-w-0 flex-1 truncate text-sm hover:underline"
                        >
                            {{ lesson.title }}
                        </Link>
                        <span
                            v-if="lesson.attachments_count"
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                            :title="`${lesson.attachments_count} anexo(s)`"
                        >
                            <Paperclip class="size-3" />
                            {{ lesson.attachments_count }}
                        </span>
                        <StatusBadge :status="lesson.status" />
                    </div>
                </VueDraggable>

                <form
                    class="flex items-start gap-2 border-t p-3"
                    @submit.prevent="addLesson(module.id)"
                >
                    <div class="flex-1">
                        <Input
                            v-model="newLessonTitles[module.id]"
                            placeholder="Título da nova aula"
                            aria-label="Título da nova aula"
                            data-test="new-lesson-input"
                        />
                        <InputError :message="lessonErrors[module.id]" />
                    </div>
                    <Button type="submit" size="sm" variant="outline">
                        <Plus /> Aula
                    </Button>
                </form>
            </div>
        </VueDraggable>

        <form class="flex items-start gap-2" @submit.prevent="addModule">
            <div class="flex-1">
                <Input
                    v-model="newModuleForm.title"
                    placeholder="Título do novo módulo"
                    aria-label="Título do novo módulo"
                    data-test="new-module-input"
                />
                <InputError :message="newModuleForm.errors.title" />
            </div>
            <Button type="submit" :disabled="newModuleForm.processing">
                <Plus /> Módulo
            </Button>
        </form>
    </section>
</template>
