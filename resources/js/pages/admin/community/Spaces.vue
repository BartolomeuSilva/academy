<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import CommunitySpaceFields from '@/components/CommunitySpaceFields.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { destroy, index, store, update } from '@/routes/admin/community/spaces';
import type { AdminCommunitySpace } from '@/types';

defineProps<{
    spaces: AdminCommunitySpace[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Espaços da comunidade', href: index() }],
    },
});

const deleteError = computed(
    () => usePage().props.errors.space as string | undefined,
);

function remove(space: AdminCommunitySpace, close: () => void): void {
    router.delete(destroy.url(space.id), {
        preserveScroll: true,
        onFinish: close,
    });
}
</script>

<template>
    <Head title="Espaços da comunidade" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Espaços da comunidade"
            description="Crie um espaço por produto e organize as conversas."
        />

        <InputError :message="deleteError" />

        <ul class="grid gap-3">
            <li
                v-for="space in spaces"
                :key="space.id"
                class="rounded-xl border bg-card p-4 text-card-foreground"
                data-test="admin-community-space"
            >
                <Form
                    v-bind="update.form(space.id)"
                    :options="{ preserveScroll: true }"
                    class="space-y-3"
                    v-slot="{ errors, processing }"
                >
                    <p class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-semibold">{{ space.name }}</span>
                        <Badge v-if="space.staff_only" variant="secondary"
                            >Só a equipe publica</Badge
                        >
                        <span class="text-muted-foreground"
                            >{{ space.topics_count }}
                            {{
                                space.topics_count === 1 ? 'tópico' : 'tópicos'
                            }}</span
                        >
                    </p>
                    <CommunitySpaceFields
                        :id-prefix="`space-${space.id}`"
                        :space="space"
                        :errors="errors"
                    />
                    <div class="flex gap-2">
                        <Button size="sm" :disabled="processing">Salvar</Button>
                        <ConfirmDialog
                            :title="`Excluir o espaço “${space.name}”?`"
                            description="Só é possível excluir espaços sem tópicos."
                            @confirm="(close) => remove(space, close)"
                        >
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                class="text-muted-foreground"
                            >
                                <Trash2 /> Excluir
                            </Button>
                        </ConfirmDialog>
                    </div>
                </Form>
            </li>
        </ul>

        <Form
            v-bind="store.form()"
            :options="{ preserveScroll: true }"
            reset-on-success
            class="space-y-3 rounded-xl border border-dashed bg-card p-4 text-card-foreground"
            v-slot="{ errors, processing }"
        >
            <h2 class="font-semibold">Novo espaço</h2>
            <CommunitySpaceFields id-prefix="new-space" :errors="errors" />
            <Button
                size="sm"
                :disabled="processing"
                data-test="create-space-button"
            >
                Criar espaço
            </Button>
        </Form>
    </div>
</template>
