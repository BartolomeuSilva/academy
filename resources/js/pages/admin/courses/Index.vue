<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { GraduationCap, GripVertical, Plus } from '@lucide/vue';
import { ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { create, edit, index, order } from '@/routes/admin/courses';
import type { AdminCourseListItem } from '@/types';

const props = defineProps<{
    courses: AdminCourseListItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Cursos', href: index() }],
    },
});

const courses = ref<AdminCourseListItem[]>([...props.courses]);

watch(
    () => props.courses,
    (value) => (courses.value = [...value]),
);

function saveOrder(): void {
    router.put(
        order.url(),
        { ids: courses.value.map((course) => course.id) },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <Head title="Cursos" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Cursos"
                description="Arraste para definir a ordem no catálogo."
            />
            <Button as-child>
                <Link :href="create()" data-test="new-course-link">
                    <Plus /> Novo curso
                </Link>
            </Button>
        </div>

        <div
            v-if="courses.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-10 text-center"
        >
            <GraduationCap class="size-10 text-muted-foreground" />
            <p class="font-medium">Nenhum curso ainda</p>
            <p class="text-sm text-muted-foreground">
                Crie o primeiro curso para começar a montar os treinamentos.
            </p>
        </div>

        <VueDraggable
            v-else
            v-model="courses"
            :animation="150"
            handle="[data-drag-handle]"
            class="grid gap-3"
            @end="saveOrder"
        >
            <div
                v-for="course in courses"
                :key="course.id"
                class="flex items-center gap-3 rounded-xl border bg-card p-3 text-card-foreground"
            >
                <button
                    type="button"
                    data-drag-handle
                    class="cursor-grab text-muted-foreground hover:text-foreground"
                    aria-label="Arrastar para reordenar"
                >
                    <GripVertical class="size-5" />
                </button>

                <div
                    class="hidden aspect-video w-28 shrink-0 overflow-hidden rounded-md bg-muted sm:block"
                >
                    <img
                        v-if="course.cover_url"
                        :src="course.cover_url"
                        :alt="`Capa do curso ${course.title}`"
                        class="size-full object-cover"
                    />
                </div>

                <div class="min-w-0 flex-1">
                    <Link
                        :href="edit(course.id)"
                        class="font-medium hover:underline"
                    >
                        {{ course.title }}
                    </Link>
                    <p class="truncate text-sm text-muted-foreground">
                        {{ course.summary }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ course.modules_count }}
                        {{ course.modules_count === 1 ? 'módulo' : 'módulos' }}
                        ·
                        {{ course.lessons_count }}
                        {{ course.lessons_count === 1 ? 'aula' : 'aulas' }}
                    </p>
                </div>

                <StatusBadge :status="course.status" />
            </div>
        </VueDraggable>
    </div>
</template>
