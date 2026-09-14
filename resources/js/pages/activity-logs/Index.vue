<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, h } from 'vue';
import ViewAction from '@/components/DataTable/Actions/ViewAction.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import PermissionDeniedState from '@/components/PermissionDeniedState.vue';
import { Badge } from '@/components/ui/badge';
import { useDataTableColumn } from '@/composables/useDataTableColumn';
import { index, show } from '@/routes/activity-logs';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { BreadcrumbItem } from '@/types';
import type { ResourceCollection } from '@/types/response/resource-collection';

type User = {
    id: number;
    name: string;
    email: string;
};

type ActivityLog = {
    id: number;
    log_name: string | null;
    description: string;
    subject_type: string | null;
    subject_id: number | string | null;
    causer: User | null;
    event: string | null;
    properties: Record<string, unknown>;
    created_at: string;
};

type Props = {
    filters: {
        search: string;
    };
    activityLogs: ResourceCollection<ActivityLog>;
};

const props = defineProps<Props>();
const { isSuperAdmin } = usePermissions();

const columns = computed(() =>
    useDataTableColumn<ActivityLog>(
        [
            {
                key: 'log_name',
                title: 'Log',
                cellFormatter: (_row, value) =>
                    h(
                        Badge,
                        {
                            variant: 'secondary',
                        },
                        {
                            default: () => [String(value ?? 'activity')],
                        },
                    ),
            },
            {
                key: 'description',
                title: 'Description',
            },
            {
                key: 'subject_type',
                title: 'Subject',
                cellFormatter: (row, value) =>
                    `${String(value ?? 'unknown')} #${row.subject_id ?? '—'}`,
            },
            {
                key: 'properties',
                title: 'Properties',
                cellFormatter: (_row, value) => {
                    const keys = Object.keys(value as Record<string, unknown>);

                    return keys.length ? keys.join(', ') : 'No properties';
                },
            },
            {
                key: 'created_at',
                title: 'Created',
                cellFormatter: (_row, value) => value,
            },
        ],
        [
            {
                component: ViewAction,
                actionRoute: (activity) => show.url(activity),
                hasPermission: () => isSuperAdmin(),
            },
        ],
    ),
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Activity logs',
                href: index.url(),
            } satisfies BreadcrumbItem,
        ],
    },
});
</script>

<template>
    <Head title="Activity logs" />

    <div class="space-y-6">
        <PermissionDeniedState
            v-if="!isSuperAdmin()"
            resource="activity logs"
            action="view"
            message="Only SuperAdmin can view activity logs. Speak with an admin."
        />

        <template v-else>
            <DataTable
                title="Activity logs"
                description="Read-only activity log history."
                :columns="columns"
                :resource-collection="activityLogs"
                searchable-attribute="search"
                :initial-search="props.filters.search"
            />
        </template>
    </div>
</template>
