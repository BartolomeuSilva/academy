<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    BookOpen,
    FileText,
    MessagesSquare,
    PlayCircle,
    Search,
} from '@lucide/vue';
import { useEventListener, watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { getJson } from '@/lib/http';
import { search as searchRoute } from '@/routes';

type Result = { title: string; subtitle: string | null; url: string };
type Results = Record<
    'courses' | 'lessons' | 'materials' | 'community',
    Result[]
>;

const GROUPS = [
    { key: 'courses', label: 'Cursos', icon: BookOpen },
    { key: 'lessons', label: 'Aulas', icon: PlayCircle },
    { key: 'materials', label: 'Materiais', icon: FileText },
    { key: 'community', label: 'Comunidade', icon: MessagesSquare },
] as const;

const open = ref(false);
const term = ref('');
const results = ref<Results | null>(null);
const loading = ref(false);
const active = ref(0);

const isMac =
    typeof navigator !== 'undefined' &&
    /Mac|iPhone|iPad/.test(navigator.userAgent);

/** Results in display order, so the arrow keys move through every group. */
const flat = computed(() =>
    GROUPS.flatMap((group) =>
        (results.value?.[group.key] ?? []).map((result) => ({
            ...result,
            group: group.key,
        })),
    ),
);

useEventListener('keydown', (event: KeyboardEvent) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        open.value = true;
    }
});

watch(open, (isOpen) => {
    if (!isOpen) {
        term.value = '';
        results.value = null;
    }
});

watchDebounced(
    term,
    async (value) => {
        const query = value.trim();
        active.value = 0;

        if (query.length < 2) {
            results.value = null;

            return;
        }

        loading.value = true;

        try {
            results.value = await getJson<Results>(
                searchRoute.url({ query: { q: query } }),
            );
        } catch {
            results.value = null;
        } finally {
            loading.value = false;
        }
    },
    { debounce: 250 },
);

function go(result: Result & { group: string }): void {
    open.value = false;

    if (result.group === 'materials') {
        window.open(result.url, '_blank', 'noopener');
    } else {
        router.visit(result.url);
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (!flat.value.length) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        active.value = (active.value + 1) % flat.value.length;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        active.value =
            (active.value - 1 + flat.value.length) % flat.value.length;
    } else if (event.key === 'Enter') {
        event.preventDefault();
        go(flat.value[active.value]);
    }
}

function indexOf(result: Result): number {
    return flat.value.findIndex((item) => item.url === result.url);
}
</script>

<template>
    <button
        type="button"
        class="flex h-9 items-center gap-2 rounded-md border bg-card px-3 text-sm text-muted-foreground shadow-xs hover:text-foreground md:w-72"
        aria-label="Buscar"
        data-test="global-search-button"
        @click="open = true"
    >
        <Search class="size-4 shrink-0" aria-hidden="true" />
        <span class="hidden truncate md:inline">Buscar cursos, aulas…</span>
        <kbd
            class="ml-auto hidden rounded border bg-muted px-1.5 font-sans text-[11px] md:inline"
            >{{ isMac ? '⌘' : 'Ctrl' }} K</kbd
        >
    </button>

    <Dialog v-model:open="open">
        <DialogContent class="top-[15%] translate-y-0 gap-0 p-0 sm:max-w-xl">
            <DialogTitle class="sr-only">Buscar</DialogTitle>
            <DialogDescription class="sr-only">
                Busque cursos, aulas, materiais e mensagens da comunidade.
            </DialogDescription>
            <div class="flex items-center gap-2 border-b px-4">
                <Search
                    class="size-4 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
                <input
                    v-model="term"
                    type="search"
                    placeholder="Buscar cursos, aulas, materiais e comunidade"
                    aria-label="Buscar cursos, aulas, materiais e comunidade"
                    class="h-12 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    role="combobox"
                    aria-controls="global-search-results"
                    :aria-expanded="flat.length > 0"
                    :aria-activedescendant="
                        flat.length ? `search-result-${active}` : undefined
                    "
                    data-test="global-search-input"
                    autofocus
                    @keydown="onKeydown"
                />
            </div>

            <div class="max-h-[60vh] overflow-y-auto p-2">
                <p
                    v-if="term.trim().length < 2"
                    class="px-2 py-6 text-center text-sm text-muted-foreground"
                >
                    Digite pelo menos 2 letras.
                </p>
                <p
                    v-else-if="loading && !results"
                    class="px-2 py-6 text-center text-sm text-muted-foreground"
                >
                    Buscando…
                </p>
                <p
                    v-else-if="results && flat.length === 0"
                    class="px-2 py-6 text-center text-sm text-muted-foreground"
                >
                    Nada encontrado para “{{ term.trim() }}”.
                </p>
                <ul
                    v-else
                    id="global-search-results"
                    role="listbox"
                    aria-label="Resultados da busca"
                >
                    <template v-for="group in GROUPS" :key="group.key">
                        <li
                            v-if="results?.[group.key].length"
                            role="presentation"
                            class="px-2 pt-2 pb-1 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            {{ group.label }}
                        </li>
                        <li
                            v-for="result in results?.[group.key] ?? []"
                            :id="`search-result-${indexOf(result)}`"
                            :key="result.url"
                            role="option"
                            :aria-selected="indexOf(result) === active"
                            class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 text-sm aria-selected:bg-accent aria-selected:text-accent-foreground"
                            data-test="global-search-result"
                            @mousemove="active = indexOf(result)"
                            @click="go({ ...result, group: group.key })"
                        >
                            <component
                                :is="group.icon"
                                class="size-4 shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <span class="min-w-0">
                                <span class="block truncate font-medium">{{
                                    result.title
                                }}</span>
                                <span
                                    v-if="result.subtitle"
                                    class="block truncate text-xs text-muted-foreground"
                                    >{{ result.subtitle }}</span
                                >
                            </span>
                        </li>
                    </template>
                </ul>
            </div>
        </DialogContent>
    </Dialog>
</template>
