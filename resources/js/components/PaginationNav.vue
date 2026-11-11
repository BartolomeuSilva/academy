<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

defineProps<{
    page: Pick<
        Paginated<unknown>,
        'current_page' | 'last_page' | 'prev_page_url' | 'next_page_url'
    >;
}>();
</script>

<template>
    <nav
        v-if="page.last_page > 1"
        aria-label="Paginação"
        class="flex items-center justify-between gap-2 text-sm"
    >
        <Button v-if="page.prev_page_url" variant="outline" size="sm" as-child>
            <Link :href="page.prev_page_url" preserve-scroll>
                <ChevronLeft /> Anteriores
            </Link>
        </Button>
        <span v-else />
        <span class="text-muted-foreground">
            Página {{ page.current_page }} de {{ page.last_page }}
        </span>
        <Button v-if="page.next_page_url" variant="outline" size="sm" as-child>
            <Link :href="page.next_page_url" preserve-scroll>
                Próximos <ChevronRight />
            </Link>
        </Button>
        <span v-else />
    </nav>
</template>
