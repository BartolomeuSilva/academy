<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check, ChevronDown, Play } from '@lucide/vue';
import { ref } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { formatDuration, formatTotalDuration } from '@/lib/format';
import { show as showLesson } from '@/routes/lessons';
import type { OutlineModule } from '@/types';

const RING_RADIUS = 15;
const RING_LENGTH = 2 * Math.PI * RING_RADIUS;

const props = defineProps<{
    courseSlug: string;
    modules: OutlineModule[];
    currentLessonId?: number;
}>();

const openModules = ref<Record<number, boolean>>(
    Object.fromEntries(
        props.modules.map((module) => [
            module.id,
            props.modules.length === 1 ||
                module.lessons.some(
                    (lesson) => lesson.id === props.currentLessonId,
                ),
        ]),
    ),
);

function completion(module: OutlineModule): number {
    const completed = module.lessons.filter((lesson) => lesson.completed);

    return module.lessons.length
        ? Math.round((completed.length / module.lessons.length) * 100)
        : 0;
}

function moduleDuration(module: OutlineModule): string {
    return formatTotalDuration(
        module.lessons.reduce(
            (total, lesson) => total + (lesson.duration_seconds ?? 0),
            0,
        ),
    );
}
</script>

<template>
    <nav aria-label="Conteúdo do curso" class="divide-y">
        <Collapsible
            v-for="(module, moduleIndex) in modules"
            :key="module.id"
            v-model:open="openModules[module.id]"
        >
            <CollapsibleTrigger
                class="group flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-accent/60 focus-visible:bg-accent focus-visible:outline-none"
            >
                <div class="relative size-10 shrink-0">
                    <svg
                        viewBox="0 0 36 36"
                        class="size-full -rotate-90"
                        aria-hidden="true"
                    >
                        <circle
                            cx="18"
                            cy="18"
                            :r="RING_RADIUS"
                            fill="none"
                            stroke-width="3"
                            class="stroke-primary/15"
                        />
                        <circle
                            cx="18"
                            cy="18"
                            :r="RING_RADIUS"
                            fill="none"
                            stroke-width="3"
                            stroke-linecap="round"
                            class="stroke-primary transition-[stroke-dashoffset]"
                            :stroke-dasharray="RING_LENGTH"
                            :stroke-dashoffset="
                                RING_LENGTH * (1 - completion(module) / 100)
                            "
                        />
                    </svg>
                    <span
                        class="absolute inset-0 flex items-center justify-center text-[10px] font-semibold tabular-nums"
                    >
                        {{ completion(module) }}%
                    </span>
                </div>
                <div class="min-w-0 flex-1">
                    <p
                        class="text-[11px] font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Módulo {{ moduleIndex + 1 }}
                    </p>
                    <p class="text-sm font-semibold">{{ module.title }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ module.lessons.length }}
                        {{ module.lessons.length === 1 ? 'aula' : 'aulas' }}
                        · {{ moduleDuration(module) }}
                    </p>
                </div>
                <ChevronDown
                    class="size-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                    aria-hidden="true"
                />
            </CollapsibleTrigger>

            <CollapsibleContent>
                <ol class="relative pb-2">
                    <li
                        v-for="(lesson, lessonIndex) in module.lessons"
                        :key="lesson.id"
                        class="relative"
                    >
                        <span
                            v-if="lessonIndex < module.lessons.length - 1"
                            class="absolute top-7 bottom-0 left-[1.6rem] w-px bg-border"
                            aria-hidden="true"
                        />
                        <Link
                            :href="showLesson([courseSlug, lesson.slug])"
                            :aria-current="
                                lesson.id === currentLessonId
                                    ? 'page'
                                    : undefined
                            "
                            preserve-scroll
                            class="relative flex items-start gap-3 px-4 py-2 text-sm hover:bg-accent/60 aria-[current=page]:bg-accent aria-[current=page]:font-semibold aria-[current=page]:text-accent-foreground"
                            data-test="outline-lesson"
                        >
                            <span
                                v-if="lesson.completed"
                                class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground"
                            >
                                <Check class="size-3" aria-label="Concluída" />
                            </span>
                            <span
                                v-else-if="lesson.id === currentLessonId"
                                class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border-2 border-primary bg-background text-primary"
                            >
                                <Play
                                    class="size-2.5 fill-current"
                                    aria-hidden="true"
                                />
                            </span>
                            <span
                                v-else
                                class="mt-0.5 size-5 shrink-0 rounded-full border-2 border-muted-foreground/40 bg-background"
                                aria-hidden="true"
                            />
                            <span class="min-w-0 flex-1">
                                <span class="text-muted-foreground tabular-nums"
                                    >{{ lessonIndex + 1 }}.</span
                                >
                                {{ lesson.title }}
                            </span>
                            <span
                                v-if="lesson.duration_seconds"
                                class="shrink-0 text-xs font-normal text-muted-foreground tabular-nums"
                            >
                                {{ formatDuration(lesson.duration_seconds) }}
                            </span>
                        </Link>
                    </li>
                </ol>
            </CollapsibleContent>
        </Collapsible>
    </nav>
</template>
