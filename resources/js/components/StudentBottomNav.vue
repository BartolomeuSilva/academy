<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { useSidebar } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useStudentNav } from '@/composables/useStudentNav';

const items = useStudentNav();
const { isCurrentOrParentUrl } = useCurrentUrl();
const { setOpenMobile } = useSidebar();
</script>

<template>
    <nav
        aria-label="Navegação principal"
        class="fixed inset-x-0 bottom-0 z-40 border-t bg-background/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden"
        data-test="bottom-nav"
    >
        <ul class="flex">
            <li v-for="item in items" :key="item.title" class="flex-1">
                <Link
                    :href="item.href"
                    :aria-current="
                        isCurrentOrParentUrl(item.href) ? 'page' : undefined
                    "
                    class="flex flex-col items-center gap-0.5 py-2 text-xs text-muted-foreground aria-[current=page]:font-semibold aria-[current=page]:text-foreground"
                >
                    <component :is="item.icon" class="size-5" />
                    {{ item.title }}
                </Link>
            </li>
            <li class="flex-1">
                <button
                    type="button"
                    class="flex w-full flex-col items-center gap-0.5 py-2 text-xs text-muted-foreground"
                    data-test="bottom-nav-menu"
                    @click="setOpenMobile(true)"
                >
                    <Menu class="size-5" />
                    Menu
                </button>
            </li>
        </ul>
    </nav>
</template>
