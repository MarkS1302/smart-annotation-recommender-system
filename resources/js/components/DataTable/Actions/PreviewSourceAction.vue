<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    sourceType?: 'json' | 'sqlite';
    isOpen?: boolean;
    onClickHandler?: () => void;
}>();
</script>

<template>
    <Button
        v-if="props.sourceType === 'sqlite'"
        size="sm"
        variant="ghost"
        :aria-label="props.isOpen ? 'Hide SQLite preview' : 'Preview SQLite source'"
        :title="props.isOpen ? 'Hide preview' : 'Preview SQLite source'"
        @click="props.onClickHandler?.()"
    >
        <EyeOff v-if="props.isOpen" />
        <Eye v-else />
    </Button>
</template>
