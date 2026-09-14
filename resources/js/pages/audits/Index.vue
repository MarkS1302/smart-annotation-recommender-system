<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { isEmpty } from 'lodash';
import { computed, h } from 'vue';
import ViewAction from '@/components/DataTable/Actions/ViewAction.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import PermissionDeniedState from '@/components/PermissionDeniedState.vue';
import { Badge } from '@/components/ui/badge';
import { useDataTableColumn } from '@/composables/useDataTableColumn';
import { index, show } from '@/routes/audits';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { BreadcrumbItem } from '@/types';
import type { ResourceCollection } from '@/types/response/resource-collection';

type User = {
    id: number;
    name: string;
    email: string;
};

type Audit = {
    id: number;
    event: string;
    auditable_type: string;
    auditable_id: number | string;
    old_values: Record<string, unknown>;
    new_values: Record<string, unknown>;
    user: User | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
};

type Props = {
    filters: {
        search: string;
    };
    auditsResourceCollection: ResourceCollection<Audit>;
};

const props = defineProps<Props>();
const { canView } = usePermissions();

const columns = computed(() =>
    useDataTableColumn<Audit>(
        [
            {
                key: 'event',
                title: 'Event',
                cellFormatter: (_row, value) =>
                    h(
                        Badge,
                        {
                            variant: 'secondary',
                        },
                        {
                            default: () => [String(value)],
                        },
                    ),
            },
            {
                key: 'auditable_type',
                title: 'Target',
                cellFormatter: (row, value) =>
                    `${String(value)} #${row.auditable_id}`,
            },
            {
                key: 'user',
                title: 'User',
                cellFormatter: (_row, value) => {
                    if (isEmpty(value)) {
                        return 'system';
                    }

                    return `${value.name} (${value.email})`;
                },
            },
            {
                key: 'old_values',
                title: 'Old values',
                cellFormatter: (_row, value) => {
                    const entries = Object.entries(
                        value as Record<string, unknown>,
                    );

                    return entries.length
                        ? entries
                              .map(([key, val]) => `${key}: ${String(val)}`)
                              .join(', ')
                        : 'No values';
                },
            },
            {
                key: 'new_values',
                title: 'New values',
                cellFormatter: (_row, value) => {
                    const entries = Object.entries(
                        value as Record<string, unknown>,
                    );

                    return entries.length
                        ? entries
                              .map(([key, val]) => `${key}: ${String(val)}`)
                              .join(', ')
                        : 'No values';
                },
            },
            {
                key: 'created_at',
                title: 'Created',
                cellFormatter: (_row, value) => value,
            },
        ],
        canView('audits')
            ? [
                  {
                      component: ViewAction,
                      actionRoute: (audit) => show.url(audit),
                      hasPermission: () => canView('audits'),
                  },
              ]
            : [],
    ),
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Audits',
                href: index.url(),
            } satisfies BreadcrumbItem,
        ],
    },
});
</script>

<template>
    <Head title="Audits" />

    <div class="space-y-6">
        <PermissionDeniedState
            v-if="!canView('audits')"
            resource="audits"
            action="view"
            message="You do not have permission to view audits. Speak with an admin."
        />

        <template v-else>
            <DataTable
                title="Audits"
                description="Read-only audit history for tracked models."
                :columns="columns"
                :resource-collection="auditsResourceCollection"
                searchable-attribute="search"
                :initial-search="props.filters.search"
            />
        </template>
    </div>
</template>
