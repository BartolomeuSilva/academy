<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { CircleCheck, Pencil, Pin, PinOff, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import CommunityAuthorName from '@/components/CommunityAuthorName.vue';
import CommunityReplyItem from '@/components/CommunityReplyItem.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import ReportDialog from '@/components/ReportDialog.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import { formatRelativeTime } from '@/lib/format';
import { index } from '@/routes/community';
import { destroy, move, report, show, update } from '@/routes/community/topics';
import { destroy as unpin, store as pin } from '@/routes/community/topics/pin';
import { store as storeReply } from '@/routes/community/topics/replies';
import type { CommunityReply, CommunityTopic, Paginated } from '@/types';

const props = defineProps<{
    topic: CommunityTopic;
    solution: CommunityReply | null;
    replies: Paginated<CommunityReply>;
    spaces: { id: number; name: string }[];
}>();

defineOptions({
    layout: (props: { topic: CommunityTopic }) => ({
        breadcrumbs: [
            { title: 'Comunidade', href: index() },
            {
                title: props.topic.space.name,
                href: index({ query: { espaco: props.topic.space.slug } }),
            },
            { title: props.topic.title, href: show(props.topic.id) },
        ],
    }),
});

const editing = ref(false);

function remove(close: () => void): void {
    router.delete(destroy.url(props.topic.id), { onSuccess: close });
}

function togglePin(): void {
    if (props.topic.pinned) {
        router.delete(unpin.url(props.topic.id), { preserveScroll: true });
    } else {
        router.post(pin.url(props.topic.id), {}, { preserveScroll: true });
    }
}

function moveTo(event: Event): void {
    router.put(
        move.url(props.topic.id),
        { space_id: Number((event.target as HTMLSelectElement).value) },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="topic.title" />

    <div class="flex max-w-4xl flex-1 flex-col gap-6 p-4">
        <article
            class="space-y-4 rounded-xl border bg-card p-4 text-card-foreground md:p-6"
            data-test="community-topic-detail"
        >
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span
                    class="rounded-full bg-accent px-2 py-0.5 font-medium text-accent-foreground"
                    >{{ topic.space.name }}</span
                >
                <span
                    v-if="topic.pinned"
                    class="flex items-center gap-1 font-medium text-primary"
                >
                    <Pin class="size-3.5" aria-hidden="true" /> Fixado
                </span>
                <span
                    v-if="solution"
                    class="flex items-center gap-1 font-medium text-primary"
                >
                    <CircleCheck class="size-3.5" aria-hidden="true" />
                    Resolvido
                </span>
            </div>

            <Form
                v-if="editing"
                v-bind="update.form(topic.id)"
                :options="{ preserveScroll: true }"
                class="space-y-3"
                v-slot="{ errors, processing }"
                @success="editing = false"
            >
                <Input
                    name="title"
                    :default-value="topic.title"
                    maxlength="160"
                    required
                    aria-label="Título"
                />
                <InputError :message="errors.title" />
                <Textarea
                    name="body"
                    :default-value="topic.body ?? ''"
                    rows="8"
                    maxlength="5000"
                    required
                    aria-label="Mensagem"
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

            <template v-else>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ topic.title }}
                </h1>
                <div class="flex items-center gap-3">
                    <Avatar class="size-9">
                        <AvatarFallback class="text-xs">{{
                            getInitials(topic.author.name)
                        }}</AvatarFallback>
                    </Avatar>
                    <p class="text-xs text-muted-foreground">
                        <CommunityAuthorName :author="topic.author" />
                        <br />
                        <span v-if="topic.created_at">{{
                            formatRelativeTime(topic.created_at)
                        }}</span>
                        <span v-if="topic.edited"> · editado</span>
                    </p>
                </div>
                <!-- eslint-disable-next-line vue/no-v-html -- sanitized Markdown rendered by the server -->
                <div class="rich-text" v-html="topic.body_html" />
            </template>

            <div
                v-if="!editing"
                class="-ml-2 flex flex-wrap items-center gap-1 border-t pt-3"
            >
                <Button
                    v-if="topic.can.update"
                    variant="ghost"
                    size="sm"
                    class="text-muted-foreground"
                    data-test="edit-topic-button"
                    @click="editing = true"
                >
                    <Pencil /> Editar
                </Button>
                <ConfirmDialog
                    v-if="topic.can.delete"
                    title="Excluir este tópico?"
                    description="O tópico e as respostas saem da comunidade."
                    @confirm="remove"
                >
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-muted-foreground"
                        data-test="delete-topic-button"
                    >
                        <Trash2 /> Excluir
                    </Button>
                </ConfirmDialog>
                <ReportDialog
                    v-if="topic.can.report"
                    :action="report.form(topic.id)"
                    subject="tópico"
                />
                <template v-if="topic.can.moderate">
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-muted-foreground"
                        data-test="toggle-pin-button"
                        @click="togglePin"
                    >
                        <component :is="topic.pinned ? PinOff : Pin" />
                        {{ topic.pinned ? 'Desafixar' : 'Fixar no topo' }}
                    </Button>
                    <label
                        class="ml-auto flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        Mover para
                        <select
                            :value="topic.space.id"
                            class="h-8 rounded-md border border-input bg-transparent px-2 text-sm dark:bg-input/30"
                            data-test="move-topic-select"
                            @change="moveTo"
                        >
                            <option
                                v-for="space in spaces"
                                :key="space.id"
                                :value="space.id"
                            >
                                {{ space.name }}
                            </option>
                        </select>
                    </label>
                </template>
            </div>
        </article>

        <section
            v-if="solution"
            aria-labelledby="solution-heading"
            class="space-y-2"
        >
            <h2
                id="solution-heading"
                class="flex items-center gap-2 text-sm font-semibold text-primary"
            >
                <CircleCheck class="size-4" aria-hidden="true" />
                Resposta que resolveu
            </h2>
            <CommunityReplyItem
                :topic-id="topic.id"
                :reply="solution"
                :can-solve="topic.can.solve"
                highlighted
            />
        </section>

        <section aria-labelledby="replies-heading" class="space-y-3">
            <h2 id="replies-heading" class="font-semibold">
                {{ replies.total }}
                {{ replies.total === 1 ? 'resposta' : 'respostas' }}
            </h2>
            <CommunityReplyItem
                v-for="reply in replies.data"
                :key="reply.id"
                :topic-id="topic.id"
                :reply="reply"
                :can-solve="topic.can.solve"
            />
            <PaginationNav :page="replies" />
        </section>

        <Form
            v-bind="storeReply.form(topic.id)"
            reset-on-success
            class="space-y-2 rounded-xl border bg-card p-4 text-card-foreground"
            v-slot="{ errors, processing }"
        >
            <label for="reply-body" class="text-sm font-medium"
                >Sua resposta</label
            >
            <Textarea
                id="reply-body"
                name="body"
                rows="4"
                maxlength="3000"
                required
                placeholder="Ajude com uma resposta ou conte como resolveu"
                :readonly="processing"
                data-test="reply-body-input"
            />
            <InputError :message="errors.body" />
            <Button :disabled="processing" data-test="send-reply-button">
                Responder
            </Button>
        </Form>
    </div>
</template>
