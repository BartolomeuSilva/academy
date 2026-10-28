<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CircleCheck, CirclePlay } from '@lucide/vue';
import { formatDuration } from '@/lib/format';
import { show as showLesson } from '@/routes/lessons';
import type { OutlineModule } from '@/types';

defineProps<{
    courseSlug: string;
    modules: OutlineModule[];
    currentLessonId?: number;
}>();
</script>

<template>
    <nav aria-label="Conteúdo do curso" class="space-y-4">
        <section v-for="module in modules" :key="module.id" class="space-y-1">
            <h3 class="px-2 text-sm font-semibold">{{ module.title }}</h3>
            <ol class="space-y-0.5">
                <li v-for="lesson in module.lessons" :key="lesson.id">
                    <Link
                        :href="showLesson([courseSlug, lesson.slug])"
                        :aria-current="
                            lesson.id === currentLessonId ? 'page' : undefined
                        "
                        class="flex items-center gap-3 rounded-md px-2 py-2 text-sm hover:bg-accent hover:text-accent-foreground aria-[current=page]:bg-accent aria-[current=page]:font-medium aria-[current=page]:text-accent-foreground"
                        data-test="outline-lesson"
                    >
                        <CircleCheck
                            v-if="lesson.completed"
                            class="size-4 shrink-0 text-primary"
                            aria-label="Concluída"
                        />
                        <CirclePlay
                            v-else
                            class="size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span class="min-w-0 flex-1">{{ lesson.title }}</span>
                        <span
                            v-if="lesson.duration_seconds"
                            class="shrink-0 text-xs text-muted-foreground tabular-nums"
                        >
                            {{ formatDuration(lesson.duration_seconds) }}
                        </span>
                    </Link>
                </li>
            </ol>
        </section>
    </nav>
</template>
