<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { PublicationStatus } from '@/types';

defineProps<{
    status: PublicationStatus;
}>();
</script>

<template>
    <Badge :variant="status === 'published' ? 'default' : 'secondary'">
        {{ status === 'published' ? 'Publicado' : 'Rascunho' }}
    </Badge>
</template>
