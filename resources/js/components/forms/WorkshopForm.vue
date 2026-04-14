<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { WorkshopCategoryOption } from '@/types/models';

const props = defineProps<{
    categories: WorkshopCategoryOption[];
    errors: Record<string, string>;
    defaultTitle?: string;
    defaultDescription?: string | null;
    defaultCategoryId?: number | null;
    defaultStartsAt?: string;
    defaultEndsAt?: string;
    defaultCapacity?: number | string;
}>();

const description = ref(props.defaultDescription ?? '');
</script>

<template>
    <div class="grid max-w-xl gap-6">
        <div class="grid gap-2">
            <Label for="workshop-title">Title</Label>
            <Input
                id="workshop-title"
                name="title"
                type="text"
                required
                maxlength="255"
                :default-value="defaultTitle ?? ''"
                autocomplete="off"
            />
            <InputError :message="errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="workshop-description">Description</Label>
            <textarea
                id="workshop-description"
                v-model="description"
                name="description"
                rows="4"
                placeholder="Optional details for participants"
                class="min-h-[96px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none selection:bg-primary selection:text-primary-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
            />
            <InputError :message="errors.description" />
        </div>

        <div class="grid gap-2">
            <Label for="workshop-category">Category</Label>
            <select
                id="workshop-category"
                name="workshop_category_id"
                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
            >
                <option value="">No category</option>
                <option
                    v-for="cat in categories"
                    :key="cat.id"
                    :value="String(cat.id)"
                    :selected="defaultCategoryId === cat.id"
                >
                    {{ cat.name }}
                </option>
            </select>
            <InputError :message="errors.workshop_category_id" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="workshop-starts">Starts at</Label>
                <Input
                    id="workshop-starts"
                    name="starts_at"
                    type="datetime-local"
                    required
                    :default-value="defaultStartsAt ?? ''"
                />
                <InputError :message="errors.starts_at" />
            </div>
            <div class="grid gap-2">
                <Label for="workshop-ends">Ends at</Label>
                <Input
                    id="workshop-ends"
                    name="ends_at"
                    type="datetime-local"
                    required
                    :default-value="defaultEndsAt ?? ''"
                />
                <InputError :message="errors.ends_at" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="workshop-capacity">Capacity</Label>
            <Input
                id="workshop-capacity"
                name="capacity"
                type="number"
                required
                min="1"
                step="1"
                :default-value="defaultCapacity ?? 10"
            />
            <InputError :message="errors.capacity" />
        </div>
    </div>
</template>
