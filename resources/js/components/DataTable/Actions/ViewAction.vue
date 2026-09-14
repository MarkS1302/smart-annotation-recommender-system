<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { isFunction } from 'lodash';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    actionRoute?: string;
    onClickHandler?: () => void;
}>();

function handleClick(): void {
    if (isFunction(props.onClickHandler)) {
        props.onClickHandler();
        return;
    }

    if (props.actionRoute) {
        router.visit(props.actionRoute);
    }
}
</script>

<template>
    <Button size="sm" variant="ghost" @click="handleClick">
        <Eye class="h-4 w-4" />
    </Button>
</template>
