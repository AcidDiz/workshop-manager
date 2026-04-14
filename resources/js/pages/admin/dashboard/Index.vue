<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import NextUpcomingWorkshopCard from "@/components/cards/NextUpcomingWorkshopCard.vue";
import StatCard from "@/components/cards/StatCard.vue";
import Heading from "@/components/Heading.vue";
import { useEchoPrivateEvent } from "@/composables/useEchoPrivateEvent";
import { dashboard } from "@/routes";
import admin from "@/routes/admin";
import type { AdminWorkshopStatistics } from "@/types/dashboard";

const props = defineProps<{
  statistics: AdminWorkshopStatistics;
}>();

const statistics = ref<AdminWorkshopStatistics>({ ...props.statistics });

watch(
  () => props.statistics,
  (next) => {
    statistics.value = { ...next };
  },
  { deep: true }
);

const nf = new Intl.NumberFormat(undefined);

const formatInt = (n: number) => nf.format(n);

const lastUpdatedLabel = computed(() => {
  const t = Date.parse(statistics.value.generated_at);

  if (Number.isNaN(t)) {
    return "—";
  }

  return new Date(t).toLocaleString();
});

useEchoPrivateEvent<{
  "statistics.updated": { statistics?: AdminWorkshopStatistics };
}>({
  channel: "admin.workshop-statistics",
  listeners: {
    "statistics.updated": (payload) => {
      if (payload.statistics) {
        statistics.value = { ...payload.statistics };
      }
    },
  },
});

defineOptions({
  layout: {
    breadcrumbs: [
      {
        title: "Dashboard",
        href: dashboard(),
      },
      {
        title: "Workshop overview",
        href: admin.dashboard.url(),
      },
    ],
  },
});
</script>

<template>
  <Head title="Workshop overview" />

  <div
    class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4"
    data-test="admin-workshop-dashboard"
  >
    <div class="flex flex-col gap-2">
      <Heading
        title="Workshop overview"
        description="Aggregated workshop and registration metrics. When Laravel Reverb is configured, figures update live over WebSockets while you stay on this page."
      />
      <p class="text-sm text-muted-foreground" data-test="admin-dashboard-last-updated">
        Last updated: {{ lastUpdatedLabel }}
      </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <StatCard
        label="Workshops (total)"
        :value="formatInt(statistics.workshops.total)"
      />
      <StatCard
        label="Upcoming workshops"
        :value="formatInt(statistics.workshops.upcoming)"
      />
      <StatCard label="Past workshops" :value="formatInt(statistics.workshops.closed)" />
      <StatCard
        label="Confirmed registrations"
        :value="formatInt(statistics.registrations.confirmed)"
      />
      <StatCard
        label="Waiting list"
        :value="formatInt(statistics.registrations.waiting_list)"
      />
      <StatCard
        label="Registrations (all statuses)"
        :value="formatInt(statistics.registrations.total)"
      />
    </div>

    <div class="max-w-md">
      <NextUpcomingWorkshopCard :workshop="statistics.next_upcoming_workshop" />
    </div>
  </div>
</template>
