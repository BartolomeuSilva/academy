<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

withDefaults(
    defineProps<{
        title: string;
        description: string;
        confirmLabel?: string;
        confirmVariant?: 'destructive' | 'default';
        processing?: boolean;
    }>(),
    {
        confirmLabel: 'Excluir',
        confirmVariant: 'destructive',
        processing: false,
    },
);

const emit = defineEmits<{
    confirm: [close: () => void];
}>();

const open = ref(false);

function confirm(): void {
    emit('confirm', () => (open.value = false));
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancelar</Button>
                </DialogClose>
                <Button
                    :variant="confirmVariant"
                    :disabled="processing"
                    data-test="confirm-dialog-button"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
