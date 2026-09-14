<script setup lang="ts">
import { AlertDialogCancel } from 'reka-ui';
import { Button } from '@/components/ui/button';

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <AlertDialogCancel data-slot="alert-dialog-cancel" as-child v-bind="$attrs">
        <Button variant="secondary">
            <slot />
        </Button>
    </AlertDialogCancel>
</template>
