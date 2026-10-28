<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    completed: number;
    total: number;
    label: string;
}>();

const percentage = computed(() =>
    props.total > 0 ? Math.round((props.completed / props.total) * 100) : 0,
);
</script>

<template>
    <div
        role="progressbar"
        :aria-label="label"
        :aria-valuenow="percentage"
        aria-valuemin="0"
        aria-valuemax="100"
        class="h-2 w-full overflow-hidden rounded-full bg-primary/15"
    >
        <div
            class="h-full rounded-full bg-primary transition-[width]"
            :style="{ width: `${percentage}%` }"
        />
    </div>
</template>
