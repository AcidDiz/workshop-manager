<script setup lang="ts">
export type DescriptionListItem = {
    term: string;
    value: string | number;
    /** Full width on `sm` and up (two-column grid). */
    spanFull?: boolean;
    /** Preserve line breaks in the description value. */
    preWrap?: boolean;
};

defineProps<{
    items: DescriptionListItem[];
}>();
</script>

<template>
    <dl
        class="grid gap-3 rounded-md border border-sidebar-border/60 bg-muted/20 p-4 text-sm sm:grid-cols-2 dark:border-sidebar-border"
    >
        <div
            v-for="item in items"
            :key="item.term"
            :class="item.spanFull ? 'sm:col-span-2' : undefined"
        >
            <dt class="text-muted-foreground">{{ item.term }}</dt>
            <dd
                class="font-medium"
                :class="item.preWrap ? 'whitespace-pre-wrap' : undefined"
            >
                {{ item.value }}
            </dd>
        </div>
    </dl>
</template>
