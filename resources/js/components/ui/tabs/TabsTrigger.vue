<script setup lang="ts">
import type { TabsTriggerProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { TabsTrigger } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<TabsTriggerProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <TabsTrigger
        data-slot="tabs-trigger"
        v-bind="delegatedProps"
        :class="
            cn(
                'data-[state=active]:bg-background data-[state=active]:text-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:outline-ring inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2 text-sm font-medium whitespace-nowrap transition-all disabled:pointer-events-none disabled:opacity-50 data-[state=active]:shadow-sm',
                props.class,
            )
        "
    >
        <slot />
    </TabsTrigger>
</template>
