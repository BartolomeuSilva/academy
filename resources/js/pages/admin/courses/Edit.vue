<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Star, StarOff, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import CourseContentEditor from '@/components/CourseContentEditor.vue';
import CourseImageCard from '@/components/CourseImageCard.vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { destroy, edit, index, update } from '@/routes/admin/courses';
import banner from '@/routes/admin/courses/banner';
import cover from '@/routes/admin/courses/cover';
import feature from '@/routes/admin/courses/feature';
import { show as showCourse } from '@/routes/courses';
import type { AdminCourse, AdminModule } from '@/types';

const props = defineProps<{
    course: AdminCourse;
    modules: AdminModule[];
    categories: string[];
}>();

const bannerError = computed(
    () => usePage().props.errors.banner as string | undefined,
);

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

function toggleFeatured(): void {
    if (props.course.featured) {
        router.delete(feature.destroy.url(props.course.id), {
            preserveScroll: true,
        });
    } else {
        router.post(
            feature.store.url(props.course.id),
            {},
            { preserveScroll: true },
        );
    }
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
            <Button variant="outline" size="sm" class="ml-auto" as-child>
                <Link
                    :href="showCourse(course.slug)"
                    data-test="preview-course-link"
                >
                    <Eye /> Ver como aluno
                </Link>
            </Button>
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
                            <Label for="category">Categoria</Label>
                            <Input
                                id="category"
                                name="category"
                                list="course-categories"
                                :default-value="course.category ?? ''"
                                placeholder="Ex.: Vendas, Atendimento"
                                maxlength="80"
                            />
                            <datalist id="course-categories">
                                <option
                                    v-for="category in categories"
                                    :key="category"
                                    :value="category"
                                />
                            </datalist>
                            <p class="text-xs text-muted-foreground">
                                Cursos da mesma categoria formam uma fileira na
                                vitrine.
                            </p>
                            <InputError :message="errors.category" />
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

                <CourseImageCard
                    title="Capa"
                    field="cover"
                    :image-url="course.cover_url"
                    :upload-url="cover.store.url(course.id)"
                    :remove-url="cover.destroy.url(course.id)"
                    hint="JPG, PNG ou WEBP até 5 MB. Formato vertical 2:3 (ex.: 800 × 1200 px)."
                    preview-class="mx-auto aspect-[2/3] w-40"
                />

                <CourseImageCard
                    title="Banner"
                    field="banner"
                    :image-url="course.banner_url"
                    :upload-url="banner.store.url(course.id)"
                    :remove-url="banner.destroy.url(course.id)"
                    hint="Imagem larga do destaque no topo da vitrine. JPG, PNG ou WEBP até 5 MB (ex.: 1920 × 820 px). Deixe o lado esquerdo mais limpo: o título aparece ali."
                    preview-class="aspect-[21/9]"
                    :error="bannerError"
                >
                    <div class="border-t pt-3">
                        <Button
                            type="button"
                            :variant="course.featured ? 'secondary' : 'default'"
                            size="sm"
                            :disabled="!course.banner_url"
                            data-test="toggle-featured-button"
                            @click="toggleFeatured"
                        >
                            <template v-if="course.featured">
                                <StarOff /> Tirar do destaque
                            </template>
                            <template v-else>
                                <Star /> Destacar na vitrine
                            </template>
                        </Button>
                        <p
                            v-if="course.featured"
                            class="mt-2 text-xs text-muted-foreground"
                        >
                            Este curso aparece no banner do topo da vitrine.
                        </p>
                    </div>
                </CourseImageCard>

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
