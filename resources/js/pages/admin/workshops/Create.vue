<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import WorkshopForm from '@/components/forms/WorkshopForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import adminWorkshops from '@/routes/admin/workshops';
import type { WorkshopCategoryOption } from '@/types/models';

defineProps<{
    categories: WorkshopCategoryOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Workshops',
                href: adminWorkshops.index.url(),
            },
            {
                title: 'Create',
                href: adminWorkshops.create.url(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Create workshop" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                title="Create workshop"
                description="Set the schedule, capacity, and optional category for a new session."
            />
            <Button variant="outline" as-child>
                <Link :href="adminWorkshops.index.url()">Back to list</Link>
            </Button>
        </div>

        <Form
            :action="adminWorkshops.store.url()"
            method="post"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <WorkshopForm :categories="categories" :errors="errors" />

            <div class="flex flex-wrap gap-2">
                <Button type="submit" :disabled="processing">
                    Create workshop
                </Button>
                <Button variant="secondary" type="button" as-child>
                    <Link :href="adminWorkshops.index.url()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
