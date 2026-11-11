<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { GraduationCap, Hash, LayoutList, Megaphone } from '@lucide/vue';
import { index, lessons } from '@/routes/community';
import type { CommunitySpace } from '@/types';

defineProps<{
    spaces: CommunitySpace[];
    /** Slug of the space being shown, `null` for every space, `aulas` for lesson discussions. */
    current: string | null;
}>();

const itemClass =
    'flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm whitespace-nowrap text-muted-foreground hover:bg-accent hover:text-accent-foreground aria-[current=page]:bg-accent aria-[current=page]:font-semibold aria-[current=page]:text-accent-foreground lg:shrink';
</script>

<template>
    <nav
        aria-label="Espaços da comunidade"
        class="-mx-4 flex [scrollbar-width:none] gap-1 overflow-x-auto px-4 pb-1 lg:sticky lg:top-4 lg:mx-0 lg:flex-col lg:overflow-visible lg:rounded-xl lg:border lg:bg-card lg:p-2 [&::-webkit-scrollbar]:hidden"
    >
        <Link
            :href="index()"
            :aria-current="current === null ? 'page' : undefined"
            :class="itemClass"
        >
            <LayoutList class="size-4 shrink-0" aria-hidden="true" />
            Todos os tópicos
        </Link>
        <Link
            v-for="space in spaces"
            :key="space.id"
            :href="index({ query: { espaco: space.slug } })"
            :aria-current="current === space.slug ? 'page' : undefined"
            :class="itemClass"
            data-test="community-space-link"
        >
            <Megaphone
                v-if="!space.can_post"
                class="size-4 shrink-0"
                aria-hidden="true"
            />
            <Hash v-else class="size-4 shrink-0" aria-hidden="true" />
            <span class="lg:min-w-0 lg:flex-1 lg:truncate">{{
                space.name
            }}</span>
            <span class="text-xs tabular-nums">{{ space.topics_count }}</span>
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
            <GraduationCap class="size-4 shrink-0" aria-hidden="true" />
            Discussões das aulas
        </Link>
    </nav>
</template>
