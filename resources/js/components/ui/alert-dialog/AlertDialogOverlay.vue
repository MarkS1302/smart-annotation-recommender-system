<script setup lang="ts">
import { AlertDialogOverlay } from 'reka-ui';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    class: {
        type: [String, Array, Object],
        default: '',
    },
});
</script>

<template>
    <AlertDialogOverlay
        data-slot="alert-dialog-overlay"
        v-bind="$attrs"
        :class="cn('fixed inset-0 z-50 bg-black/80', props.class)"
    />
</template>
