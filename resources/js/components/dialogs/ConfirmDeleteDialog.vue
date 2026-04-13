<script setup lang="ts">
import { Form } from "@inertiajs/vue3";
import { computed } from "vue";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

const open = defineModel<boolean>("open", { default: false });

const props = withDefaults(
  defineProps<{
    /** Wayfinder `*.form(...)` spread (action + method). */
    formAttributes: Record<string, unknown>;
    title: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    confirmDataTest?: string;
    formOptions?: Record<string, unknown>;
  }>(),
  {
    confirmLabel: "Delete",
    cancelLabel: "Cancel",
    formOptions: () => ({}),
  }
);

const mergedFormOptions = computed(() => ({
  preserveScroll: true,
  ...props.formOptions,
}));
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent>
      <Form
        v-bind="formAttributes"
        :options="mergedFormOptions"
        class="space-y-4"
        v-slot="{ processing }"
        @success="open = false"
      >
        <DialogHeader>
          <DialogTitle>{{ title }}</DialogTitle>
          <DialogDescription>
            {{ description }}
          </DialogDescription>
        </DialogHeader>

        <slot name="fields" />

        <DialogFooter>
          <DialogClose as-child>
            <Button type="button" variant="secondary">
              {{ cancelLabel }}
            </Button>
          </DialogClose>
          <Button
            type="submit"
            variant="destructive"
            :disabled="processing"
            :data-test="confirmDataTest"
          >
            {{ confirmLabel }}
          </Button>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>
</template>
