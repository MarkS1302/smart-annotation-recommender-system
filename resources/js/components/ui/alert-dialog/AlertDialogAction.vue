<script setup lang="ts">
import { AlertDialogAction } from 'reka-ui';
import { Button } from '@/components/ui/button';

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <AlertDialogAction data-slot="alert-dialog-action" as-child v-bind="$attrs">
        <Button variant="destructive">
            <slot />
        </Button>
    </AlertDialogAction>
</template>
