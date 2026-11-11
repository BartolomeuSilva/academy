<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Star } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { update } from '@/routes/lessons/rating';
import type { LessonRatingSummary } from '@/types';

const STAR_LABELS = ['Ruim', 'Fraca', 'Boa', 'Muito boa', 'Excelente'];

const props = defineProps<{
    lessonId: number;
    rating: LessonRatingSummary;
}>();

const form = useForm({
    stars: props.rating.mine?.stars ?? 0,
    feedback: props.rating.mine?.feedback ?? '',
});

const editing = ref(props.rating.mine === null);
const hovered = ref(0);
const shownStars = computed(() => hovered.value || form.stars);

function submit(): void {
    form.put(update.url(props.lessonId), {
        preserveScroll: true,
        preserveState: true,
        only: ['rating'],
        onSuccess: () => (editing.value = false),
    });
}
</script>

<template>
    <section
        class="space-y-4 rounded-xl border bg-card p-4 text-card-foreground md:p-6"
        aria-labelledby="rating-heading"
        data-test="lesson-rating"
    >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <h2 id="rating-heading" class="font-semibold">
                    O que achou desta aula?
                </h2>
                <p class="text-sm text-muted-foreground">
                    Sua nota ajuda a melhorar os próximos conteúdos.
                </p>
            </div>
            <p
                v-if="rating.average !== null"
                class="flex items-center gap-1 rounded-lg bg-accent/60 px-3 py-1.5 text-sm"
                data-test="rating-average"
            >
                <Star class="size-4 fill-amber-400 text-amber-500" />
                <span class="font-semibold tabular-nums">{{
                    rating.average.toLocaleString('pt-BR')
                }}</span>
                <span class="text-muted-foreground"
                    >· {{ rating.count }}
                    {{ rating.count === 1 ? 'avaliação' : 'avaliações' }}</span
                >
            </p>
        </div>

        <div
            v-if="!editing && rating.mine"
            class="flex flex-wrap items-center gap-3 text-sm"
        >
            <span class="text-muted-foreground">Sua nota:</span>
            <span
                class="flex"
                :aria-label="`${rating.mine.stars} de 5 estrelas`"
                role="img"
            >
                <Star
                    v-for="value in 5"
                    :key="value"
                    class="size-5"
                    :class="
                        value <= rating.mine.stars
                            ? 'fill-amber-400 text-amber-500'
                            : 'text-muted-foreground/40'
                    "
                    aria-hidden="true"
                />
            </span>
            <Button
                variant="ghost"
                size="sm"
                data-test="edit-rating-button"
                @click="editing = true"
            >
                Alterar
            </Button>
        </div>

        <form v-else class="space-y-3" @submit.prevent="submit">
            <fieldset>
                <legend class="mb-2 text-sm font-medium">
                    Sua nota para esta aula
                </legend>
                <div class="flex gap-1" @mouseleave="hovered = 0">
                    <label
                        v-for="value in 5"
                        :key="value"
                        class="cursor-pointer rounded-md p-1 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                        @mouseenter="hovered = value"
                    >
                        <input
                            v-model="form.stars"
                            type="radio"
                            name="stars"
                            :value="value"
                            class="sr-only"
                            :data-test="`rating-star-${value}`"
                        />
                        <Star
                            class="size-7 transition-colors"
                            :class="
                                value <= shownStars
                                    ? 'fill-amber-400 text-amber-500'
                                    : 'text-muted-foreground/50'
                            "
                            aria-hidden="true"
                        />
                        <span class="sr-only"
                            >{{ value }}
                            {{ value === 1 ? 'estrela' : 'estrelas' }} —
                            {{ STAR_LABELS[value - 1] }}</span
                        >
                    </label>
                </div>
                <p class="mt-1 h-5 text-sm text-muted-foreground">
                    {{ shownStars ? STAR_LABELS[shownStars - 1] : '' }}
                </p>
                <InputError :message="form.errors.stars" />
            </fieldset>

            <div v-if="form.stars" class="space-y-1">
                <label for="rating-feedback" class="text-sm font-medium">
                    Quer contar o que pode melhorar? (opcional)
                </label>
                <Textarea
                    id="rating-feedback"
                    v-model="form.feedback"
                    maxlength="1000"
                    placeholder="Só a equipe vê este comentário."
                    :readonly="form.processing"
                    data-test="rating-feedback-input"
                />
                <InputError :message="form.errors.feedback" />
            </div>

            <div class="flex gap-2">
                <Button
                    type="submit"
                    :disabled="!form.stars || form.processing"
                    data-test="send-rating-button"
                >
                    Enviar avaliação
                </Button>
                <Button
                    v-if="rating.mine"
                    type="button"
                    variant="ghost"
                    @click="
                        form.reset();
                        editing = false;
                    "
                >
                    Cancelar
                </Button>
            </div>
        </form>
    </section>
</template>
