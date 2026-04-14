<script setup lang="ts">
import { Form } from "@inertiajs/vue3";
import { CalendarRange, Users } from "lucide-vue-next";
import { computed, ref } from "vue";
import StatusBadge from "@/components/badge/StatusBadge.vue";
import ConfirmDialog from "@/components/dialogs/ConfirmDialog.vue";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import appWorkshopRegistrations from "@/routes/app/workshops/registrations";
import type { WorkshopListItem } from "@/types/models";

const props = defineProps<{
  workshop: WorkshopListItem;
}>();

const cancelOpen = ref(false);

const isUpcoming = computed(() => props.workshop.timing_status === "upcoming");

const isFull = computed(
  () => props.workshop.confirmed_registrations_count >= props.workshop.capacity
);

const canRegister = computed(
  () => isUpcoming.value && !props.workshop.my_registration_status && !isFull.value
);

const canJoinWaitingList = computed(
  () => isUpcoming.value && !props.workshop.my_registration_status && isFull.value
);

const isRegistered = computed(() => props.workshop.my_registration_status !== null);

const isWaitingList = computed(
  () => props.workshop.my_registration_status === "waiting_list"
);

const cancelDialogDescription = computed(() =>
  isWaitingList.value
    ? "You will leave the waiting list. You can join again if the session still has a queue."
    : "You will lose your spot for this workshop. You can register again if places are still available."
);

function formatRange(startsAt: string, endsAt: string): string {
  const start = new Date(startsAt);
  const end = new Date(endsAt);

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  }).formatRange(start, end);
}
</script>

<template>
  <Card class="h-full border-sidebar-border/70 shadow-sm dark:border-sidebar-border">
    <CardHeader class="space-y-1">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <CardTitle class="text-lg leading-snug">
          {{ props.workshop.title }}
        </CardTitle>
        <span
          v-if="props.workshop.category?.name"
          class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
        >
          {{ props.workshop.category.name }}
        </span>
      </div>
      <CardDescription v-if="props.workshop.description" class="line-clamp-3">
        {{ props.workshop.description }}
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-3 text-sm text-muted-foreground">
      <div class="flex items-center gap-2">
        <CalendarRange class="size-4 shrink-0" aria-hidden="true" />
        <span>{{ formatRange(props.workshop.starts_at, props.workshop.ends_at) }}</span>
      </div>
      <div class="flex items-center gap-2">
        <Users class="size-4 shrink-0" aria-hidden="true" />
        <span>
          {{ props.workshop.confirmed_registrations_count }}/{{ props.workshop.capacity }}
        </span>
      </div>
      <StatusBadge
        :label="props.workshop.timing_status === 'upcoming' ? 'Upcoming' : 'Closed'"
        :badge-class="props.workshop.timing_status_badge_class"
      />
      <div
        v-if="props.workshop.my_registration_status === 'waiting_list'"
        class="rounded-md border border-amber-500/40 bg-amber-500/10 px-2 py-1.5 text-xs text-amber-950 dark:text-amber-100"
      >
        On waiting list
      </div>
      <div class="flex flex-wrap items-center gap-2 pt-1">
        <Form
          v-if="canRegister"
          v-bind="appWorkshopRegistrations.attach.form(props.workshop.id)"
          #default="{ processing }"
          :options="{ preserveScroll: true }"
        >
          <Button
            type="submit"
            size="sm"
            :disabled="processing"
            :data-test="`register-workshop-${props.workshop.id}`"
          >
            Register
          </Button>
        </Form>
        <Form
          v-else-if="canJoinWaitingList"
          v-bind="appWorkshopRegistrations.attach.form(props.workshop.id)"
          #default="{ processing }"
          :options="{ preserveScroll: true }"
        >
          <Button
            type="submit"
            size="sm"
            variant="secondary"
            :disabled="processing"
            :data-test="`join-waiting-list-${props.workshop.id}`"
          >
            Join waiting list
          </Button>
        </Form>
        <Button
          v-else-if="isRegistered"
          type="button"
          size="sm"
          variant="outline"
          @click="cancelOpen = true"
        >
          Cancel registration
        </Button>
      </div>
      <ConfirmDialog
        v-model:open="cancelOpen"
        :form-attributes="appWorkshopRegistrations.detach.form(props.workshop.id)"
        title="Cancel registration?"
        :description="cancelDialogDescription"
        confirm-label="Cancel registration"
        confirm-variant="destructive"
        :confirm-data-test="`confirm-cancel-registration-${props.workshop.id}`"
        :form-options="{ preserveScroll: true }"
      />
    </CardContent>
  </Card>
</template>
