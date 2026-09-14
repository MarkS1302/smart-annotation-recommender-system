<script setup lang="ts">
import { AlertDialogTitle } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps({
    class: {
        type: [String, Array, Object],
        default: '',
    },
});
</script>

<template>
    <AlertDialogTitle
        data-slot="alert-dialog-title"
        :class="cn('text-lg font-semibold', props.class)"
    >
        <slot />
    </AlertDialogTitle>
</template>
