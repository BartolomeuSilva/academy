<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Camera } from '@lucide/vue';
import { computed, ref, useTemplateRef } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import { destroy, store } from '@/routes/avatar';

withDefaults(
    defineProps<{
        /** Smaller card for side panels, e.g. the community. */
        compact?: boolean;
    }>(),
    { compact: false },
);

const page = usePage();
const user = computed(() => page.props.auth.user);
const input = useTemplateRef('input');
const uploading = ref(false);
const error = ref<string | null>(null);

function upload(): void {
    const file = input.value?.files?.[0];

    if (!file) {
        return;
    }

    router.post(
        store.url(),
        { avatar: file },
        {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
            onStart: () => {
                uploading.value = true;
                error.value = null;
            },
            onError: (errors) => (error.value = errors.avatar ?? null),
            onFinish: () => {
                uploading.value = false;

                if (input.value) {
                    input.value.value = '';
                }
            },
        },
    );
}

function remove(): void {
    router.delete(destroy.url(), { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <div v-if="user" class="space-y-2" data-test="avatar-uploader">
        <div class="flex items-center gap-3">
            <Avatar
                :class="compact ? 'size-10' : 'size-16'"
                class="shrink-0 overflow-hidden"
            >
                <AvatarImage
                    v-if="user.avatar"
                    :src="user.avatar"
                    :alt="`Foto de ${user.name}`"
                    class="object-cover"
                />
                <AvatarFallback :class="compact ? 'text-xs' : 'text-lg'">{{
                    getInitials(user.name)
                }}</AvatarFallback>
            </Avatar>
            <div class="min-w-0 flex-1">
                <p v-if="compact" class="truncate text-sm font-semibold">
                    {{ user.name }}
                </p>
                <p v-if="compact" class="text-xs text-muted-foreground">Você</p>
                <div v-else class="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="uploading"
                        data-test="change-avatar-button"
                        @click="input?.click()"
                    >
                        <Camera />
                        {{ uploading ? 'Enviando…' : 'Trocar foto' }}
                    </Button>
                    <Button
                        v-if="user.avatar"
                        type="button"
                        variant="ghost"
                        size="sm"
                        data-test="remove-avatar-button"
                        @click="remove"
                    >
                        Remover
                    </Button>
                </div>
            </div>
            <Button
                v-if="compact"
                type="button"
                variant="outline"
                size="sm"
                class="shrink-0 rounded-full"
                :disabled="uploading"
                data-test="change-avatar-button"
                @click="input?.click()"
            >
                {{ uploading ? 'Enviando…' : 'Trocar foto' }}
            </Button>
        </div>
        <input
            ref="input"
            type="file"
            accept="image/png,image/jpeg,image/webp"
            class="sr-only"
            tabindex="-1"
            aria-label="Foto de perfil"
            data-test="avatar-input"
            @change="upload"
        />
        <p v-if="!compact" class="text-xs text-muted-foreground">
            JPG, PNG ou WebP de até 2 MB. Aparece na comunidade e nos
            comentários.
        </p>
        <p v-if="error" class="text-sm text-destructive" role="alert">
            {{ error }}
        </p>
    </div>
</template>
