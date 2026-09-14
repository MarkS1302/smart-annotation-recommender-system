<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, h } from 'vue';
import ViewAction from '@/components/DataTable/Actions/ViewAction.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { useDataTableColumn } from '@/composables/useDataTableColumn';
import { index, show } from '@/routes/ai-responses';
import type { ResourceCollection } from '@/types/response/resource-collection';

type AiResponse = Record<string, unknown> & {
    id: number;
    entity: string;
    record_id: string;
    status: 'pending' | 'success' | 'error';
    created_at: string | null;
};

defineProps<{
    aiResponses: ResourceCollection<AiResponse>;
}>();

const columns = computed(() =>
    useDataTableColumn<AiResponse>(
        [
            {
                key: 'id',
                title: '#',
                class: 'w-16',
            },
            {
                key: 'entity',
                title: 'Entity',
            },
            {
                key: 'status',
                title: 'Status',
                cellFormatter: (_row, value) =>
                    h(
                        Badge,
                        {
                            variant:
                                value === 'error'
                                    ? 'destructive'
                                    : value === 'pending'
                                      ? 'secondary'
                                      : 'default',
                        },
                        () => String(value),
                    ),
            },
            {
                key: 'created_at',
                title: 'Created',
            },
        ],
        [
            {
                component: ViewAction,
                actionRoute: (aiResponse) => show.url(aiResponse.id),
            },
        ],
    ),
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'AI responses',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="AI responses" />

    <DataTable
        title="AI responses"
        description="Stored AI request results."
        :columns="columns"
        :resource-collection="aiResponses"
    />
</template>
