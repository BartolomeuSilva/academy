<script setup lang="ts">
import { Form, Head, Link, router, usePoll } from '@inertiajs/vue3';
import { Lock, Search, SendHorizontal, X } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, nextTick, onMounted, ref, useTemplateRef, watch } from 'vue';
import ChatMessage from '@/components/ChatMessage.vue';
import CommunityChannelsPanel from '@/components/CommunityChannelsPanel.vue';
import ImagePicker from '@/components/ImagePicker.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/community';
import { show } from '@/routes/community/channels';
import { store } from '@/routes/community/messages';
import type {
    CommunityChannel,
    CommunityChannelSummary,
    CommunityMessage,
} from '@/types';

/** How often new messages are fetched while the latest ones are shown. */
const POLL_MILLISECONDS = 8000;

const props = defineProps<{
    channels: CommunityChannelSummary[];
    channel: CommunityChannel;
    messages: CommunityMessage[];
    has_older: boolean;
    filters: { search: string; before: number | null; from: number | null };
    reactions: string[];
}>();

defineOptions({
    layout: (props: { channel: CommunityChannel }) => ({
        breadcrumbs: [
            { title: 'Comunidade', href: index() },
            { title: props.channel.name, href: show(props.channel.slug) },
        ],
    }),
});

const search = ref(props.filters.search);
const replyingTo = ref<CommunityMessage | null>(null);
const scroller = useTemplateRef('scroller');
const composer = useTemplateRef('composer');
const images = useTemplateRef('images');

/** Only the latest messages update live; history and search stay put. */
const isLive = computed(
    () => !props.filters.search && !props.filters.before && !props.filters.from,
);

const { start, stop } = usePoll(
    POLL_MILLISECONDS,
    { only: ['messages', 'channel', 'has_older'] },
    { autoStart: false },
);

watch(isLive, (live) => (live ? start() : stop()), { immediate: true });

/** Messages grouped by day, for the date separators. */
const days = computed(() => {
    const groups: { label: string; messages: CommunityMessage[] }[] = [];

    for (const message of props.messages) {
        const label = message.created_at
            ? new Date(message.created_at).toLocaleDateString('pt-BR', {
                  day: 'numeric',
                  month: 'long',
              })
            : '';
        const last = groups.at(-1);

        if (last?.label === label) {
            last.messages.push(message);
        } else {
            groups.push({ label, messages: [message] });
        }
    }

    return groups;
});

function scrollToBottom(): void {
    void nextTick(() => {
        if (scroller.value) {
            scroller.value.scrollTop = scroller.value.scrollHeight;
        }
    });
}

function isNearBottom(): boolean {
    const element = scroller.value;

    return (
        !element ||
        element.scrollHeight - element.scrollTop - element.clientHeight < 160
    );
}

onMounted(() => {
    if (props.filters.from) {
        document
            .getElementById(`mensagem-${props.filters.from}`)
            ?.scrollIntoView({ block: 'center' });
    } else if (!props.filters.before) {
        scrollToBottom();
    }
});

// New messages arriving while the student reads the end of the conversation keep it in view.
watch(
    () => props.messages.at(-1)?.id,
    () => {
        if (isLive.value && isNearBottom()) {
            scrollToBottom();
        }
    },
);

watchDebounced(
    search,
    (value) =>
        router.get(
            show.url(props.channel.slug),
            value.trim() ? { busca: value.trim() } : {},
            { preserveState: true, replace: true },
        ),
    { debounce: 300 },
);

function startReply(message: CommunityMessage): void {
    replyingTo.value = message;
    composer.value?.focus();
}

function submitOnEnter(event: KeyboardEvent): void {
    if (!event.shiftKey && !event.isComposing) {
        event.preventDefault();
        (event.target as HTMLTextAreaElement).form?.requestSubmit();
    }
}

function onSent(): void {
    replyingTo.value = null;
    images.value?.clear();
    scrollToBottom();
}
</script>

