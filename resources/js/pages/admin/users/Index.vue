<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Search,
    ShieldCheck,
    ShieldOff,
    UserCheck,
    UserX,
    Users,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDateTime } from '@/lib/format';
import { index } from '@/routes/admin/users';
import { destroy as unblock, store as block } from '@/routes/admin/users/block';
import { update as updateRole } from '@/routes/admin/users/role';
import type { AdminUser, Paginated } from '@/types';

const props = defineProps<{
    users: Paginated<AdminUser>;
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Usuários', href: index() }],
    },
});

const search = ref(props.filters.search);
const processing = ref(false);

watchDebounced(
    search,
    (value) => {
        router.get(index.url(), value.trim() ? { busca: value.trim() } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
    { debounce: 300 },
);

const visitOptions = (close: () => void) => ({
    preserveScroll: true,
    onStart: () => (processing.value = true),
    onFinish: () => (processing.value = false),
    onSuccess: close,
});

function toggleRole(user: AdminUser, close: () => void): void {
    router.put(
        updateRole.url(user.id),
        { role: user.role === 'admin' ? 'student' : 'admin' },
        visitOptions(close),
    );
}

function toggleBlock(user: AdminUser, close: () => void): void {
    if (user.blocked) {
        router.delete(unblock.url(user.id), visitOptions(close));
    } else {
        router.post(block.url(user.id), {}, visitOptions(close));
    }
}
</script>

<template>
    <Head title="Usuários" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Usuários"
                description="Promova administradores e bloqueie o acesso de contas."
            />
            <div class="relative w-full sm:w-72">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por nome ou e-mail"
                    aria-label="Buscar usuários por nome ou e-mail"
                    class="pl-8"
                    data-test="user-search-input"
                />
            </div>
        </div>

        <div
            v-if="users.data.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-10 text-center"
        >
            <Users class="size-10 text-muted-foreground" />
            <p class="font-medium">Nenhum usuário encontrado</p>
        </div>

        <ul v-else class="grid gap-3">
            <li
                v-for="user in users.data"
                :key="user.id"
                class="flex flex-col gap-3 rounded-xl border bg-card p-4 text-card-foreground sm:flex-row sm:items-center"
                data-test="admin-user"
            >
                <div class="min-w-0 flex-1 space-y-1">
                    <p class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-medium">{{ user.name }}</span>
                        <span v-if="user.is_self" class="text-muted-foreground">
                            (você)
                        </span>
                        <Badge v-if="user.role === 'admin'">Admin</Badge>
                        <Badge v-if="user.blocked" variant="destructive">
                            Bloqueado
                        </Badge>
                        <Badge v-if="!user.verified" variant="outline">
                            E-mail não verificado
                        </Badge>
                    </p>
                    <p class="text-sm break-all text-muted-foreground">
                        {{ user.email }}
                    </p>
                    <p
                        v-if="user.created_at"
                        class="text-xs text-muted-foreground"
                    >
                        Cadastro em {{ formatDateTime(user.created_at) }}
                    </p>
                </div>

                <div v-if="!user.is_self" class="flex flex-wrap gap-2">
                    <ConfirmDialog
                        :title="
                            user.role === 'admin'
                                ? `Tornar ${user.name} aluno?`
                                : `Tornar ${user.name} administrador?`
                        "
                        :description="
                            user.role === 'admin'
                                ? 'A pessoa perde o acesso à administração de cursos, comentários e usuários.'
                                : 'A pessoa poderá gerenciar cursos, moderar comentários e administrar usuários.'
                        "
                        :confirm-label="
                            user.role === 'admin'
                                ? 'Tornar aluno'
                                : 'Tornar administrador'
                        "
                        :confirm-variant="
                            user.role === 'admin' ? 'destructive' : 'default'
                        "
                        :processing="processing"
                        @confirm="(close) => toggleRole(user, close)"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            data-test="toggle-role-button"
                        >
                            <template v-if="user.role === 'admin'">
                                <ShieldOff /> Tornar aluno
                            </template>
                            <template v-else>
                                <ShieldCheck /> Tornar admin
                            </template>
                        </Button>
                    </ConfirmDialog>

                    <ConfirmDialog
                        :title="
                            user.blocked
                                ? `Desbloquear ${user.name}?`
                                : `Bloquear ${user.name}?`
                        "
                        :description="
                            user.blocked
                                ? 'A pessoa volta a conseguir entrar na plataforma.'
                                : 'A pessoa é desconectada e não consegue mais entrar até ser desbloqueada.'
                        "
                        :confirm-label="
                            user.blocked ? 'Desbloquear' : 'Bloquear'
                        "
                        :confirm-variant="
                            user.blocked ? 'default' : 'destructive'
                        "
                        :processing="processing"
                        @confirm="(close) => toggleBlock(user, close)"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            data-test="toggle-block-button"
                        >
                            <template v-if="user.blocked">
                                <UserCheck /> Desbloquear
                            </template>
                            <template v-else> <UserX /> Bloquear </template>
                        </Button>
                    </ConfirmDialog>
                </div>
            </li>
        </ul>

        <nav
            v-if="users.last_page > 1"
            aria-label="Paginação"
            class="flex items-center justify-between gap-2 text-sm"
        >
            <Button
                v-if="users.prev_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="users.prev_page_url" preserve-scroll>
                    <ChevronLeft /> Anteriores
                </Link>
            </Button>
            <span v-else />
            <span class="text-muted-foreground">
                Página {{ users.current_page }} de {{ users.last_page }}
            </span>
            <Button
                v-if="users.next_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="users.next_page_url" preserve-scroll>
                    Próximas <ChevronRight />
                </Link>
            </Button>
            <span v-else />
        </nav>
    </div>
</template>
