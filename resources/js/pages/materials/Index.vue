<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Download,
    FolderOpen,
    Search,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatBytes } from '@/lib/format';
import { show as showLesson } from '@/routes/lessons';
import { index } from '@/routes/materials';
import type { LibraryMaterial, Paginated } from '@/types';

const props = defineProps<{
    materials: Paginated<LibraryMaterial>;
    courses: { id: number; slug: string; title: string }[];
    filters: { search: string; course: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Materiais', href: index() }],
    },
});

const search = ref(props.filters.search);

function visit(filters: { search: string; course: string }): void {
    router.get(
        index.url(),
        {
            ...(filters.search ? { busca: filters.search } : {}),
            ...(filters.course ? { curso: filters.course } : {}),
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

watchDebounced(
    search,
    (value) => visit({ search: value.trim(), course: props.filters.course }),
    { debounce: 300 },
);
</script>

<template>
    <Head title="Materiais" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <header class="space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight">Materiais</h1>
            <p class="text-muted-foreground">
                Todos os arquivos das aulas num só lugar.
            </p>
        </header>

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div
                v-if="courses.length > 1"
                class="flex flex-wrap gap-2"
                role="group"
                aria-label="Filtrar por curso"
            >
                <Button
                    size="sm"
                    :variant="filters.course === '' ? 'default' : 'outline'"
                    :aria-pressed="filters.course === ''"
                    @click="visit({ search: search.trim(), course: '' })"
                >
                    Todos
                </Button>
                <Button
                    v-for="course in courses"
                    :key="course.id"
                    size="sm"
                    :variant="
                        filters.course === course.slug ? 'default' : 'outline'
                    "
                    :aria-pressed="filters.course === course.slug"
                    data-test="material-course-filter"
                    @click="
                        visit({ search: search.trim(), course: course.slug })
                    "
                >
                    {{ course.title }}
                </Button>
            </div>
            <div class="relative lg:ml-auto lg:w-72">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    v-model="search"
                    type="search"
                    class="pl-9"
                    placeholder="Buscar um material"
                    aria-label="Buscar um material"
                    data-test="material-search"
                />
            </div>
        </div>

        <div
            v-if="materials.data.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-10 text-center"
        >
            <FolderOpen class="size-10 text-muted-foreground" />
            <p class="font-medium">
                {{
                    filters.search || filters.course
                        ? 'Nenhum material encontrado'
                        : 'Nenhum material disponível ainda'
                }}
            </p>
            <p class="text-sm text-muted-foreground">
                {{
                    filters.search || filters.course
                        ? 'Tente outro nome ou limpe o filtro.'
                        : 'Os arquivos das aulas aparecerão aqui.'
                }}
            </p>
        </div>

        <ul v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <li
                v-for="material in materials.data"
                :key="material.id"
                class="flex min-w-0 flex-col gap-4 rounded-xl border bg-card p-4 text-card-foreground"
                data-test="library-material"
            >
                <div class="flex items-start gap-3">
                    <a
                        :href="material.url"
                        target="_blank"
                        rel="noopener"
                        class="shrink-0 rounded-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        :aria-label="`Abrir ${material.original_name}`"
                    >
                        <img
                            v-if="material.kind === 'image'"
                            :src="material.url"
                            alt=""
                            loading="lazy"
                            class="size-12 rounded-md object-cover"
                        />
                        <span
                            v-else
                            class="flex size-12 items-center justify-center rounded-md bg-red-50 text-xs font-bold tracking-wide text-red-700 ring-1 ring-red-200 dark:bg-red-950/50 dark:text-red-300 dark:ring-red-900"
                        >
                            PDF
                        </span>
                    </a>
                    <div class="min-w-0">
                        <p class="line-clamp-2 font-medium break-words">
                            {{ material.original_name }}
                        </p>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ material.course.title }}
                        </p>
                    </div>
                </div>
                <div
                    class="mt-auto flex items-center justify-between gap-2 border-t pt-3"
                >
                    <Link
                        :href="
                            showLesson([
                                material.course.slug,
                                material.lesson.slug,
                            ])
                        "
                        class="min-w-0 truncate text-sm text-muted-foreground hover:text-foreground hover:underline"
                    >
                        Aula: {{ material.lesson.title }}
                    </Link>
                    <Button variant="outline" size="sm" as-child>
                        <a
                            :href="material.download_url"
                            :aria-label="`Baixar ${material.original_name} (${formatBytes(material.size)})`"
                        >
                            <Download /> {{ formatBytes(material.size) }}
                        </a>
                    </Button>
                </div>
            </li>
        </ul>

        <nav
            v-if="materials.last_page > 1"
            aria-label="Paginação"
            class="flex items-center justify-between gap-2 text-sm"
        >
            <Button
                v-if="materials.prev_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="materials.prev_page_url" preserve-scroll>
                    <ChevronLeft /> Anteriores
                </Link>
            </Button>
            <span v-else />
            <span class="text-muted-foreground">
                Página {{ materials.current_page }} de
                {{ materials.last_page }}
            </span>
            <Button
                v-if="materials.next_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="materials.next_page_url" preserve-scroll>
                    Próximos <ChevronRight />
                </Link>
            </Button>
            <span v-else />
        </nav>
    </div>
</template>
