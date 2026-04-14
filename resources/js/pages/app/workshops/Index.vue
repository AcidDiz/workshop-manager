<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkshopCard from '@/components/cards/WorkshopCard.vue';
import Heading from '@/components/Heading.vue';
import FiltersBar from '@/components/tables/FiltersBar.vue';
import type { FilterBarField } from '@/components/tables/types';
import appWorkshops from '@/routes/app/workshops';
import type { WorkshopListItem } from '@/types/models';

defineProps<{
    workshopList: WorkshopListItem[];
    filters: Record<string, unknown>;
    cardFilterFields: FilterBarField[];
}>();

const indexUrl = (query: Record<string, string>) =>
    appWorkshops.index.url({ query });

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Workshops',
                href: appWorkshops.index.url(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Workshops" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <Heading
            title="Workshops"
            description="Browse sessions by category, date, and timing. Upcoming rows are listed first."
        />

        <FiltersBar
            :fields="cardFilterFields"
            :filters="filters"
            :index-url="indexUrl"
        />

        <ul
            v-if="workshopList.length"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <li v-for="w in workshopList" :key="w.id">
                <WorkshopCard :workshop="w" />
            </li>
        </ul>

        <p v-else class="text-sm text-muted-foreground">
            No workshops match the current filters.
        </p>
    </div>
</template>
