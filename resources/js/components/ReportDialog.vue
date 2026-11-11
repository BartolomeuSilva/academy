<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Flag } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

defineProps<{
    /** Wayfinder form attributes of the report endpoint. */
    action: { action: string; method: 'post' };
    subject: string;
    /** Icon-only trigger, e.g. in a message toolbar. */
    compact?: boolean;
}>();

const open = ref(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                variant="ghost"
                :size="compact ? 'icon' : 'sm'"
                :class="compact ? 'size-7' : 'text-muted-foreground'"
                :aria-label="compact ? 'Denunciar' : undefined"
                data-test="report-button"
            >
                <Flag />
                <template v-if="!compact">Denunciar</template>
            </Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="action"
                :options="{ preserveScroll: true }"
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>Denunciar {{ subject }}</DialogTitle>
                    <DialogDescription>
                        A equipe vai analisar. Quem escreveu não fica sabendo
                        quem denunciou.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="report-reason">Motivo (opcional)</Label>
                    <Textarea
                        id="report-reason"
                        name="reason"
                        maxlength="500"
                        placeholder="Ex.: propaganda, ofensa, informação pessoal"
                    />
                    <InputError :message="errors.reason" />
                </div>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary"
                            >Cancelar</Button
                        >
                    </DialogClose>
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="send-report-button"
                    >
                        Enviar denúncia
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
