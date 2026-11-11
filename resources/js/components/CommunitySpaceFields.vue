<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AdminCommunitySpace } from '@/types';

defineProps<{
    /** Prefix that keeps field ids unique when several forms share the page. */
    idPrefix: string;
    space?: AdminCommunitySpace;
    errors: Record<string, string>;
}>();
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1.5fr)_6rem]">
        <div class="grid gap-1.5">
            <Label :for="`${idPrefix}-name`">Nome</Label>
            <Input
                :id="`${idPrefix}-name`"
                name="name"
                :default-value="space?.name"
                maxlength="80"
                required
            />
            <InputError :message="errors.name" />
        </div>
        <div class="grid gap-1.5">
            <Label :for="`${idPrefix}-slug`">Endereço</Label>
            <Input
                :id="`${idPrefix}-slug`"
                name="slug"
                :default-value="space?.slug"
                maxlength="100"
                placeholder="gerado pelo nome"
            />
            <InputError :message="errors.slug" />
        </div>
        <div class="grid gap-1.5">
            <Label :for="`${idPrefix}-position`">Ordem</Label>
            <Input
                :id="`${idPrefix}-position`"
                name="position"
                type="number"
                min="0"
                :default-value="space?.position"
            />
            <InputError :message="errors.position" />
        </div>
        <div class="grid gap-1.5 sm:col-span-3">
            <Label :for="`${idPrefix}-description`">Descrição</Label>
            <Input
                :id="`${idPrefix}-description`"
                name="description"
                :default-value="space?.description ?? ''"
                maxlength="255"
            />
            <InputError :message="errors.description" />
        </div>
        <label class="flex items-center gap-2 text-sm sm:col-span-3">
            <input type="hidden" name="staff_only" value="0" />
            <input
                type="checkbox"
                name="staff_only"
                value="1"
                :checked="space?.staff_only"
                class="size-4 accent-primary"
            />
            Só a equipe cria tópicos (os alunos podem responder)
        </label>
    </div>
</template>
