<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';

withDefaults(
    defineProps<{
        /** Optional link to the workshop detail (admin show). */
        viewHref?: string;
        editHref: string;
        /** Wayfinder `*.form(...)` for the destroy/delete route. */
        deleteForm: Record<string, unknown>;
        dialogTitle: string;
        dialogDescription: string;
        confirmLabel?: string;
        /** Pest Browser: `@${deleteTriggerDataTest}` */
        deleteTriggerDataTest?: string;
        confirmDeleteDataTest?: string;
    }>(),
    {
        confirmLabel: 'Delete',
    },
);

const deleteOpen = ref(false);
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Button v-if="viewHref" variant="outline" size="sm" as-child>
            <Link :href="viewHref">View</Link>
        </Button>
        <Button variant="outline" size="sm" as-child>
            <Link :href="editHref">Edit</Link>
        </Button>
        <Button
            variant="destructive"
            size="sm"
            type="button"
            :data-test="deleteTriggerDataTest"
            @click="deleteOpen = true"
        >
            Delete
        </Button>
        <ConfirmDialog
            v-model:open="deleteOpen"
            :form-attributes="deleteForm"
            :title="dialogTitle"
            :description="dialogDescription"
            :confirm-label="confirmLabel"
            confirm-variant="destructive"
            :confirm-data-test="confirmDeleteDataTest"
        />
    </div>
</template>
