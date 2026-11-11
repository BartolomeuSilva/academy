<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, ShieldCheck, Trash2 } from '@lucide/vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { destroy, index, update } from '@/routes/admin/community/reports';
import { show as showTopic } from '@/routes/community/topics';
import type { AdminCommunityReport, Paginated } from '@/types';

defineProps<{
    reports: Paginated<AdminCommunityReport>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Denúncias', href: index() }],
    },
});

function dismiss(report: AdminCommunityReport): void {
    router.patch(update.url(report.id), {}, { preserveScroll: true });
}

function removeContent(report: AdminCommunityReport, close: () => void): void {
    router.delete(destroy.url(report.id), {
        preserveScroll: true,
        onSuccess: close,
    });
}
</script>

<template>
    <Head title="Denúncias" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Denúncias"
            description="Conteúdos da comunidade que os alunos pediram para revisar."
        />

        <div
            v-if="reports.data.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-10 text-center"
        >
            <ShieldCheck class="size-10 text-muted-foreground" />
            <p class="font-medium">Nenhuma denúncia aberta</p>
        </div>

        <ul v-else class="grid gap-3">
            <li
                v-for="report in reports.data"
                :key="report.id"
                class="space-y-3 rounded-xl border bg-card p-4 text-card-foreground"
                data-test="admin-report"
            >
                <p
                    class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                >
                    <Badge variant="secondary">{{
                        report.content?.kind === 'topic' ? 'Tópico' : 'Resposta'
                    }}</Badge>
                    <span
                        >Denunciado por
                        <span class="font-medium text-foreground">{{
                            report.reporter
                        }}</span></span
                    >
                    <span v-if="report.created_at">{{
                        formatDateTime(report.created_at)
                    }}</span>
                    <Badge v-if="report.content?.deleted" variant="outline"
                        >Já removido</Badge
                    >
                </p>
                <p v-if="report.reason" class="text-sm">
                    <span class="font-medium">Motivo:</span> {{ report.reason }}
                </p>
                <blockquote
                    v-if="report.content"
                    class="border-l-2 pl-3 text-sm whitespace-pre-line text-muted-foreground"
                >
                    <span class="font-medium text-foreground"
                        >{{ report.content.author }}:</span
                    >
                    {{ report.content.body }}
                </blockquote>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        v-if="report.content?.topic && !report.content.deleted"
                        :href="showTopic(report.content.topic.id)"
                        class="mr-auto text-sm underline underline-offset-4"
                    >
                        Ver “{{ report.content.topic.title }}”
                    </Link>
                    <Button
                        variant="outline"
                        size="sm"
                        data-test="dismiss-report-button"
                        @click="dismiss(report)"
                    >
                        <Archive /> Manter e arquivar
                    </Button>
                    <ConfirmDialog
                        title="Remover este conteúdo?"
                        description="O conteúdo sai da comunidade e todas as denúncias sobre ele são fechadas."
                        confirm-label="Remover"
                        @confirm="(close) => removeContent(report, close)"
                    >
                        <Button
                            variant="destructive"
                            size="sm"
                            data-test="remove-reported-button"
                        >
                            <Trash2 /> Remover conteúdo
                        </Button>
                    </ConfirmDialog>
                </div>
            </li>
        </ul>

        <PaginationNav :page="reports" />
    </div>
</template>
