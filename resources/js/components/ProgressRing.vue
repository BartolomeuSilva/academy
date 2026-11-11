<script setup lang="ts">
const RADIUS = 15;
const LENGTH = 2 * Math.PI * RADIUS;

withDefaults(
    defineProps<{
        percentage: number;
        size?: 'sm' | 'lg';
    }>(),
    { size: 'sm' },
);
</script>

<template>
    <div
        class="relative shrink-0"
        :class="size === 'lg' ? 'size-16' : 'size-10'"
    >
        <svg
            viewBox="0 0 36 36"
            class="size-full -rotate-90"
            aria-hidden="true"
        >
            <circle
                cx="18"
                cy="18"
                :r="RADIUS"
                fill="none"
                stroke-width="3"
                class="stroke-primary/15"
            />
            <circle
                cx="18"
                cy="18"
                :r="RADIUS"
                fill="none"
                stroke-width="3"
                stroke-linecap="round"
                class="stroke-primary transition-[stroke-dashoffset]"
                :stroke-dasharray="LENGTH"
                :stroke-dashoffset="LENGTH * (1 - percentage / 100)"
            />
        </svg>
        <span
            class="absolute inset-0 flex items-center justify-center font-semibold tabular-nums"
            :class="size === 'lg' ? 'text-sm' : 'text-[10px]'"
        >
            {{ percentage }}%
        </span>
    </div>
</template>
