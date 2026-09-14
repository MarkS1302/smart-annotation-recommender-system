<script setup lang="ts">
import { AlertDialogDescription } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps({
    class: {
        type: [String, Array, Object],
        default: '',
    },
});
</script>

<template>
    <AlertDialogDescription
        data-slot="alert-dialog-description"
        :class="cn('text-sm text-muted-foreground', props.class)"
    >
        <slot />
    </AlertDialogDescription>
</template>
