<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { Button } from '@/components/ui/button';

const {actionRoute, onClickHandler} = defineProps<{
    actionRoute?: string;
    onClickHandler?: () => void;
}>();

function handleClick(): void {
    if ((onClickHandler)) {
        onClickHandler();
        return;
    }

    if (actionRoute) {
        router.visit(actionRoute);
    }
}
</script>

<template>
    <Button size="sm" variant="ghost" @click="handleClick">
        <Eye class="h-4 w-4" />
    </Button>
</template>
