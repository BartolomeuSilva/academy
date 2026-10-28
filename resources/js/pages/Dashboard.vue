<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Início',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Início" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="rounded-xl border bg-card p-6 text-card-foreground">
            <h1 class="text-xl font-semibold">
                Olá, {{ $page.props.auth.user.name }}!
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                O catálogo de cursos estará disponível aqui em breve.
            </p>
        </div>
    </div>
</template>
