<script setup lang="ts">
import { Form, Head, Link, setLayoutProps } from "@inertiajs/vue3";
import { computed, ref, watch, watchEffect } from "vue";
import StatusBadge from "@/components/badge/StatusBadge.vue";
import ConfirmDialog from "@/components/dialogs/ConfirmDialog.vue";
import Heading from "@/components/Heading.vue";
import DescriptionList from "@/components/lists/DescriptionList.vue";
import type { DescriptionListItem } from "@/components/lists/DescriptionList.vue";
import Table from "@/components/tables/Table.vue";
import type { TableColumn } from "@/components/tables/types";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import adminWorkshops from "@/routes/admin/workshops";
import type { WorkshopShowPayload } from "@/types/models";

type AssignableUser = { id: number; name: string; email: string };

const props = defineProps<{
  workshop: WorkshopShowPayload;
  canAttachParticipants: boolean;
  participantList: Record<string, unknown>[];
  assignableUsers: AssignableUser[];
  participantTableColumns: TableColumn[];
  filters: Record<string, unknown>;
}>();

const tableRows = computed(() =>
  props.participantList.map((row) => ({ ...row } as Record<string, unknown>))
);

const workshopHasStarted = computed(
  () => new Date(props.workshop.starts_at).getTime() <= Date.now()
);

const remindOpen = ref(false);

const indexUrl = (query: Record<string, string>) => {
  void query;

  return adminWorkshops.show.url(props.workshop.id);
};

const removeOpen = ref(false);
const removeUserId = ref<number | null>(null);
const removeUserLabel = ref("");

watch(removeOpen, (open) => {
  if (!open) {
    removeUserId.value = null;
    removeUserLabel.value = "";
  }
});

function openRemoveParticipant(row: Record<string, unknown>): void {
  removeUserId.value = Number(row.user_id);
  const u = row.user as { name?: string } | undefined;
  removeUserLabel.value = u?.name ?? "this participant";
  removeOpen.value = true;
}

watchEffect(() => {
  setLayoutProps({
    breadcrumbs: [
      {
        title: "Workshops",
        href: adminWorkshops.index.url(),
      },
      {
        title: props.workshop.title,
        href: adminWorkshops.show.url(props.workshop.id),
      },
    ],
  });
});

function registrationStatusLabel(row: Record<string, unknown>): string {
  return String(row.registration_status_label ?? "");
}

function registrationStatusBadgeClass(row: Record<string, unknown>): string {
  return String(row.registration_status_badge_class ?? "");
}

function formatRange(startIso: string, endIso: string): string {
  const start = new Date(startIso);
  const end = new Date(endIso);

  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
    return `${startIso} — ${endIso}`;
  }

  return `${new Intl.DateTimeFormat(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(start)} — ${new Intl.DateTimeFormat(undefined, { timeStyle: "short" }).format(
    end
  )}`;
}

const workshopSummaryItems = computed((): DescriptionListItem[] => {
  const items: DescriptionListItem[] = [
    { term: "Category", value: props.workshop.category.name },
    { term: "Created by", value: props.workshop.creator.name },
    {
      term: "Schedule",
      value: formatRange(props.workshop.starts_at, props.workshop.ends_at),
      spanFull: true,
    },
    { term: "Capacity", value: `${props.workshop.enrollment} ` },
    { term: "Waiting list", value: props.workshop.waiting_list_registrations_count },
  ];

  if (props.workshop.description) {
    items.push({
      term: "Description",
      value: props.workshop.description,
      spanFull: true,
      preWrap: true,
    });
  }

  return items;
});
</script>

