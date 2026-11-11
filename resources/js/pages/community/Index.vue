<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CircleCheck,
    MessageCircle,
    MessagesSquare,
    Pin,
    Plus,
    Search,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import CommunityAuthorName from '@/components/CommunityAuthorName.vue';
import CommunitySpacesNav from '@/components/CommunitySpacesNav.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatRelativeTime } from '@/lib/format';
import { index } from '@/routes/community';
import { create, show } from '@/routes/community/topics';
import type {
    CommunitySpace,
    CommunityTopicFilter,
    CommunityTopicSummary,
    Paginated,
} from '@/types';

const FILTERS: { value: CommunityTopicFilter; label: string }[] = [
    { value: 'recentes', label: 'Recentes' },
    { value: 'sem-resposta', label: 'Sem resposta' },
    { value: 'meus', label: 'Meus tópicos' },
];

const props = defineProps<{
    spaces: CommunitySpace[];
    topics: Paginated<CommunityTopicSummary>;
    filters: {
        space: string | null;
        filter: CommunityTopicFilter;
        search: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Comunidade', href: index() }],
    },
});

const search = ref(props.filters.search);
const currentSpace = computed(() =>
    props.spaces.find((space) => space.slug === props.filters.space),
);

function query(changes: Partial<typeof props.filters>): Record<string, string> {
    const next = { ...props.filters, search: search.value.trim(), ...changes };

    return {
        ...(next.space ? { espaco: next.space } : {}),
        ...(next.filter !== 'recentes' ? { filtro: next.filter } : {}),
        ...(next.search ? { busca: next.search } : {}),
    };
}

watchDebounced(
    search,
    () =>
        router.get(index.url(), query({}), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }),
    { debounce: 300 },
);
</script>

<template>
    <Head title="Comunidade" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ currentSpace?.name ?? 'Comunidade' }}
                </h1>
                <p class="text-muted-foreground">
                    {{
                        currentSpace?.description ??
                        'Tire dúvidas, compartilhe o que aprendeu e ajude outros alunos.'
                    }}
                </p>
            </div>
            <Button
                v-if="currentSpace?.can_post ?? true"
                as-child
                data-test="new-topic-link"
            >
                <Link
                    :href="
                        create({
                            query: filters.space
                                ? { espaco: filters.space }
                                : {},
                        })
                    "
                >
                    <Plus /> Novo tópico
                </Link>
            </Button>
        </header>

        <div class="grid items-start gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">
            <CommunitySpacesNav :spaces="spaces" :current="filters.space" />

            <section class="min-w-0 space-y-4" aria-label="Tópicos">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <nav
                        class="flex gap-1 rounded-lg bg-muted p-1"
                        aria-label="Filtrar tópicos"
                    >
                        <Link
                            v-for="option in FILTERS"
                            :key="option.value"
                            :href="
                                index({
                                    query: query({ filter: option.value }),
                                })
                            "
                            :aria-current="
                                filters.filter === option.value
                                    ? 'page'
                                    : undefined
                            "
                            preserve-scroll
                            class="flex-1 rounded-md px-3 py-1.5 text-center text-sm whitespace-nowrap text-muted-foreground aria-[current=page]:bg-background aria-[current=page]:font-medium aria-[current=page]:text-foreground aria-[current=page]:shadow-sm"
                        >
                            {{ option.label }}
                        </Link>
                    </nav>
                    <div class="relative sm:w-64">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            class="bg-card pl-9"
                            placeholder="Buscar na comunidade"
                            aria-label="Buscar na comunidade"
                            data-test="community-search"
                        />
                    </div>
                </div>

                <div
                    v-if="topics.data.length === 0"
                    class="flex flex-col items-center gap-3 rounded-xl border border-dashed bg-card p-10 text-center"
                >
                    <MessagesSquare class="size-10 text-muted-foreground" />
                    <p class="font-medium">
                        {{
                            filters.search || filters.filter !== 'recentes'
                                ? 'Nenhum tópico encontrado'
                                : 'Nenhum tópico ainda'
                        }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{
                            filters.search || filters.filter !== 'recentes'
                                ? 'Tente outra busca ou outro filtro.'
                                : 'Comece a conversa com uma dúvida ou uma dica.'
                        }}
                    </p>
                </div>

                <ul v-else class="space-y-3">
                    <li
                        v-for="topic in topics.data"
                        :key="topic.id"
                        class="relative rounded-xl border bg-card p-4 text-card-foreground transition-colors focus-within:ring-2 focus-within:ring-ring hover:border-primary/40"
                        data-test="community-topic"
                    >
                        <div
                            class="mb-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                        >
                            <span
                                v-if="topic.pinned"
                                class="flex items-center gap-1 font-medium text-primary"
                            >
                                <Pin class="size-3.5" aria-hidden="true" />
                                Fixado
                            </span>
                            <span
                                v-if="!filters.space"
                                class="rounded-full bg-accent px-2 py-0.5 font-medium text-accent-foreground"
                                >{{ topic.space.name }}</span
                            >
                            <span
                                v-if="topic.solved"
                                class="flex items-center gap-1 font-medium text-primary"
                            >
                                <CircleCheck
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                Resolvido
                            </span>
                        </div>
                        <h2 class="font-semibold">
                            <Link
                                :href="show(topic.id)"
                                class="outline-none after:absolute after:inset-0"
                            >
                                {{ topic.title }}
                            </Link>
                        </h2>
                        <p
                            class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                        >
                            {{ topic.excerpt }}
                        </p>
                        <div
                            class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                        >
                            <CommunityAuthorName :author="topic.author" />
                            <span>{{
                                formatRelativeTime(topic.last_activity_at)
                            }}</span>
                            <span class="ml-auto flex items-center gap-1">
                                <MessageCircle
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                {{ topic.replies_count }}
                                {{
                                    topic.replies_count === 1
                                        ? 'resposta'
                                        : 'respostas'
                                }}
                            </span>
                        </div>
                    </li>
                </ul>

                <PaginationNav :page="topics" />
            </section>
        </div>
    </div>
</template>
