<script setup lang="ts">
import { BadgeCheck } from '@lucide/vue';
import type { CommunityAuthor } from '@/types';

defineProps<{
    author: CommunityAuthor;
}>();
</script>

<template>
    <span class="inline-flex items-center gap-1.5">
        <span class="font-medium text-foreground">{{ author.name }}</span>
        <span
            v-if="author.is_team"
            class="inline-flex items-center gap-0.5 rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-semibold tracking-wide text-primary-foreground uppercase"
            data-test="team-badge"
        >
            <BadgeCheck class="size-3" aria-hidden="true" />
            Equipe
        </span>
    </span>
</template>
