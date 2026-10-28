<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { Pencil, Reply, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import { formatDateTime } from '@/lib/format';
import { destroy, store, update } from '@/routes/lessons/comments';
import type { LessonComment } from '@/types';

const props = defineProps<{
    lessonId: number;
    comment: LessonComment;
    canReply?: boolean;
}>();

const editing = ref(false);
const replying = ref(false);

const visitOptions = {
    preserveScroll: true,
    preserveState: true,
    only: ['comments'],
};

function remove(close: () => void): void {
    router.delete(destroy.url([props.lessonId, props.comment.id]), {
        ...visitOptions,
        onSuccess: close,
    });
}
</script>

<template>
    <article class="flex gap-3" data-test="comment">
        <Avatar class="size-8 shrink-0">
            <AvatarFallback class="text-xs">
                {{ comment.author ? getInitials(comment.author) : '–' }}
            </AvatarFallback>
        </Avatar>

        <div class="min-w-0 flex-1 space-y-1">
            <p
                v-if="comment.deleted"
                class="text-sm text-muted-foreground italic"
            >
                Comentário removido
            </p>

            <template v-else>
                <p class="flex flex-wrap items-baseline gap-x-2 text-sm">
                    <span class="font-medium">{{ comment.author }}</span>
                    <span
                        v-if="comment.created_at"
                        class="text-xs text-muted-foreground"
                    >
                        {{ formatDateTime(comment.created_at) }}
                    </span>
                    <span
                        v-if="comment.edited"
                        class="text-xs text-muted-foreground"
                    >
                        (editado)
                    </span>
                </p>

                <Form
                    v-if="editing"
                    v-bind="update.form([lessonId, comment.id])"
                    :options="visitOptions"
                    class="space-y-2"
                    v-slot="{ errors, processing }"
                    @success="editing = false"
                >
                    <Textarea
                        name="body"
                        :default-value="comment.body ?? ''"
                        aria-label="Editar comentário"
                        data-test="edit-comment-input"
                        maxlength="2000"
                        required
                    />
                    <InputError :message="errors.body" />
                    <div class="flex gap-2">
                        <Button size="sm" :disabled="processing">
                            Salvar
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            @click="editing = false"
                        >
                            Cancelar
                        </Button>
                    </div>
                </Form>

                <p
                    v-else
                    class="text-sm break-words whitespace-pre-line"
                    data-test="comment-body"
                >
                    {{ comment.body }}
                </p>

                <div v-if="!editing" class="-ml-2 flex flex-wrap gap-1">
                    <Button
                        v-if="canReply"
                        size="sm"
                        variant="ghost"
                        data-test="reply-comment-button"
                        @click="replying = !replying"
                    >
                        <Reply /> Responder
                    </Button>
                    <Button
                        v-if="comment.can_update"
                        size="sm"
                        variant="ghost"
                        data-test="edit-comment-button"
                        @click="editing = true"
                    >
                        <Pencil /> Editar
                    </Button>
                    <ConfirmDialog
                        v-if="comment.can_delete"
                        title="Excluir comentário?"
                        description="O comentário será removido da aula."
                        @confirm="remove"
                    >
                        <Button
                            size="sm"
                            variant="ghost"
                            data-test="delete-comment-button"
                        >
                            <Trash2 /> Excluir
                        </Button>
                    </ConfirmDialog>
                </div>
            </template>

            <slot />

            <Form
                v-if="replying"
                v-bind="store.form(lessonId)"
                :options="visitOptions"
                class="space-y-2 pt-2"
                v-slot="{ errors, processing }"
                @success="replying = false"
            >
                <input type="hidden" name="parent_id" :value="comment.id" />
                <Textarea
                    name="body"
                    :aria-label="`Responder a ${comment.author}`"
                    placeholder="Escreva sua resposta"
                    data-test="reply-input"
                    maxlength="2000"
                    required
                />
                <InputError :message="errors.body ?? errors.parent_id" />
                <div class="flex gap-2">
                    <Button
                        size="sm"
                        :disabled="processing"
                        data-test="send-reply-button"
                    >
                        Responder
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        @click="replying = false"
                    >
                        Cancelar
                    </Button>
                </div>
            </Form>
        </div>
    </article>
</template>
