<script setup lang="ts">
import { AlertDialogContent, AlertDialogPortal } from 'reka-ui';
import { cn } from '@/lib/utils';
import AlertDialogOverlay from './AlertDialogOverlay.vue';

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
    <AlertDialogPortal>
        <AlertDialogOverlay />
        <AlertDialogContent
            data-slot="alert-dialog-content"
            v-bind="$attrs"
            :class="
                cn(
                    'bg-background fixed top-[50%] left-[50%] z-50 grid w-full max-w-[calc(100%-2rem)] translate-x-[-50%] translate-y-[-50%] gap-4 rounded-lg border p-6 shadow-lg duration-200 sm:max-w-lg',
                    props.class,
                )
            "
        >
            <slot />
        </AlertDialogContent>
    </AlertDialogPortal>
</template>
