<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import type { FilterBarField } from '@/components/tables/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    fields: FilterBarField[];
    filters: Record<string, unknown>;
    indexUrl: (query: Record<string, string>) => string;
}>();

const local = reactive<Record<string, string>>({});

function paramKey(field: FilterBarField): string {
    return field.param;
}

function syncFromProps(): void {
    for (const key of Object.keys(local)) {
        delete local[key];
    }

    for (const field of props.fields) {
        const key = paramKey(field);
        const raw = props.filters[key];
        local[key] =
            raw === null || raw === undefined ? '' : String(raw);
    }
}

watch(
    () => [props.filters, props.fields],
    () => {
        syncFromProps();
    },
    { immediate: true, deep: true },
);

function buildQuery(): Record<string, string> {
    const query: Record<string, string> = {};

    const rawSort = props.filters.sort;
    const rawDirection = props.filters.direction;

    if (rawSort !== null && rawSort !== undefined && rawSort !== '') {
        query.sort = String(rawSort);
    }

    if (rawDirection !== null && rawDirection !== undefined && rawDirection !== '') {
        query.direction = String(rawDirection);
    }

    for (const [key, value] of Object.entries(local)) {
        if (value !== '') {
            query[key] = value;
        }
    }

    return query;
}

function apply(): void {
    router.get(props.indexUrl(buildQuery()), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function reset(): void {
    router.get(
        props.indexUrl({}),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <form
        class="grid gap-4 border border-sidebar-border/60 bg-muted/20 p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-sidebar-border"
        @submit.prevent="apply"
    >
        <div
            v-for="field in fields"
            :key="paramKey(field)"
            class="space-y-1.5"
        >
            <Label :for="`filters-${paramKey(field)}`">{{ field.label }}</Label>

            <Input
                v-if="
                    field.input_type === 'text' ||
                    field.input_type === 'date' ||
                    !field.input_type
                "
                :id="`filters-${paramKey(field)}`"
                :type="field.input_type === 'date' ? 'date' : 'text'"
                :placeholder="field.placeholder ?? ''"
                v-model="local[paramKey(field)]"
                class="h-9"
                @keydown.enter.prevent="apply"
            />

            <select
                v-else-if="field.input_type === 'select'"
                :id="`filters-${paramKey(field)}`"
                v-model="local[paramKey(field)]"
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <option value="">
                    {{ field.placeholder ?? 'Select…' }}
                </option>
                <option
                    v-for="opt in field.options ?? []"
                    :key="String(opt.value)"
                    :value="String(opt.value)"
                >
                    {{ opt.label }}
                </option>
            </select>
        </div>

        <div class="flex flex-wrap items-end gap-2 sm:col-span-2 lg:col-span-4">
            <Button type="submit" size="sm">Apply filters</Button>
            <Button type="button" variant="outline" size="sm" @click="reset">
                Reset
            </Button>
        </div>
    </form>
</template>
