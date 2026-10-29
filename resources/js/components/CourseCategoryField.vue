<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    categories: string[];
    defaultValue?: string | null;
    error?: string;
}>();
</script>

<template>
    <div class="grid gap-2">
        <Label for="category">Categoria</Label>
        <Input
            id="category"
            name="category"
            list="course-categories"
            :default-value="defaultValue ?? ''"
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
            Cursos da mesma categoria formam uma fileira na vitrine.
        </p>
        <InputError :message="error" />
    </div>
</template>
