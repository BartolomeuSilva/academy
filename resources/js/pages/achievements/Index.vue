<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    GraduationCap,
    Heart,
    Lock,
    MessagesSquare,
    PlayCircle,
    Star,
} from '@lucide/vue';
import { index } from '@/routes/achievements';
import type { AchievementGroup } from '@/types';

const ICONS = {
    lessons: PlayCircle,
    courses: GraduationCap,
    ratings: Star,
    messages: MessagesSquare,
    reactions: Heart,
};

defineProps<{
    groups: AchievementGroup[];
    earned: number;
    total: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Conquistas', href: index() }],
    },
});
</script>

<template>
    <Head title="Conquistas" />

    <div class="flex flex-1 flex-col gap-8 p-4">
        <header
            class="flex flex-col gap-4 rounded-2xl bg-hero p-6 text-white sm:flex-row sm:items-center sm:justify-between sm:p-8"
        >
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Conquistas
                </h1>
                <p class="text-white/80">
                    Medalhas por aprender, avaliar as aulas e ajudar outros
                    alunos.
                </p>
            </div>
            <div class="min-w-48 space-y-2" data-test="achievements-summary">
                <p class="text-sm text-white/80">
                    <span class="text-3xl font-bold text-white tabular-nums">{{
                        earned
                    }}</span>
                    de {{ total }} medalhas
                </p>
                <div
                    class="h-2 overflow-hidden rounded-full bg-white/20"
                    role="progressbar"
                    aria-label="Medalhas conquistadas"
                    :aria-valuenow="earned"
                    aria-valuemin="0"
                    :aria-valuemax="total"
                >
                    <div
                        class="h-full rounded-full bg-brand"
                        :style="{ width: `${(earned / total) * 100}%` }"
                    />
                </div>
            </div>
        </header>

        <section
            v-for="group in groups"
            :key="group.key"
            :aria-labelledby="`group-${group.key}`"
            class="space-y-3"
        >
            <h2 :id="`group-${group.key}`" class="text-lg font-semibold">
                {{ group.title }}
            </h2>
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <li
                    v-for="achievement in group.achievements"
                    :key="achievement.key"
                    class="flex flex-col items-center gap-3 rounded-xl border bg-card p-4 text-center text-card-foreground"
                    :class="achievement.earned ? 'border-primary/40' : ''"
                    data-test="achievement"
                    :data-earned="achievement.earned"
                >
                    <span
                        class="relative flex size-16 items-center justify-center rounded-full"
                        :class="
                            achievement.earned
                                ? 'bg-hero text-white shadow-md ring-4 ring-brand/40'
                                : 'bg-muted text-muted-foreground'
                        "
                        aria-hidden="true"
                    >
                        <component :is="ICONS[group.key]" class="size-7" />
                        <span
                            v-if="!achievement.earned"
                            class="absolute -right-1 -bottom-1 flex size-6 items-center justify-center rounded-full border bg-card"
                        >
                            <Lock class="size-3" />
                        </span>
                    </span>
                    <div class="space-y-1">
                        <p class="font-semibold">{{ achievement.title }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ achievement.description }}
                        </p>
                    </div>
                    <p
                        v-if="achievement.earned"
                        class="mt-auto text-xs font-semibold text-primary"
                    >
                        Conquistada
                    </p>
                    <div v-else class="mt-auto w-full space-y-1">
                        <div
                            class="h-1.5 overflow-hidden rounded-full bg-muted"
                            aria-hidden="true"
                        >
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{
                                    width: `${(achievement.progress / achievement.target) * 100}%`,
                                }"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground tabular-nums">
                            {{ achievement.progress }} de
                            {{ achievement.target }}
                        </p>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
