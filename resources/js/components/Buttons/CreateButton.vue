<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { isFunction } from 'lodash';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    label?: string;
    route?: string;
    onClick?: () => void;
}>();

function handleClick(): void {
    if (isFunction(props.onClick)) {
        props.onClick();

        return;
    }

    if (props.route) {
        router.visit(props.route);
    }
}
</script>

<template>
    <Button @click="handleClick">
        {{ label ?? 'Create' }}
    </Button>
</template>
