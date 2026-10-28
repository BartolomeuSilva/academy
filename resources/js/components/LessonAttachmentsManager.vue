<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    ExternalLink,
    FileText,
    GripVertical,
    Image,
    Pencil,
    Trash2,
    Upload,
} from '@lucide/vue';
import { ref, useTemplateRef, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatBytes } from '@/lib/format';
import {
    destroy as destroyAttachment,
    update as updateAttachment,
} from '@/routes/admin/attachments';
import { order, store } from '@/routes/admin/lessons/attachments';
import type { AdminAttachment } from '@/types';

const props = defineProps<{
    lessonId: number;
    attachments: AdminAttachment[];
}>();

const attachments = ref<AdminAttachment[]>([...props.attachments]);

watch(
    () => props.attachments,
    (value) => (attachments.value = [...value]),
);

const fileInput = useTemplateRef('fileInput');
const uploading = ref(false);
const uploadProgress = ref<number | null>(null);
const uploadErrors = ref<string[]>([]);

function upload(event: Event): void {
    const files = Array.from((event.target as HTMLInputElement).files ?? []);

    if (files.length === 0) {
        return;
    }

    router.post(
        store.url(props.lessonId),
        { files },
        {
            preserveScroll: true,
            onStart: () => {
                uploading.value = true;
                uploadErrors.value = [];
            },
            onProgress: (event) =>
                (uploadProgress.value = event?.percentage ?? null),
            onError: (errors) => (uploadErrors.value = Object.values(errors)),
            onFinish: () => {
                uploading.value = false;
                uploadProgress.value = null;

                if (fileInput.value) {
                    fileInput.value.value = '';
                }
            },
        },
    );
}

function saveOrder(): void {
    router.put(
        order.url(props.lessonId),
        { ids: attachments.value.map((attachment) => attachment.id) },
        { preserveScroll: true, preserveState: true },
    );
}

const renamingId = ref<number | null>(null);
const renameForm = useForm({ original_name: '' });

function startRename(attachment: AdminAttachment): void {
    renamingId.value = attachment.id;
    renameForm.original_name = attachment.original_name;
    renameForm.clearErrors();
}

function saveRename(attachmentId: number): void {
    renameForm.put(updateAttachment.url(attachmentId), {
        preserveScroll: true,
        onSuccess: () => (renamingId.value = null),
    });
}

function remove(attachmentId: number, close: () => void): void {
    router.delete(destroyAttachment.url(attachmentId), {
        preserveScroll: true,
        onSuccess: close,
    });
}
</script>

<template>
    <section
        class="space-y-4 rounded-xl border bg-card p-4 text-card-foreground"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-medium">Anexos</h2>
                <p class="text-sm text-muted-foreground">
                    Imagens (JPG, PNG, WEBP) e PDFs de até 50 MB, até 10 por
                    vez.
                </p>
            </div>

            <input
                ref="fileInput"
                type="file"
                multiple
                accept="image/jpeg,image/png,image/webp,application/pdf"
                class="hidden"
                data-test="attachments-input"
                @change="upload"
            />
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="uploading"
                @click="fileInput?.click()"
            >
                <Upload /> Enviar arquivos
            </Button>
        </div>

        <div
            v-if="uploadProgress !== null"
            class="h-1.5 overflow-hidden rounded bg-muted"
        >
            <div
                class="h-full bg-primary"
                :style="{ width: `${uploadProgress}%` }"
            />
        </div>
        <InputError
            v-for="message in uploadErrors"
            :key="message"
            :message="message"
        />

        <p
            v-if="attachments.length === 0"
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            Nenhum anexo nesta aula.
        </p>

        <VueDraggable
            v-else
            v-model="attachments"
            :animation="150"
            handle="[data-drag-handle]"
            class="divide-y rounded-lg border"
            @end="saveOrder"
        >
            <div
                v-for="attachment in attachments"
                :key="attachment.id"
                class="flex items-center gap-3 px-3 py-2"
            >
                <button
                    type="button"
                    data-drag-handle
                    class="cursor-grab text-muted-foreground hover:text-foreground"
                    aria-label="Arrastar anexo"
                >
                    <GripVertical class="size-4" />
                </button>

                <component
                    :is="attachment.kind === 'pdf' ? FileText : Image"
                    class="size-5 shrink-0 text-muted-foreground"
                />

                <form
                    v-if="renamingId === attachment.id"
                    class="flex flex-1 items-start gap-2"
                    @submit.prevent="saveRename(attachment.id)"
                >
                    <div class="flex-1">
                        <Input
                            v-model="renameForm.original_name"
                            v-focus
                            aria-label="Nome do anexo"
                        />
                        <InputError
                            :message="renameForm.errors.original_name"
                        />
                    </div>
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="renameForm.processing"
                    >
                        Salvar
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        @click="renamingId = null"
                    >
                        Cancelar
                    </Button>
                </form>

                <template v-else>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm">
                            {{ attachment.original_name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatBytes(attachment.size) }}
                        </p>
                    </div>

                    <Button size="icon" variant="ghost" as-child>
                        <a
                            :href="attachment.url"
                            target="_blank"
                            rel="noopener"
                            aria-label="Abrir anexo"
                        >
                            <ExternalLink />
                        </a>
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        aria-label="Renomear anexo"
                        @click="startRename(attachment)"
                    >
                        <Pencil />
                    </Button>
                    <ConfirmDialog
                        :title="`Excluir “${attachment.original_name}”?`"
                        description="O arquivo será excluído permanentemente."
                        @confirm="(close) => remove(attachment.id, close)"
                    >
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            aria-label="Excluir anexo"
                        >
                            <Trash2 />
                        </Button>
                    </ConfirmDialog>
                </template>
            </div>
        </VueDraggable>
    </section>
</template>
