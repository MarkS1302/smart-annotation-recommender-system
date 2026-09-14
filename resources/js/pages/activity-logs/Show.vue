<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import PermissionDeniedState from '@/components/PermissionDeniedState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/activity-logs';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { BreadcrumbItem } from '@/types';

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

defineProps<{
    activityLog: ActivityLog;
}>();
const { isSuperAdmin } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Activity logs',
                href: index(),
            } satisfies BreadcrumbItem,
        ],
    },
});

function prettyJson(values: Record<string, unknown>): string {
    return JSON.stringify(values, null, 2);
}
</script>

<template>
    <Head title="Activity log" />
    <div class="space-y-6">
        <PermissionDeniedState
            v-if="!isSuperAdmin()"
            resource="activity logs"
            action="view"
            message="Only SuperAdmin can view activity logs. Speak with an admin."
        />

        <template v-else>
            <div class="flex items-center justify-between gap-4">
                <Heading
                    title="Activity log"
                    description="Read-only activity details."
                />

                <Button variant="secondary" as-child>
                    <Link :href="index()">Back</Link>
                </Button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Log name</p>
                    <Badge class="mt-2" variant="secondary">
                        {{ activityLog.log_name ?? 'activity' }}
                    </Badge>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Description</p>
                    <p class="mt-1 font-medium">
                        {{ activityLog.description }}
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Subject</p>
                    <p class="mt-1 font-medium">
                        {{ activityLog.subject_type ?? 'unknown' }} #{{
                            activityLog.subject_id ?? '—'
                        }}
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Causer</p>
                    <p class="mt-1 font-medium">
                        {{
                            activityLog.causer
                                ? `${activityLog.causer.name} (${activityLog.causer.email})`
                                : 'system'
                        }}
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Event</p>
                    <p class="mt-1 font-medium">
                        {{ activityLog.event ?? '—' }}
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Created</p>
                    <p class="mt-1 font-medium">
                        {{ activityLog.created_at }}
                    </p>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <p class="text-sm font-medium">Properties</p>
                <pre
                    class="mt-3 overflow-x-auto rounded-lg bg-muted p-4 text-xs"
                    >{{ prettyJson(activityLog.properties) }}</pre
                >
            </div>
        </template>
    </div>
</template>
