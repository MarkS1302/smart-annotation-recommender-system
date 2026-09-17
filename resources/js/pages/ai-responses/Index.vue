<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, h } from 'vue';
import ViewAction from '@/components/DataTable/Actions/ViewAction.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import PermissionDeniedState from '@/components/PermissionDeniedState.vue';
import { Badge } from '@/components/ui/badge';
import { useDataTableColumn } from '@/composables/useDataTableColumn';
import { index, show } from '@/routes/ai-responses';
import { usePermissions } from '@/shared/hooks/use-permissions';
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

const { canView } = usePermissions();

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
                hasPermission: () => canView('ai-responses'),
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
    <div class="space-y-6">
        <Head title="AI responses" />

        <PermissionDeniedState
            v-if="!canView('ai-responses')"
            resource="AI responses"
            action="view"
        />

        <DataTable
            v-else
            title="AI responses"
            description="Stored AI request results."
            :columns="columns"
            :resource-collection="aiResponses"
        />
    </div>
</template>
