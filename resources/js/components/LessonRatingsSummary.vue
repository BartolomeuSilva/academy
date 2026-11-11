<script setup lang="ts">
import { Star } from '@lucide/vue';
import { formatDateTime } from '@/lib/format';
import type { AdminLessonRatings } from '@/types';

defineProps<{
    ratings: AdminLessonRatings;
}>();
</script>

<template>
    <section
        class="space-y-4 rounded-xl border bg-card p-4 text-card-foreground"
        aria-labelledby="ratings-heading"
        data-test="lesson-ratings-summary"
    >
        <h2 id="ratings-heading" class="font-medium">Avaliações dos alunos</h2>

        <p v-if="ratings.count === 0" class="text-sm text-muted-foreground">
            Nenhuma avaliação ainda.
        </p>

        <template v-else>
            <div class="flex flex-wrap items-center gap-6">
                <div class="text-center">
                    <p class="text-3xl font-semibold tabular-nums">
                        {{ ratings.average?.toLocaleString('pt-BR') }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ ratings.count }}
                        {{ ratings.count === 1 ? 'avaliação' : 'avaliações' }}
                    </p>
                </div>
                <dl class="min-w-48 flex-1 space-y-1 text-xs">
                    <div
                        v-for="stars in [5, 4, 3, 2, 1]"
                        :key="stars"
                        class="flex items-center gap-2"
                    >
                        <dt class="flex w-8 items-center gap-0.5 tabular-nums">
                            {{ stars }}
                            <Star
                                class="size-3 fill-amber-400 text-amber-500"
                                aria-label="estrelas"
                            />
                        </dt>
                        <dd class="flex flex-1 items-center gap-2">
                            <span
                                class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"
                                aria-hidden="true"
                            >
                                <span
                                    class="block h-full bg-amber-400"
                                    :style="{
                                        width: `${((ratings.distribution[stars] ?? 0) / ratings.count) * 100}%`,
                                    }"
                                />
                            </span>
                            <span
                                class="w-6 text-right text-muted-foreground tabular-nums"
                                >{{ ratings.distribution[stars] ?? 0 }}</span
                            >
                        </dd>
                    </div>
                </dl>
            </div>

            <div v-if="ratings.feedback.length" class="space-y-3">
                <h3 class="text-sm font-medium">
                    O que os alunos escreveram
                    <span class="font-normal text-muted-foreground"
                        >(só a equipe vê)</span
                    >
                </h3>
                <ul class="divide-y">
                    <li
                        v-for="item in ratings.feedback"
                        :key="item.id"
                        class="space-y-1 py-3 first:pt-0"
                    >
                        <p class="flex flex-wrap items-center gap-x-2 text-xs">
                            <span class="font-medium">{{ item.author }}</span>
                            <span
                                class="flex items-center gap-0.5 text-muted-foreground"
                            >
                                {{ item.stars }}
                                <Star
                                    class="size-3 fill-amber-400 text-amber-500"
                                    aria-label="estrelas"
                                />
                            </span>
                            <span
                                v-if="item.updated_at"
                                class="text-muted-foreground"
                                >{{ formatDateTime(item.updated_at) }}</span
                            >
                        </p>
                        <p class="text-sm whitespace-pre-line">
                            {{ item.feedback }}
                        </p>
                    </li>
                </ul>
            </div>
        </template>
    </section>
</template>
