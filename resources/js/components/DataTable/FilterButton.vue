<script setup lang="ts">
import { Filter } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

defineProps<{
    filtersLength: number;
}>();
</script>

<template>
    <div class="relative">
        <Button variant="outline" size="icon">
            <Filter />
        </Button>
        <Badge
            v-if="filtersLength > 0"
            class="absolute right-0 top-0 -translate-y-1/2 translate-x-1/2 rounded-full bg-destructive"
        >
            {{ filtersLength }}
        </Badge>
    </div>
</template>
