<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form/index';

const {
    class: formItemClass,
    description = '',
    required = false,
} = defineProps<{
    name: string;
    label: string;
    description?: string;
    required?: boolean;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <FormField v-slot="slotProps" :name="name">
        <FormItem :class="formItemClass">
            <FormLabel :class="{ required: required }">
                {{ label }}
            </FormLabel>

            <FormControl>
                <slot v-bind="slotProps" />
            </FormControl>

            <FormDescription v-if="description">
                {{ description }}
            </FormDescription>

            <FormMessage />
        </FormItem>
    </FormField>
</template>
