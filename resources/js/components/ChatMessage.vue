<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { Pencil, Reply, SmilePlus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import CommunityAuthorName from '@/components/CommunityAuthorName.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import ImageGallery from '@/components/ImageGallery.vue';
import InputError from '@/components/InputError.vue';
import ReportDialog from '@/components/ReportDialog.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import { destroy, report, update } from '@/routes/community/messages';
import { store as react } from '@/routes/community/messages/reactions';
import type { CommunityMessage } from '@/types';

const props = defineProps<{
    channelSlug: string;
    message: CommunityMessage;
    reactions: string[];
    canReply: boolean;
    highlighted?: boolean;
}>();

const emit = defineEmits<{
    reply: [message: CommunityMessage];
}>();

const editing = ref(false);

const visitOptions = { preserveScroll: true, preserveState: true };

function toggleReaction(emoji: string): void {
    router.post(
        react.url([props.channelSlug, props.message.id]),
        { emoji },
        { ...visitOptions, only: ['messages'] },
    );
}

function remove(close: () => void): void {
    router.delete(destroy.url([props.channelSlug, props.message.id]), {
        ...visitOptions,
        onSuccess: close,
    });
}

function time(iso: string): string {
    return new Date(iso).toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <article
        :id="`mensagem-${message.id}`"
        class="group relative flex scroll-mt-24 gap-3 rounded-lg px-2 py-2 hover:bg-muted/50"
        :class="highlighted ? 'bg-accent/60 ring-1 ring-primary/30' : ''"
        data-test="chat-message"
    >
        <Avatar class="size-9 shrink-0 overflow-hidden">
            <AvatarImage
                v-if="message.author.avatar"
                :src="message.author.avatar"
                alt=""
                class="object-cover"
            />
            <AvatarFallback class="text-xs">{{
                getInitials(message.author.name)
            }}</AvatarFallback>
        </Avatar>

        <div class="min-w-0 flex-1 space-y-1">
            <p class="flex flex-wrap items-center gap-x-2 text-xs">
                <CommunityAuthorName :author="message.author" />
                <time
                    v-if="message.created_at"
                    :datetime="message.created_at"
                    class="text-muted-foreground"
                    >{{ time(message.created_at) }}</time
                >
                <span v-if="message.edited" class="text-muted-foreground"
                    >(editada)</span
                >
            </p>

            <a
                v-if="message.reply_to"
                :href="`#mensagem-${message.reply_to.id}`"
                class="block max-w-full truncate rounded-r-md border-l-2 border-primary bg-accent/70 px-2 py-1 text-xs text-accent-foreground"
                data-test="quoted-message"
            >
                <span class="font-semibold">{{ message.reply_to.author }}</span>
                {{ message.reply_to.excerpt }}
            </a>

            <Form
                v-if="editing"
                v-bind="update.form([channelSlug, message.id])"
                :options="visitOptions"
                class="space-y-2"
                v-slot="{ errors, processing }"
                @success="editing = false"
            >
                <Textarea
                    name="body"
                    :default-value="message.body ?? ''"
                    maxlength="3000"
                    required
                    aria-label="Editar mensagem"
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
                <!-- eslint-disable-next-line vue/no-v-html -- sanitized Markdown rendered by the server -->
                <div class="rich-text break-words" v-html="message.body_html" />
                <ImageGallery :images="message.images" />
            </template>

            <div class="flex flex-wrap items-center gap-1 pt-1">
                <button
                    v-for="reaction in message.reactions"
                    :key="reaction.emoji"
                    type="button"
                    class="flex h-7 items-center gap-1 rounded-full border px-2 text-xs hover:bg-accent aria-pressed:border-primary aria-pressed:bg-accent aria-pressed:text-accent-foreground"
                    :aria-pressed="reaction.mine"
                    :aria-label="`${reaction.emoji} ${reaction.count} — ${reaction.mine ? 'remover sua reação' : 'reagir'}`"
                    data-test="reaction-chip"
                    @click="toggleReaction(reaction.emoji)"
                >
                    <span aria-hidden="true">{{ reaction.emoji }}</span>
                    <span class="tabular-nums" aria-hidden="true">{{
                        reaction.count
                    }}</span>
                </button>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="flex h-7 items-center gap-1 rounded-full border px-2 text-xs text-muted-foreground hover:bg-accent"
                            aria-label="Reagir"
                            data-test="add-reaction-button"
                        >
                            <SmilePlus class="size-3.5" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" class="flex gap-1 p-1">
                        <button
                            v-for="emoji in reactions"
                            :key="emoji"
                            type="button"
                            class="flex size-8 items-center justify-center rounded-md text-lg hover:bg-accent"
                            :aria-label="`Reagir com ${emoji}`"
                            @click="toggleReaction(emoji)"
                        >
                            {{ emoji }}
                        </button>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <div
            v-if="!editing"
            class="absolute -top-3 right-2 flex gap-0.5 rounded-lg border bg-card p-0.5 opacity-0 shadow-sm transition-opacity group-hover:opacity-100 focus-within:opacity-100"
        >
            <Button
                v-if="canReply"
                variant="ghost"
                size="icon"
                class="size-7"
                aria-label="Responder"
                data-test="reply-message-button"
                @click="emit('reply', message)"
            >
                <Reply />
            </Button>
            <Button
                v-if="message.can.update"
                variant="ghost"
                size="icon"
                class="size-7"
                aria-label="Editar"
                data-test="edit-message-button"
                @click="editing = true"
            >
                <Pencil />
            </Button>
            <ConfirmDialog
                v-if="message.can.delete"
                title="Excluir esta mensagem?"
                description="A mensagem sai da conversa."
                @confirm="remove"
            >
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7"
                    aria-label="Excluir"
                    data-test="delete-message-button"
                >
                    <Trash2 />
                </Button>
            </ConfirmDialog>
            <ReportDialog
                v-if="message.can.report"
                :action="report.form([channelSlug, message.id])"
                subject="mensagem"
                compact
            />
        </div>
    </article>
</template>