<template>
  <Head :title="`Workshop — ${workshop.title}`" />

  <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <Heading
        :title="workshop.title"
        description="Confirmed participants are listed first; waiting list follows in registration order."
      />
      <div class="flex flex-wrap gap-2">
        <Button
          type="button"
          variant="secondary"
          data-test="admin-workshop-send-reminders"
          :disabled="workshopHasStarted"
          @click="remindOpen = true"
        >
          Send reminders
        </Button>
        <Button variant="outline" as-child>
          <Link :href="adminWorkshops.edit.url(workshop.id)">Edit</Link>
        </Button>
        <Button variant="secondary" as-child>
          <Link :href="adminWorkshops.index.url()">Back to list</Link>
        </Button>
      </div>
    </div>

    <ConfirmDialog
      v-model:open="remindOpen"
      :form-attributes="adminWorkshops.reminders.dispatch.form(workshop.id)"
      title="Send reminders for this workshop?"
      description="Confirmed participants will receive the same email as the automated “tomorrow” reminder. Waiting-list users are not emailed."
      confirm-label="Send reminders"
      cancel-label="Cancel"
      confirm-variant="default"
      confirm-data-test="admin-workshop-confirm-send-reminders"
    />

    <section class="grid gap-3 lg:grid-cols-2">
      <DescriptionList :items="workshopSummaryItems" />

      <div
        class="space-y-3 rounded-md border border-sidebar-border/60 p-4 dark:border-sidebar-border"
      >
        <h2 class="text-lg font-semibold">Add participant</h2>
        <p class="text-sm text-muted-foreground">
          Register an employee who is not already on this workshop. There must be a free confirmed
          seat; overlapping sessions for that user are blocked.
        </p>
        <p
          v-if="!canAttachParticipants"
          class="text-sm text-muted-foreground"
        >
          This workshop is at capacity. Increase capacity in the edit form or remove a participant
          before adding someone else.
        </p>
        <Form
          v-else-if="assignableUsers.length > 0"
          v-bind="adminWorkshops.participants.attach.form(workshop.id)"
          class="flex flex-col gap-3 sm:flex-row sm:items-end"
          v-slot="{ processing, errors }"
        >
          <div class="flex min-w-0 flex-1 flex-col gap-2">
            <Label for="add-participant-user">Employee</Label>
            <select
              id="add-participant-user"
              data-test="workshop-show-add-participant-select"
              name="user_id"
              required
              class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
            >
              <option value="" disabled>Select user…</option>
              <option v-for="u in assignableUsers" :key="u.id" :value="u.id">
                {{ u.name }} ({{ u.email }})
              </option>
            </select>
            <p v-if="errors.user_id" class="text-sm text-destructive">
              {{ errors.user_id }}
            </p>
          </div>
          <Button
            type="submit"
            data-test="workshop-show-add-participant-submit"
            :disabled="processing"
          >
            Add
          </Button>
        </Form>
        <p v-else class="text-sm text-muted-foreground">
          Every employee is already registered for this workshop, or there are no employee
          accounts yet.
        </p>
      </div>
    </section>
    <section class="flex flex-col gap-3">
      <h2 class="text-lg font-semibold">Participants</h2>
      <Table
        :columns="participantTableColumns"
        :rows="tableRows"
        :filters="filters"
        :index-url="indexUrl"
        :show-filters="false"
        :show-manage-actions="true"
        empty-message="No one has registered for this workshop yet."
      >
        <template #cell-registration_status_label="{ row }">
          <StatusBadge
            :label="registrationStatusLabel(row)"
            :badge-class="registrationStatusBadgeClass(row)"
          />
        </template>
        <template #row-actions="{ row }">
          <Button
            variant="destructive"
            size="sm"
            type="button"
            @click="openRemoveParticipant(row)"
          >
            Remove
          </Button>
        </template>
      </Table>
    </section>

    <ConfirmDialog
      v-model:open="removeOpen"
      :form-attributes="adminWorkshops.participants.detach.form(workshop.id)"
      title="Remove participant"
      :description="`Remove ${removeUserLabel} from this workshop? Confirmed seats may promote someone from the waiting list.`"
      confirm-label="Remove"
      cancel-label="Cancel"
      confirm-variant="destructive"
    >
      <template #fields>
        <input
          v-if="removeUserId !== null"
          type="hidden"
          name="user_id"
          :value="removeUserId"
        />
      </template>
    </ConfirmDialog>
  </div>
</template>
