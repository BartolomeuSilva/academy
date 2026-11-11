<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { GraduationCap, MessageCircle } from '@lucide/vue';
import CommunityAuthorName from '@/components/CommunityAuthorName.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import { formatRelativeTime } from '@/lib/format';
import { index, lessons } from '@/routes/community';
import { show as showLesson } from '@/routes/lessons';
import type { LessonDiscussion, Paginated } from '@/types';

defineProps<{
    discussions: Paginated<LessonDiscussion>;
    filters: { filter: 'recentes' | 'sem-resposta' };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Comunidade', href: index() },
            { title: 'Dúvidas das aulas', href: lessons() },
        ],
    },
});
</script>

<template>
    <Head title="Dúvidas das aulas" />

    <div class="flex max-w-4xl flex-1 flex-col gap-6 p-4">
        <header class="space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight">
                Dúvidas das aulas
            </h1>
            <p class="text-muted-foreground">
                As dúvidas e comentários mais recentes de todas as aulas.
            </p>
        </header>

        <nav
            class="flex w-fit gap-1 rounded-lg bg-muted p-1"
            aria-label="Filtrar discussões"
        >
            <Link
                v-for="option in [
                    { value: 'recentes', label: 'Recentes', query: {} },
                    {
                        value: 'sem-resposta',
                        label: 'Sem resposta',
                        query: { filtro: 'sem-resposta' },
                    },
                ]"
                :key="option.value"
                :href="lessons({ query: option.query })"
                :aria-current="
                    filters.filter === option.value ? 'page' : undefined
                "
                class="rounded-md px-3 py-1.5 text-sm text-muted-foreground aria-[current=page]:bg-background aria-[current=page]:font-medium aria-[current=page]:text-foreground aria-[current=page]:shadow-sm"
            >
                {{ option.label }}
            </Link>
        </nav>

        <div
            v-if="discussions.data.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed bg-card p-10 text-center"
        >
            <GraduationCap class="size-10 text-muted-foreground" />
            <p class="font-medium">Nenhuma discussão por aqui</p>
        </div>

        <ul v-else class="space-y-3">
            <li
                v-for="discussion in discussions.data"
                :key="discussion.id"
                class="relative rounded-xl border bg-card p-4 text-card-foreground transition-colors focus-within:ring-2 focus-within:ring-ring hover:border-primary/40"
                data-test="lesson-discussion"
            >
                <p class="mb-1 text-xs text-muted-foreground">
                    {{ discussion.course.title }} ·
                    <Link
                        :href="
                            showLesson(
                                [
                                    discussion.course.slug,
                                    discussion.lesson.slug,
                                ],
                                { query: { aba: 'comentarios' } },
                            )
                        "
                        class="font-medium text-foreground outline-none after:absolute after:inset-0"
                    >
                        {{ discussion.lesson.title }}
                    </Link>
                </p>
                <p class="line-clamp-3 text-sm">{{ discussion.excerpt }}</p>
                <div
                    class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                >
                    <CommunityAuthorName :author="discussion.author" />
                    <span v-if="discussion.created_at">{{
                        formatRelativeTime(discussion.created_at)
                    }}</span>
                    <span class="ml-auto flex items-center gap-1">
                        <MessageCircle class="size-3.5" aria-hidden="true" />
                        {{ discussion.replies_count }}
                        {{
                            discussion.replies_count === 1
                                ? 'resposta'
                                : 'respostas'
                        }}
                    </span>
                </div>
            </li>
        </ul>

        <PaginationNav :page="discussions" />
    </div>
</template>
