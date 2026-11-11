<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { CircleCheck, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import CommunityAuthorName from '@/components/CommunityAuthorName.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import ReportDialog from '@/components/ReportDialog.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import { formatRelativeTime } from '@/lib/format';
import { destroy, report, update } from '@/routes/community/topics/replies';
import {
    destroy as unmarkSolution,
    store as markSolution,
} from '@/routes/community/topics/solution';
import type { CommunityReply } from '@/types';

const props = defineProps<{
    topicId: number;
    reply: CommunityReply;
    canSolve: boolean;
    /** Shown above the replies as the topic's answer. */
    highlighted?: boolean;
}>();

const editing = ref(false);

function remove(close: () => void): void {
    router.delete(destroy.url([props.topicId, props.reply.id]), {
        preserveScroll: true,
        onSuccess: close,
    });
}

function toggleSolution(): void {
    if (props.reply.is_solution) {
        router.delete(unmarkSolution.url(props.topicId), {
            preserveScroll: true,
        });
    } else {
        router.post(
            markSolution.url([props.topicId, props.reply.id]),
            {},
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <article
        :id="highlighted ? undefined : `resposta-${reply.id}`"
        class="flex scroll-mt-20 gap-3 rounded-xl border bg-card p-4 text-card-foreground"
        :class="
            highlighted
                ? 'border-primary/50 bg-accent/40 ring-1 ring-primary/30'
                : ''
        "
        data-test="community-reply"
    >
        <Avatar class="size-9 shrink-0">
            <AvatarFallback class="text-xs">{{
                getInitials(reply.author.name)
            }}</AvatarFallback>
        </Avatar>
        <div class="min-w-0 flex-1 space-y-2">
            <p
                class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground"
            >
                <CommunityAuthorName :author="reply.author" />
                <span v-if="reply.created_at">{{
                    formatRelativeTime(reply.created_at)
                }}</span>
                <span v-if="reply.edited">· editada</span>
                <span
                    v-if="reply.is_solution && !highlighted"
                    class="flex items-center gap-1 font-medium text-primary"
                >
                    <CircleCheck class="size-3.5" aria-hidden="true" />
                    Resolveu o tópico
                </span>
            </p>

            <Form
                v-if="editing"
                v-bind="update.form([topicId, reply.id])"
                :options="{ preserveScroll: true }"
                class="space-y-2"
                v-slot="{ errors, processing }"
                @success="editing = false"
            >
                <Textarea
                    name="body"
                    :default-value="reply.body ?? ''"
                    maxlength="3000"
                    required
                    aria-label="Editar resposta"
                />
                <InputError :message="errors.body" />
                <div class="flex gap-2">
                    <Button size="sm" :disabled="processing">Salvar</Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        @click="editing = false"
                        >Cancelar</Button
                    >
                </div>
            </Form>
            <!-- eslint-disable-next-line vue/no-v-html -- sanitized Markdown rendered by the server -->
            <div v-else class="rich-text" v-html="reply.body_html" />

            <div
                v-if="!editing"
                class="-ml-2 flex flex-wrap items-center gap-1"
            >
                <Button
                    v-if="canSolve"
                    variant="ghost"
                    size="sm"
                    :class="
                        reply.is_solution
                            ? 'text-primary'
                            : 'text-muted-foreground'
                    "
                    data-test="toggle-solution-button"
                    @click="toggleSolution"
                >
                    <CircleCheck />
                    {{
                        reply.is_solution
                            ? 'Desmarcar solução'
                            : 'Marcar como solução'
                    }}
                </Button>
                <Button
                    v-if="reply.can.update"
                    variant="ghost"
                    size="sm"
                    class="text-muted-foreground"
                    @click="editing = true"
                >
                    <Pencil /> Editar
                </Button>
                <ConfirmDialog
                    v-if="reply.can.delete"
                    title="Excluir esta resposta?"
                    description="A resposta será removida da conversa."
                    @confirm="remove"
                >
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-muted-foreground"
                    >
                        <Trash2 /> Excluir
                    </Button>
                </ConfirmDialog>
                <ReportDialog
                    v-if="reply.can.report"
                    :action="report.form([topicId, reply.id])"
                    subject="resposta"
                />
            </div>
        </div>
    </article>
</template>
