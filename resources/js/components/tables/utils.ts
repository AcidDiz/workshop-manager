import type { TableColumn } from '@/components/tables/types';

const toSnakeCase = (value: string): string =>
    value.replace(/([a-z])([A-Z])/g, '$1_$2').toLowerCase();

const toCamelCase = (value: string): string =>
    value.replace(/_([a-z])/g, (_, char: string) => char.toUpperCase());

export function getNestedValue(
    row: Record<string, unknown>,
    fieldName: string,
): unknown {
    return fieldName.split('.').reduce((value: unknown, key: string): unknown => {
        if (value === null || value === undefined || typeof value !== 'object') {
            return null;
        }

        const record = value as Record<string, unknown>;
        const bySameKey = record[key];
        const bySnake = record[toSnakeCase(key)];
        const byCamel = record[toCamelCase(key)];

        return bySameKey ?? bySnake ?? byCamel ?? null;
    }, row);
}

export function formatTableCellValue(value: unknown, column: TableColumn): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (column.cast_type === 'workshop_timing') {
        return value === 'upcoming' ? 'Upcoming' : 'Closed';
    }

    if (column.cast_type.startsWith('datetime')) {
        const date = new Date(String(value));

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date);
    }

    return String(value);
}

/** @deprecated Use formatTableCellValue */
export const formatWorkshopCellValue = formatTableCellValue;
