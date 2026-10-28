<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, MessageSquare, Trash2 } from '@lucide/vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { destroy, index } from '@/routes/admin/comments';
import { show as showLesson } from '@/routes/lessons';
import type { AdminComment, Paginated } from '@/types';

defineProps<{
    comments: Paginated<AdminComment>;
    courses: { id: number; title: string }[];
    filters: { course_id: number | null };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Comentários', href: index() }],
    },
});

function filterByCourse(event: Event): void {
    const courseId = (event.target as HTMLSelectElement).value;

    router.get(index.url(), courseId ? { curso: courseId } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

function remove(comment: AdminComment, close: () => void): void {
    router.delete(destroy.url(comment.id), {
        preserveScroll: true,
        onSuccess: close,
    });
}
</script>

<template>
    <Head title="Comentários" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Comentários"
                description="Comentários mais recentes de todas as aulas."
            />
            <div class="flex items-center gap-2 text-sm">
                <label for="course-filter" class="text-muted-foreground">
                    Curso
                </label>
                <select
                    id="course-filter"
                    :value="filters.course_id ?? ''"
                    class="h-9 rounded-md border border-input bg-transparent px-2 text-sm dark:bg-input/30"
                    @change="filterByCourse"
                >
                    <option value="">Todos os cursos</option>
                    <option
                        v-for="course in courses"
                        :key="course.id"
                        :value="course.id"
                    >
                        {{ course.title }}
                    </option>
                </select>
            </div>
        </div>

        <div
            v-if="comments.data.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-10 text-center"
        >
            <MessageSquare class="size-10 text-muted-foreground" />
            <p class="font-medium">Nenhum comentário</p>
        </div>

        <ul v-else class="grid gap-3">
            <li
                v-for="comment in comments.data"
                :key="comment.id"
                class="flex gap-3 rounded-xl border bg-card p-4 text-card-foreground"
                data-test="admin-comment"
            >
                <div class="min-w-0 flex-1 space-y-1">
                    <p class="flex flex-wrap items-center gap-x-2 text-sm">
                        <span class="font-medium">{{ comment.author }}</span>
                        <Badge v-if="comment.is_reply" variant="secondary">
                            Resposta
                        </Badge>
                        <span
                            v-if="comment.created_at"
                            class="text-xs text-muted-foreground"
                        >
                            {{ formatDateTime(comment.created_at) }}
                            <template v-if="comment.edited">
                                (editado)
                            </template>
                        </span>
                    </p>
                    <p class="text-sm break-words whitespace-pre-line">
                        {{ comment.body }}
                    </p>
                    <Link
                        :href="
                            showLesson([
                                comment.course.slug,
                                comment.lesson.slug,
                            ])
                        "
                        class="text-xs text-muted-foreground underline-offset-4 hover:underline"
                    >
                        {{ comment.course.title }} · {{ comment.lesson.title }}
                    </Link>
                </div>

                <ConfirmDialog
                    title="Excluir comentário?"
                    :description="
                        comment.is_reply
                            ? 'A resposta será removida da aula.'
                            : 'O comentário e todas as respostas serão removidos da aula.'
                    "
                    @confirm="(close) => remove(comment, close)"
                >
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="`Excluir comentário de ${comment.author}`"
                    >
                        <Trash2 />
                    </Button>
                </ConfirmDialog>
            </li>
        </ul>

        <nav
            v-if="comments.last_page > 1"
            aria-label="Paginação"
            class="flex items-center justify-between gap-2 text-sm"
        >
            <Button
                v-if="comments.prev_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="comments.prev_page_url" preserve-scroll>
                    <ChevronLeft /> Anteriores
                </Link>
            </Button>
            <span v-else />
            <span class="text-muted-foreground">
                Página {{ comments.current_page }} de {{ comments.last_page }}
            </span>
            <Button
                v-if="comments.next_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="comments.next_page_url" preserve-scroll>
                    Próximas <ChevronRight />
                </Link>
            </Button>
            <span v-else />
        </nav>
    </div>
</template>
