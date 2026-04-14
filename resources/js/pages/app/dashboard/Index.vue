<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatCard from '@/components/cards/StatCard.vue';
import WorkshopCard from '@/components/cards/WorkshopCard.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import app from '@/routes/app';
import appWorkshops from '@/routes/app/workshops';
import type { WorkshopListItem } from '@/types/models';

const props = defineProps<{
    registrationSummary: {
        confirmed: number;
        waiting_list: number;
    };
    upcomingRegistrations: WorkshopListItem[];
    completedWorkshops: WorkshopListItem[];
}>();

const nf = new Intl.NumberFormat(undefined);

const formatInt = (n: number) => nf.format(n);

const confirmedLabel = computed(() =>
    formatInt(props.registrationSummary.confirmed),
);
const waitingLabel = computed(() =>
    formatInt(props.registrationSummary.waiting_list),
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: app.dashboard.url(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4"
        data-test="app-workshop-dashboard"
    >
        <Heading
            title="Dashboard"
            description="Your workshop registrations at a glance. Open Workshops to browse sessions and join or leave a list."
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:max-w-2xl">
            <StatCard label="Confirmed registrations" :value="confirmedLabel" />
            <StatCard label="On waiting list" :value="waitingLabel" />
        </div>

        <div>
            <Button as-child variant="secondary">
                <Link :href="appWorkshops.index.url()"> Browse workshops </Link>
            </Button>
        </div>

        <section class="flex flex-col gap-3">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">
                        Your upcoming workshops
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Sessions you are confirmed for or currently waiting on.
                    </p>
                </div>
            </div>

            <ul
                v-if="upcomingRegistrations.length"
                class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <li
                    v-for="workshop in upcomingRegistrations"
                    :key="workshop.id"
                >
                    <WorkshopCard :workshop="workshop" />
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">
                You are not registered for any upcoming workshops yet.
            </p>
        </section>

        <section class="flex flex-col gap-3">
            <div>
                <h2 class="text-lg font-semibold">Completed workshops</h2>
                <p class="text-sm text-muted-foreground">
                    Workshops you completed in the past as a confirmed
                    participant.
                </p>
            </div>

            <ul
                v-if="completedWorkshops.length"
                class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <li v-for="workshop in completedWorkshops" :key="workshop.id">
                    <WorkshopCard :workshop="workshop" />
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">
                No completed workshops yet.
            </p>
        </section>
    </div>
</template>
