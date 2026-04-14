<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
}>();
</script>

<template>
    <div
        class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm dark:border-sidebar-border"
    >
        <p class="text-sm text-muted-foreground">{{ label }}</p>
        <p class="mt-1 text-2xl font-semibold tracking-tight">{{ value }}</p>
    </div>
</template>
