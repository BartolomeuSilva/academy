<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown, CircleCheck, CirclePlay, Circle } from '@lucide/vue';
import { ref } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { formatDuration, formatTotalDuration } from '@/lib/format';
import { show as showLesson } from '@/routes/lessons';
import type { OutlineModule } from '@/types';

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

function moduleSummary(module: OutlineModule): string {
    const completed = module.lessons.filter((lesson) => lesson.completed);
    const duration = module.lessons.reduce(
        (total, lesson) => total + (lesson.duration_seconds ?? 0),
        0,
    );

    return `${completed.length}/${module.lessons.length} · ${formatTotalDuration(duration)}`;
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
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold">
                        <span class="text-muted-foreground"
                            >{{ moduleIndex + 1 }}.</span
                        >
                        {{ module.title }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ moduleSummary(module) }}
                    </p>
                </div>
                <ChevronDown
                    class="size-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                    aria-hidden="true"
                />
            </CollapsibleTrigger>

            <CollapsibleContent>
                <ol class="pb-2">
                    <li v-for="lesson in module.lessons" :key="lesson.id">
                        <Link
                            :href="showLesson([courseSlug, lesson.slug])"
                            :aria-current="
                                lesson.id === currentLessonId
                                    ? 'page'
                                    : undefined
                            "
                            preserve-scroll
                            class="relative flex items-start gap-3 px-4 py-2.5 text-sm hover:bg-accent/60 aria-[current=page]:bg-accent aria-[current=page]:font-medium aria-[current=page]:text-accent-foreground aria-[current=page]:before:absolute aria-[current=page]:before:inset-y-0 aria-[current=page]:before:left-0 aria-[current=page]:before:w-1 aria-[current=page]:before:bg-primary"
                            data-test="outline-lesson"
                        >
                            <CircleCheck
                                v-if="lesson.completed"
                                class="mt-0.5 size-4 shrink-0 text-primary"
                                aria-label="Concluída"
                            />
                            <CirclePlay
                                v-else-if="lesson.id === currentLessonId"
                                class="mt-0.5 size-4 shrink-0 text-primary"
                                aria-hidden="true"
                            />
                            <Circle
                                v-else
                                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <span class="min-w-0 flex-1">{{
                                lesson.title
                            }}</span>
                            <span
                                v-if="lesson.duration_seconds"
                                class="shrink-0 text-xs text-muted-foreground tabular-nums"
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
