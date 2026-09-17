<script setup lang="ts">
import { Download } from '@lucide/vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    sourceType?: 'json' | 'sqlite';
    actionRoute?: string;
}>();

function downloadSource(): void {
    if (props.actionRoute) {
        window.location.assign(props.actionRoute);
    }
}
</script>

<template>
    <Button
        v-if="props.sourceType === 'sqlite'"
        size="sm"
        variant="ghost"
        aria-label="Download SQLite source"
        title="Download SQLite source"
        @click="downloadSource"
    >
        <Download />
    </Button>
</template>
