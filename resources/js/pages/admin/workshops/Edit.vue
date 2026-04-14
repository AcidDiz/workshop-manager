<script setup lang="ts">
import { Form, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import WorkshopForm from '@/components/forms/WorkshopForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import adminWorkshops from '@/routes/admin/workshops';
import type {
    WorkshopCategoryOption,
    WorkshopFormPayload,
} from '@/types/models';

const props = defineProps<{
    workshop: WorkshopFormPayload;
    categories: WorkshopCategoryOption[];
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Workshops',
                href: adminWorkshops.index.url(),
            },
            {
                title: 'Edit',
                href: adminWorkshops.edit.url(props.workshop.id),
            },
        ],
    });
});
</script>

<template>
    <Head :title="`Edit — ${workshop.title}`" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                title="Edit workshop"
                :description="`Update “${workshop.title}”. Past sessions can still be edited (e.g. corrections).`"
            />
            <Button variant="outline" as-child>
                <Link :href="adminWorkshops.index.url()">Back to list</Link>
            </Button>
        </div>

        <Form
            :action="adminWorkshops.update.url(workshop.id)"
            method="put"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <WorkshopForm
                :categories="categories"
                :errors="errors"
                :default-title="workshop.title"
                :default-description="workshop.description"
                :default-category-id="workshop.workshop_category_id"
                :default-starts-at="workshop.starts_at"
                :default-ends-at="workshop.ends_at"
                :default-capacity="workshop.capacity"
            />

            <div class="flex flex-wrap gap-2">
                <Button type="submit" :disabled="processing">
                    Save changes
                </Button>
                <Button variant="secondary" type="button" as-child>
                    <Link :href="adminWorkshops.index.url()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
