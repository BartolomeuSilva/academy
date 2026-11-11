<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/community';
import { create, store } from '@/routes/community/topics';

const props = defineProps<{
    spaces: { id: number; name: string; slug: string }[];
    space: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Comunidade', href: index() },
            { title: 'Novo tópico', href: create() },
        ],
    },
});

const defaultSpaceId =
    props.spaces.find((space) => space.slug === props.space)?.id ??
    props.spaces.find((space) => space.slug === 'geral')?.id ??
    props.spaces[0]?.id;
</script>

<template>
    <Head title="Novo tópico" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <header class="space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight">Novo tópico</h1>
            <p class="text-muted-foreground">
                Conte o que você precisa: quanto mais detalhes, mais fácil
                alguém ajudar.
            </p>
        </header>

        <Form
            v-bind="store.form()"
            class="max-w-3xl space-y-5 rounded-xl border bg-card p-4 text-card-foreground md:p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="space_id">Espaço</Label>
                <select
                    id="space_id"
                    name="space_id"
                    :value="defaultSpaceId"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30"
                    data-test="topic-space-select"
                >
                    <option
                        v-for="space in spaces"
                        :key="space.id"
                        :value="space.id"
                    >
                        {{ space.name }}
                    </option>
                </select>
                <InputError :message="errors.space_id" />
            </div>

            <div class="grid gap-2">
                <Label for="title">Título</Label>
                <Input
                    id="title"
                    name="title"
                    maxlength="160"
                    required
                    placeholder="Ex.: Como conecto o WhatsApp ao ZendFlow?"
                    data-test="topic-title-input"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="body">Mensagem</Label>
                <Textarea
                    id="body"
                    name="body"
                    rows="8"
                    maxlength="5000"
                    required
                    aria-describedby="body-hint"
                    data-test="topic-body-input"
                />
                <p id="body-hint" class="text-xs text-muted-foreground">
                    Dica: use **negrito**, listas com "- " e links.
                </p>
                <InputError :message="errors.body" />
            </div>

            <div class="flex gap-2">
                <Button
                    type="submit"
                    :disabled="processing"
                    data-test="publish-topic-button"
                >
                    Publicar tópico
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
