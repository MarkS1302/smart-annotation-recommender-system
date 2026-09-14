<script setup lang="ts">
import { AlertDialogRoot } from 'reka-ui';

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <AlertDialogRoot data-slot="alert-dialog" v-bind="$attrs">
        <slot />
    </AlertDialogRoot>
</template>
