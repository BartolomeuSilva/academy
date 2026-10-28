<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { MessageSquare, PlayCircle, Paperclip } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';
import { index as catalog } from '@/routes/courses';

const features = [
    {
        icon: PlayCircle,
        title: 'Videoaulas',
        description: 'Assista no seu ritmo, em qualquer dispositivo.',
    },
    {
        icon: Paperclip,
        title: 'Materiais de apoio',
        description: 'Imagens e PDFs anexados a cada aula.',
    },
    {
        icon: MessageSquare,
        title: 'Tire suas dúvidas',
        description: 'Comente nas aulas e converse com a equipe.',
    },
];
</script>

<template>
    <Head title="Bem-vindo" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-6"
        >
            <div class="flex items-center gap-2 font-semibold">
                <AppLogoIcon class="size-7 fill-current" />
                <span>{{ $page.props.name }}</span>
            </div>

            <nav class="flex items-center gap-2">
                <Button v-if="$page.props.auth.user" as-child>
                    <Link :href="catalog()">Acessar plataforma</Link>
                </Button>
                <template v-else>
                    <Button variant="ghost" as-child>
                        <Link :href="login()">Entrar</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="register()">Criar conta</Link>
                    </Button>
                </template>
            </nav>
        </header>

        <main
            class="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center gap-12 px-4 py-12"
        >
            <section class="max-w-2xl space-y-4">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
                    Treinamentos internos, em um só lugar.
                </h1>
                <p class="text-lg text-muted-foreground">
                    Cursos em vídeo organizados por módulos, com materiais de
                    apoio e espaço para dúvidas. Gratuito para toda a equipe.
                </p>
                <div
                    v-if="!$page.props.auth.user"
                    class="flex flex-wrap gap-3 pt-2"
                >
                    <Button size="lg" as-child>
                        <Link :href="register()">Começar agora</Link>
                    </Button>
                    <Button size="lg" variant="outline" as-child>
                        <Link :href="login()">Já tenho conta</Link>
                    </Button>
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-3">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="rounded-xl border bg-card p-5 text-card-foreground"
                >
                    <component
                        :is="feature.icon"
                        class="mb-3 size-6 text-primary"
                    />
                    <h2 class="font-semibold">{{ feature.title }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ feature.description }}
                    </p>
                </div>
            </section>
        </main>
    </div>
</template>
