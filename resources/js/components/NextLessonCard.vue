<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CirclePlay } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { formatTotalDuration } from '@/lib/format';
import { show } from '@/routes/lessons';
import type { NextLesson } from '@/types';

defineProps<{
    nextLesson: NextLesson;
}>();
</script>

<template>
    <section
        class="relative overflow-hidden rounded-2xl bg-neutral-900 text-white"
        aria-labelledby="next-lesson-heading"
        data-test="next-lesson"
    >
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgb(255_255_255_/_0.14),_transparent_55%)]"
            aria-hidden="true"
        />
        <div
            class="relative flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8"
        >
            <div class="min-w-0 space-y-3">
                <p
                    class="text-xs font-semibold tracking-widest text-white/70 uppercase"
                >
                    {{
                        nextLesson.started
                            ? 'Sua próxima aula'
                            : 'Comece por aqui'
                    }}
                </p>
                <div class="border-l-2 border-white/40 pl-3">
                    <p class="text-sm text-white/70">
                        {{ nextLesson.course.title }}
                    </p>
                    <h2
                        id="next-lesson-heading"
                        class="text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        {{ nextLesson.lesson.title }}
                    </h2>
                    <p class="text-sm text-white/70">
                        Aula {{ nextLesson.lesson.number
                        }}<template v-if="nextLesson.lesson.duration_seconds">
                            ·
                            {{
                                formatTotalDuration(
                                    nextLesson.lesson.duration_seconds,
                                )
                            }}</template
                        >
                    </p>
                </div>
                <p class="text-sm text-white/70">
                    {{
                        nextLesson.remaining_lessons === 1
                            ? 'Falta 1 aula'
                            : `Faltam ${nextLesson.remaining_lessons} aulas`
                    }}
                    neste curso<template v-if="nextLesson.remaining_seconds">
                        ·
                        {{ formatTotalDuration(nextLesson.remaining_seconds) }}
                        de vídeo</template
                    >
                </p>
            </div>

            <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                <Button
                    size="lg"
                    class="bg-white text-neutral-900 hover:bg-white/90"
                    as-child
                >
                    <Link
                        :href="
                            show([
                                nextLesson.course.slug,
                                nextLesson.lesson.slug,
                            ])
                        "
                        data-test="next-lesson-link"
                    >
                        <CirclePlay />
                        {{ nextLesson.started ? 'Continuar' : 'Começar' }}
                    </Link>
                </Button>
                <p v-if="nextLesson.started" class="text-xs text-white/70">
                    O vídeo retoma de onde você parou.
                </p>
            </div>
        </div>
    </section>
</template>