<template>
    <Head :title="`${channel.name} · Comunidade`" />

    <div
        class="flex min-h-0 flex-1 flex-col gap-4 p-4 lg:grid lg:h-[calc(100svh-4rem)] lg:flex-none lg:grid-cols-[16rem_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)] lg:gap-0 lg:p-0"
    >
        <CommunityChannelsPanel :channels="channels" :current="channel.slug" />

        <section
            class="flex h-[calc(100svh-15rem)] min-h-96 min-w-0 flex-col overflow-hidden rounded-xl border bg-card text-card-foreground lg:h-auto lg:min-h-0 lg:rounded-none lg:border-0"
            aria-labelledby="channel-heading"
        >
            <header
                class="flex flex-wrap items-center gap-3 border-b px-4 py-3"
            >
                <span
                    class="flex size-10 items-center justify-center rounded-lg bg-accent text-xl"
                    aria-hidden="true"
                    >{{ channel.emoji ?? '💬' }}</span
                >
                <div class="min-w-0 flex-1">
                    <h1 id="channel-heading" class="font-semibold">
                        {{ channel.name }}
                    </h1>
                    <p
                        v-if="channel.description"
                        class="truncate text-sm text-muted-foreground"
                    >
                        {{ channel.description }}
                    </p>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ channel.messages_count }}
                    {{
                        channel.messages_count === 1 ? 'mensagem' : 'mensagens'
                    }}
                </p>
                <div class="relative w-full">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        class="pl-9"
                        placeholder="Pesquisar neste canal"
                        aria-label="Pesquisar neste canal"
                        data-test="channel-search"
                    />
                </div>
            </header>

            <div
                ref="scroller"
                class="min-h-0 flex-1 overflow-y-auto px-2 py-3"
                role="log"
                aria-live="polite"
                aria-label="Mensagens"
                data-test="channel-messages"
            >
                <div
                    v-if="has_older || !isLive"
                    class="mb-3 flex justify-center gap-2"
                >
                    <Button
                        v-if="has_older && messages.length"
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <Link
                            :href="
                                show(channel.slug, {
                                    query: {
                                        antes: messages[0].id,
                                        ...(filters.search
                                            ? { busca: filters.search }
                                            : {}),
                                    },
                                })
                            "
                            data-test="older-messages-link"
                        >
                            Ver mensagens anteriores
                        </Link>
                    </Button>
                    <Button v-if="!isLive" variant="ghost" size="sm" as-child>
                        <Link :href="show(channel.slug)"
                            >Voltar às mais recentes</Link
                        >
                    </Button>
                </div>

                <p
                    v-if="messages.length === 0"
                    class="py-16 text-center text-sm text-muted-foreground"
                >
                    {{
                        filters.search
                            ? 'Nenhuma mensagem encontrada.'
                            : 'Nenhuma mensagem ainda. Comece a conversa!'
                    }}
                </p>

                <template v-for="day in days" :key="day.label">
                    <div class="my-3 flex justify-center">
                        <span
                            class="rounded-full border bg-background px-3 py-0.5 text-xs text-muted-foreground"
                            >{{ day.label }}</span
                        >
                    </div>
                    <ChatMessage
                        v-for="message in day.messages"
                        :key="message.id"
                        :channel-slug="channel.slug"
                        :message="message"
                        :reactions="reactions"
                        :can-reply="channel.can_post"
                        :highlighted="message.id === filters.from"
                        @reply="startReply"
                    />
                </template>
            </div>

            <Form
                v-if="channel.can_post"
                v-bind="store.form(channel.slug)"
                :options="{ preserveScroll: true, preserveState: true }"
                reset-on-success
                class="border-t p-3"
                v-slot="{ errors, processing }"
                @success="onSent"
            >
                <div
                    v-if="replyingTo"
                    class="mb-2 flex items-center gap-2 rounded-md border-l-2 border-primary bg-accent/70 px-2 py-1 text-xs"
                    data-test="replying-to"
                >
                    <span class="min-w-0 flex-1 truncate">
                        Respondendo a
                        <span class="font-semibold">{{
                            replyingTo.author.name
                        }}</span>
                    </span>
                    <input
                        type="hidden"
                        name="reply_to_id"
                        :value="replyingTo.id"
                    />
                    <button
                        type="button"
                        class="rounded p-0.5 hover:bg-background"
                        aria-label="Cancelar resposta"
                        @click="replyingTo = null"
                    >
                        <X class="size-3.5" />
                    </button>
                </div>
                <div
                    class="flex items-end gap-2 rounded-xl border bg-background p-1.5 focus-within:ring-2 focus-within:ring-ring/50"
                >
                    <ImagePicker ref="images" :errors="errors" compact />
                    <textarea
                        ref="composer"
                        name="body"
                        rows="1"
                        maxlength="3000"
                        :placeholder="`Mensagem em ${channel.name}`"
                        :aria-label="`Mensagem em ${channel.name}`"
                        :readonly="processing"
                        class="[field-sizing:content] max-h-40 min-h-9 flex-1 resize-none bg-transparent px-1 py-2 text-sm outline-none placeholder:text-muted-foreground"
                        data-test="message-input"
                        @keydown.enter="submitOnEnter"
                    />
                    <Button
                        size="icon"
                        :disabled="processing"
                        aria-label="Enviar"
                        data-test="send-message-button"
                    >
                        <SendHorizontal />
                    </Button>
                </div>
                <InputError :message="errors.body" class="mt-1" />
                <p class="mt-1 text-xs text-muted-foreground">
                    Imagens JPG, PNG ou WebP até 5 MB · Enter envia, Shift+Enter
                    quebra a linha
                </p>
            </Form>
            <p
                v-else
                class="flex items-center justify-center gap-2 border-t p-4 text-center text-sm text-muted-foreground"
                data-test="staff-only-notice"
            >
                <Lock class="size-4" aria-hidden="true" />
                Somente a equipe publica em {{ channel.name }}. Converse nos
                outros canais!
            </p>
        </section>
    </div>
</template>
