<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, useSlots } from 'vue';
import FiltersBar from '@/components/tables/FiltersBar.vue';
import type { FilterBarField, TableColumn } from '@/components/tables/types';
import {
    formatTableCellValue,
    getNestedValue,
} from '@/components/tables/utils';

/** Slot name for a column: `cell-` + `field_name` with `.` replaced by `-` (e.g. `cell-timing_status`, `cell-category-name`). */
function cellSlotName(fieldName: string): string {
    return `cell-${fieldName.replace(/\./g, '-')}`;
}

/**
 * Cell override: scoped slot `cell-{field_name}` with `.` → `-` (e.g. `cell-timing_status`, `cell-category-name`).
 * Props: `{ row, column, value }`. Default slot content is `formatTableCellValue` for that cell.
 */
const props = withDefaults(
    defineProps<{
        columns: TableColumn[];
        rows: Record<string, unknown>[];
        filters: Record<string, unknown>;
        indexUrl: (query: Record<string, string>) => string;
        /** When true and `#row-actions` is provided, render the slot for `cast_type === 'actions'` cells. */
        showManageActions?: boolean;
        /** Hide the filter bar (e.g. static tables with no filterable columns). */
        showFilters?: boolean;
        emptyMessage?: string;
    }>(),
    {
        showManageActions: false,
        showFilters: true,
        emptyMessage: 'No rows match the current filters.',
    },
);

const slots = useSlots();

const filterBarFields = computed((): FilterBarField[] =>
    props.columns
        .filter((column) => column.filterable)
        .map((column) => ({
            param: column.filter_param ?? column.field_name,
            label: column.label,
            placeholder: column.placeholder,
            input_type: column.input_type,
            options: column.options,
        })),
);

const hasRowActionsSlot = computed(() => Boolean(slots['row-actions']));

function isSortedBy(column: TableColumn): boolean {
    return props.filters.sort === column.field_name;
}

function currentDirection(): 'asc' | 'desc' | null {
    const raw = props.filters.direction;

    if (raw === 'asc' || raw === 'desc') {
        return raw;
    }

    return null;
}

function buildQuery(
    overrides: Record<string, string | null>,
): Record<string, string> {
    const query: Record<string, string> = {};

    for (const [key, value] of Object.entries(props.filters)) {
        if (value === null || value === undefined || value === '') {
            continue;
        }

        query[key] = String(value);
    }

    for (const [key, value] of Object.entries(overrides)) {
        if (value === null || value === '') {
            delete query[key];
            continue;
        }

        query[key] = value;
    }

    return query;
}

function toggleSort(column: TableColumn): void {
    if (!column.sortable) {
        return;
    }

    const sortedByThis = isSortedBy(column);
    const dir = currentDirection();

    if (!sortedByThis) {
        router.get(
            props.indexUrl(
                buildQuery({
                    sort: column.field_name,
                    direction: String(column.default_sort ?? 'asc'),
                }),
            ),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );

        return;
    }

    if (dir === 'asc') {
        router.get(
            props.indexUrl(
                buildQuery({ sort: column.field_name, direction: 'desc' }),
            ),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );

        return;
    }

    router.get(
        props.indexUrl(buildQuery({ sort: null, direction: null })),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <FiltersBar
            v-if="showFilters && filterBarFields.length > 0"
            :fields="filterBarFields"
            :filters="filters"
            :index-url="indexUrl"
        />

        <div
            class="overflow-x-auto rounded-md border border-sidebar-border/60 dark:border-sidebar-border"
        >
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead
                    class="border-b border-sidebar-border/60 bg-muted/40 dark:border-sidebar-border"
                >
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.field_name"
                            class="px-3 py-2 font-medium whitespace-nowrap text-muted-foreground"
                        >
                            <button
                                v-if="col.sortable"
                                type="button"
                                class="inline-flex items-center gap-1 text-left hover:text-foreground"
                                @click="toggleSort(col)"
                            >
                                <span>{{ col.label }}</span>
                                <span v-if="isSortedBy(col)" class="text-xs">
                                    {{
                                        currentDirection() === 'desc'
                                            ? '▼'
                                            : '▲'
                                    }}
                                </span>
                            </button>
                            <span v-else>{{ col.label }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="rows.length === 0">
                        <td
                            :colspan="columns.length || 1"
                            class="px-3 py-8 text-center text-muted-foreground"
                        >
                            {{ emptyMessage }}
                        </td>
                    </tr>
                    <tr
                        v-for="row in rows"
                        v-else
                        :key="(row.id as number | string) ?? Math.random()"
                        class="border-b border-sidebar-border/40 odd:bg-muted/10 dark:border-sidebar-border/60"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.field_name"
                            class="px-3 py-2 align-top whitespace-nowrap"
                        >
                            <template
                                v-if="
                                    col.cast_type === 'actions' &&
                                    showManageActions &&
                                    hasRowActionsSlot
                                "
                            >
                                <slot name="row-actions" :row="row" />
                            </template>
                            <template v-else-if="col.cast_type === 'actions'" />
                            <template v-else>
                                <slot
                                    :name="cellSlotName(col.field_name)"
                                    :row="row"
                                    :column="col"
                                    :value="getNestedValue(row, col.field_name)"
                                >
                                    {{
                                        formatTableCellValue(
                                            getNestedValue(row, col.field_name),
                                            col,
                                        )
                                    }}
                                </slot>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
