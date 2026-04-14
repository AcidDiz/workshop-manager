<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarRange, Mail, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import adminWorkshops from '@/routes/admin/workshops';
import type { AdminNextUpcomingWorkshop } from '@/types/dashboard';

const props = defineProps<{
    workshop: AdminNextUpcomingWorkshop | null;
}>();

const remindOpen = ref(false);

const scheduleLabel = computed(() => {
    if (!props.workshop) {
        return '';
    }

    const start = new Date(props.workshop.starts_at);
    const end = new Date(props.workshop.ends_at);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return `${props.workshop.starts_at} — ${props.workshop.ends_at}`;
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).formatRange(start, end);
});
</script>

<template>
    <div
        class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm dark:border-sidebar-border"
    >
        <p class="text-sm text-muted-foreground">Next upcoming workshop</p>
        <template v-if="workshop">
            <p class="mt-2 leading-snug font-medium">{{ workshop.title }}</p>

            <div class="mt-3 space-y-2 text-sm text-muted-foreground">
                <div class="flex items-start gap-2">
                    <CalendarRange class="mt-0.5 size-4" />
                    <span>{{ scheduleLabel }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <Users class="size-4" />
                    <span>
                        {{ workshop.confirmed_registrations_count }}/{{
                            workshop.capacity
                        }}
                        confirmed seats
                    </span>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <Button type="button" size="sm" @click="remindOpen = true">
                    <Mail class="size-4" />
                    Send reminder
                </Button>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="adminWorkshops.show.url(workshop.id)"
                        >Open workshop</Link
                    >
                </Button>
            </div>

            <ConfirmDialog
                v-model:open="remindOpen"
                :form-attributes="
                    adminWorkshops.reminders.dispatch.form(workshop.id)
                "
                title="Send reminder for the next workshop?"
                description="Confirmed participants will receive the workshop reminder email now. Waiting-list users are not emailed."
                confirm-label="Send reminder"
                cancel-label="Cancel"
                confirm-variant="default"
            />
        </template>

        <p v-else class="mt-2 text-sm text-muted-foreground">
            No upcoming workshops are scheduled yet.
        </p>
    </div>
</template>
