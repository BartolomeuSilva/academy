<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CourseCategoryField from '@/components/CourseCategoryField.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { create, index, store } from '@/routes/admin/courses';

defineProps<{
    categories: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cursos', href: index() },
            { title: 'Novo curso', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Novo curso" />

    <div class="flex max-w-2xl flex-1 flex-col gap-6 p-4">
        <Heading
            title="Novo curso"
            description="Você poderá adicionar capa, módulos e aulas em seguida."
        />

        <Form
            v-bind="store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="title">Título</Label>
                <Input id="title" name="title" required autofocus />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="summary">Resumo</Label>
                <Input
                    id="summary"
                    name="summary"
                    required
                    maxlength="300"
                    placeholder="Aparece no card do catálogo"
                />
                <InputError :message="errors.summary" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Descrição</Label>
                <Textarea id="description" name="description" rows="6" />
                <InputError :message="errors.description" />
            </div>

            <CourseCategoryField
                :categories="categories"
                :error="errors.category"
            />

            <div class="flex items-center gap-3">
                <Button
                    type="submit"
                    :disabled="processing"
                    data-test="create-course-button"
                >
                    Criar curso
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
