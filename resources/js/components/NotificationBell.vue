<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatRelativeTime } from '@/lib/format';
import { getJson, postJson } from '@/lib/http';
import { index, readAll, show } from '@/routes/notifications';
import type { BellNotification } from '@/types';

const page = usePage();
const unread = ref<number | null>(null);
const notifications = ref<BellNotification[] | null>(null);
const failed = ref(false);

// Each visit brings a fresh count, which replaces the one fetched when the bell was last opened.
watch(
    () => page.url,
    () => {
        unread.value = null;
        notifications.value = null;
    },
);

const unreadCount = computed(
    () => unread.value ?? page.props.unreadNotifications,
);

async function load(open: boolean): Promise<void> {
    if (!open) {
        return;
    }

    try {
        const response = await getJson<{
            unread: number;
            notifications: BellNotification[];
        }>(index.url());
        unread.value = response.unread;
        notifications.value = response.notifications;
        failed.value = false;
    } catch {
        failed.value = true;
    }
}

async function markAllAsRead(): Promise<void> {
    await postJson(readAll.url(), {}).catch(() => undefined);
    unread.value = 0;
    notifications.value =
        notifications.value?.map((item) => ({ ...item, read: true })) ?? null;
}
</script>

<template>
    <DropdownMenu @update:open="load">
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative"
                :aria-label="
                    unreadCount
                        ? `Notificações: ${unreadCount} não lidas`
                        : 'Notificações'
                "
                data-test="notification-bell"
            >
                <Bell />
                <span
                    v-if="unreadCount"
                    class="absolute -top-0.5 -right-0.5 flex min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4 font-semibold text-white"
                    aria-hidden="true"
                    data-test="notification-count"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <div class="flex items-center justify-between gap-2 pr-1">
                <DropdownMenuLabel>Notificações</DropdownMenuLabel>
                <Button
                    v-if="unreadCount"
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs"
                    data-test="mark-all-read-button"
                    @click.prevent="markAllAsRead"
                >
                    Marcar todas como lidas
                </Button>
            </div>
            <DropdownMenuSeparator />
            <p
                v-if="failed"
                class="px-2 py-4 text-center text-sm text-muted-foreground"
            >
                Não foi possível carregar.
            </p>
            <p
                v-else-if="notifications === null"
                class="px-2 py-4 text-center text-sm text-muted-foreground"
            >
                Carregando…
            </p>
            <p
                v-else-if="notifications.length === 0"
                class="px-2 py-4 text-center text-sm text-muted-foreground"
            >
                Nenhuma notificação por aqui.
            </p>
            <DropdownMenuItem
                v-for="notification in notifications ?? []"
                v-else
                :key="notification.id"
                as-child
            >
                <Link
                    :href="show(notification.id)"
                    class="flex cursor-pointer items-start gap-2"
                    data-test="notification-item"
                >
                    <span
                        class="mt-1.5 size-2 shrink-0 rounded-full"
                        :class="
                            notification.read ? 'bg-transparent' : 'bg-primary'
                        "
                        aria-hidden="true"
                    />
                    <span class="min-w-0 space-y-0.5">
                        <span class="block text-sm">
                            <span class="font-semibold">{{
                                notification.actor
                            }}</span>
                            {{ notification.message }}
                        </span>
                        <span
                            v-if="notification.excerpt"
                            class="line-clamp-2 block text-xs text-muted-foreground"
                            >{{ notification.excerpt }}</span
                        >
                        <span
                            v-if="notification.created_at"
                            class="block text-xs text-muted-foreground"
                            >{{
                                formatRelativeTime(notification.created_at)
                            }}</span
                        >
                        <span v-if="!notification.read" class="sr-only"
                            >(não lida)</span
                        >
                    </span>
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
