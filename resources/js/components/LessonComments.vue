<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { MessageSquare } from '@lucide/vue';
import { ref } from 'vue';
import CommentItem from '@/components/CommentItem.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/lessons/comments';
import type { LessonComments } from '@/types';

const COMMENTS_PER_PAGE = 10;

const props = defineProps<{
    lessonId: number;
    comments: LessonComments;
}>();

const loadingMore = ref(false);

function loadMore(): void {
    router.reload({
        data: { comentarios: props.comments.data.length + COMMENTS_PER_PAGE },
        only: ['comments'],
        onStart: () => (loadingMore.value = true),
        onFinish: () => (loadingMore.value = false),
    });
}
</script>

<template>
    <section
        class="space-y-4 rounded-xl border bg-card p-4 text-card-foreground md:p-6"
        aria-labelledby="comments-heading"
    >
        <h2 id="comments-heading" class="font-semibold">
            Comentários
            <span
                v-if="comments.total"
                class="font-normal text-muted-foreground"
            >
                ({{ comments.total }})
            </span>
        </h2>

        <Form
            v-bind="store.form(lessonId)"
            :options="{
                preserveScroll: true,
                preserveState: true,
                only: ['comments'],
            }"
            reset-on-success
            class="space-y-2"
            v-slot="{ errors, processing }"
        >
            <Textarea
                name="body"
                aria-label="Novo comentário"
                placeholder="Tire uma dúvida ou deixe um comentário sobre a aula"
                maxlength="2000"
                required
                data-test="new-comment-input"
            />
            <InputError :message="errors.body" />
            <Button :disabled="processing" data-test="send-comment-button">
                Comentar
            </Button>
        </Form>

        <div
            v-if="comments.data.length === 0"
            class="flex flex-col items-center gap-2 py-6 text-center text-muted-foreground"
        >
            <MessageSquare class="size-8" aria-hidden="true" />
            <p class="text-sm">Nenhum comentário ainda. Seja o primeiro!</p>
        </div>

        <ul v-else class="space-y-6">
            <li v-for="comment in comments.data" :key="comment.id">
                <CommentItem
                    :lesson-id="lessonId"
                    :comment="comment"
                    :can-reply="!comment.deleted"
                >
                    <ul
                        v-if="comment.replies.length"
                        class="space-y-4 border-l pt-3 pl-4"
                    >
                        <li v-for="reply in comment.replies" :key="reply.id">
                            <CommentItem
                                :lesson-id="lessonId"
                                :comment="reply"
                            />
                        </li>
                    </ul>
                </CommentItem>
            </li>
        </ul>

        <Button
            v-if="comments.has_more"
            variant="outline"
            class="w-full"
            :disabled="loadingMore"
            data-test="load-more-comments-button"
            @click="loadMore"
        >
            {{ loadingMore ? 'Carregando…' : 'Carregar mais comentários' }}
        </Button>
    </section>
</template>
