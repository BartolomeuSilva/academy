<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { edit, update } from '@/routes/notification-settings';

defineProps<{
    notifyByEmail: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Notificações', href: edit() }],
    },
});
</script>

<template>
    <Head title="Notificações" />

    <h1 class="sr-only">Notificações</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Notificações"
            description="Avisamos no sino quando alguém responde a você na comunidade ou nos comentários das aulas."
        />

        <Form
            v-bind="update.form()"
            :options="{ preserveScroll: true }"
            class="space-y-4"
            v-slot="{ errors, processing }"
        >
            <label class="flex items-start gap-3 text-sm">
                <input type="hidden" name="notify_by_email" value="0" />
                <input
                    type="checkbox"
                    name="notify_by_email"
                    value="1"
                    :checked="notifyByEmail"
                    class="mt-0.5 size-4 accent-primary"
                    data-test="notify-by-email-checkbox"
                />
                <span>
                    <span class="font-medium">Também avisar por e-mail</span>
                    <span class="block text-muted-foreground"
                        >Um e-mail por resposta, com o link para a
                        conversa.</span
                    >
                </span>
            </label>
            <InputError :message="errors.notify_by_email" />
            <Button
                :disabled="processing"
                data-test="save-notification-settings-button"
                >Salvar</Button
            >
        </Form>
    </div>
</template>
