<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import PermissionDeniedState from '@/components/PermissionDeniedState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/audits';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { BreadcrumbItem } from '@/types';
import type { Audit } from '@/types/models/audits';
import type { Resource } from '@/types/response/resource';

defineProps<{
    audit: Resource<Audit>;
}>();
const { canView } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Audits',
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
    <Head title="Audit" />

    <div class="space-y-6">
        <PermissionDeniedState
            v-if="!canView('audits')"
            resource="audits"
            action="view"
            message="You do not have permission to view audits. Speak with an admin."
        />

        <template v-else>
            <div class="flex items-center justify-between gap-4">
                <Heading title="Audit" description="Read-only audit details." />

                <Button variant="secondary" as-child>
                    <Link :href="index()">Back</Link>
                </Button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Event</p>
                    <Badge class="mt-2" variant="secondary">{{
                        audit.data.event
                    }}</Badge>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Target</p>
                    <p class="mt-1 font-medium">
                        {{ audit.data.auditable_type }} #{{
                            audit.data.auditable_id
                        }}
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">User</p>
                    <p class="mt-1 font-medium">
                        {{
                            audit.data.user
                                ? `${audit.data.user.name} (${audit.data.user.email})`
                                : 'system'
                        }}
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Created</p>
                    <p class="mt-1 font-medium">
                        {{ audit.data.created_at }}
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">IP address</p>
                    <p class="mt-1 font-medium">
                        {{ audit.data.ip_address ?? '—' }}
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">User agent</p>
                    <p class="mt-1 text-sm font-medium break-words">
                        {{ audit.data.user_agent ?? '—' }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border p-4">
                    <p class="text-sm font-medium">Old values</p>
                    <pre
                        class="mt-3 overflow-x-auto rounded-lg bg-muted p-4 text-xs"
                        >{{ prettyJson(audit.data.old_values) }}</pre
                    >
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm font-medium">New values</p>
                    <pre
                        class="mt-3 overflow-x-auto rounded-lg bg-muted p-4 text-xs"
                        >{{ prettyJson(audit.data.new_values) }}</pre
                    >
                </div>
            </div>
        </template>
    </div>
</template>
