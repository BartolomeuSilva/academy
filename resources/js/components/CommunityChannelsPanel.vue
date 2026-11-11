<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { GraduationCap, Lock, ShieldCheck } from '@lucide/vue';
import AvatarUploader from '@/components/AvatarUploader.vue';
import { lessons } from '@/routes/community';
import { show } from '@/routes/community/channels';
import type { CommunityChannelSummary } from '@/types';

defineProps<{
    channels: CommunityChannelSummary[];
    /** Slug of the open channel, or `aulas` for the lesson discussions. */
    current: string;
}>();

const itemClass =
    'flex shrink-0 items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm whitespace-nowrap text-muted-foreground hover:bg-accent hover:text-accent-foreground aria-[current=page]:bg-accent aria-[current=page]:font-semibold aria-[current=page]:text-accent-foreground';
</script>

<template>
    <aside
        class="flex min-w-0 flex-col gap-3 lg:h-full lg:border-r lg:p-3"
        aria-label="Canais da comunidade"
    >
        <div class="hidden rounded-xl border bg-card p-2.5 lg:block">
            <AvatarUploader compact />
        </div>

        <div class="hidden px-1 lg:block">
            <p class="font-semibold">Canais</p>
            <p class="text-xs text-muted-foreground">
                {{ channels.length }}
                {{ channels.length === 1 ? 'canal' : 'canais' }}
            </p>
        </div>

        <nav
            class="-mx-4 flex [scrollbar-width:none] gap-1 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0 [&::-webkit-scrollbar]:hidden"
        >
            <Link
                v-for="channel in channels"
                :key="channel.id"
                :href="show(channel.slug)"
                :aria-current="current === channel.slug ? 'page' : undefined"
                :class="itemClass"
                data-test="community-channel-link"
            >
                <span
                    class="flex size-7 shrink-0 items-center justify-center rounded-md bg-muted text-base"
                    aria-hidden="true"
                    >{{ channel.emoji ?? '💬' }}</span
                >
                <span class="lg:min-w-0 lg:flex-1 lg:truncate">{{
                    channel.name
                }}</span>
                <Lock
                    v-if="channel.staff_only"
                    class="size-3.5 shrink-0"
                    aria-label="Só a equipe publica"
                />
            </Link>
            <span
                class="hidden h-px bg-border lg:my-1 lg:block"
                aria-hidden="true"
            />
            <Link
                :href="lessons()"
                :aria-current="current === 'aulas' ? 'page' : undefined"
                :class="itemClass"
                data-test="lesson-discussions-link"
            >
                <span
                    class="flex size-7 shrink-0 items-center justify-center rounded-md bg-muted"
                    aria-hidden="true"
                    ><GraduationCap class="size-4"
                /></span>
                Dúvidas das aulas
            </Link>
        </nav>

        <div
            class="mt-auto hidden space-y-1 rounded-xl border bg-card p-3 text-xs lg:block"
        >
            <p class="flex items-center gap-1.5 font-semibold">
                <ShieldCheck class="size-4 text-primary" aria-hidden="true" />
                Combinados
            </p>
            <p class="text-muted-foreground">
                Respeito sempre. Sem divulgação, telefone, e-mail ou @ de rede
                social. Dúvida sobre um produto? A equipe responde aqui.
                Mensagens que quebram os combinados são removidas.
            </p>
        </div>
    </aside>
</template>
