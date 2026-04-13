<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import StatCard from "@/components/cards/StatCard.vue";
import Heading from "@/components/Heading.vue";
import { Button } from "@/components/ui/button";
import app from "@/routes/app";
import appWorkshops from "@/routes/app/workshops";

const props = defineProps<{
  registrationSummary: {
    confirmed: number;
    waiting_list: number;
  };
}>();

const nf = new Intl.NumberFormat(undefined);

const formatInt = (n: number) => nf.format(n);

const confirmedLabel = computed(() => formatInt(props.registrationSummary.confirmed));
const waitingLabel = computed(() => formatInt(props.registrationSummary.waiting_list));

defineOptions({
  layout: {
    breadcrumbs: [
      {
        title: "Dashboard",
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
  </div>
</template>
