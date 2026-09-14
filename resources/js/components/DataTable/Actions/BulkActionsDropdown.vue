<script setup lang="ts">
import { MoreVertical } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

defineProps<{
    selectionCount?: number;
}>();
</script>

<template>
    <DropdownMenu :modal="false">
        <DropdownMenuTrigger as-child>
            <Button type="button" variant="outline">
                <MoreVertical class="mr-2 h-4 w-4" />
                Bulk actions
                <span v-if="selectionCount !== undefined">({{ selectionCount }})</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" class="w-56">
            <DropdownMenuLabel>Actions</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <slot />
            </DropdownMenuGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
