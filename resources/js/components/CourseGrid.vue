<script setup lang="ts">
import { computed, ref } from 'vue';
import CourseCard from '@/components/CourseCard.vue';
import { Button } from '@/components/ui/button';
import type { CatalogCourse } from '@/types';

const UNCATEGORIZED = 'Outros cursos';

const props = withDefaults(
    defineProps<{
        courses: CatalogCourse[];
        /** Show category chips above the grid when there is more than one category. */
        filterable?: boolean;
    }>(),
    { filterable: false },
);

const categories = computed(() => {
    const names = [
        ...new Set(
            props.courses.flatMap((course) =>
                course.category ? [course.category] : [],
            ),
        ),
    ];

    return props.courses.some((course) => !course.category) && names.length
        ? [...names, UNCATEGORIZED]
        : names;
});

const selected = ref<string | null>(null);

const shown = computed(() =>
    selected.value === null
        ? props.courses
        : props.courses.filter(
              (course) => (course.category ?? UNCATEGORIZED) === selected.value,
          ),
);
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="filterable && categories.length > 1"
            class="flex flex-wrap gap-2"
            role="group"
            aria-label="Filtrar cursos por categoria"
        >
            <Button
                size="sm"
                class="rounded-full"
                :variant="selected === null ? 'default' : 'outline'"
                :aria-pressed="selected === null"
                @click="selected = null"
            >
                Todos
            </Button>
            <Button
                v-for="category in categories"
                :key="category"
                size="sm"
                class="rounded-full"
                :variant="selected === category ? 'default' : 'outline'"
                :aria-pressed="selected === category"
                data-test="category-filter"
                @click="selected = category"
            >
                {{ category }}
            </Button>
        </div>
        <ul
            class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6"
        >
            <li v-for="course in shown" :key="course.id" class="flex">
                <CourseCard :course="course" class="w-full" />
            </li>
        </ul>
    </div>
</template>
