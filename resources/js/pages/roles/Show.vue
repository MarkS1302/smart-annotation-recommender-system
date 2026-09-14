<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PermissionDeniedState from '@/components/PermissionDeniedState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/roles';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { BreadcrumbItem } from '@/types';

type Permission = {
    id: number;
    name: string;
};

type Role = {
    id: number;
    name: string;
    permission_ids: number[];
    permissions: Permission[];
};

defineProps<{
    role: Role;
    permissions: Permission[];
}>();
const { canView } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Roles',
                href: index(),
            } satisfies BreadcrumbItem,
        ],
    },
});
</script>

<template>
    <Head :title="role.name" />

    <div class="space-y-6">
        <PermissionDeniedState
            v-if="!canView('roles')"
            resource="roles"
            action="view"
        />

        <template v-else>
        <div class="flex items-center justify-between gap-4">
            <Heading title="Role" description="Read-only role details." />

            <Button variant="secondary" as-child>
                <Link :href="index()">Back</Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Name</p>
                <p class="mt-1 font-medium">{{ role.name }}</p>
            </div>

            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Permissions</p>
                <p class="mt-1 font-medium">
                    {{ role.permissions.length }} assigned
                </p>
            </div>
        </div>

        <div class="rounded-xl border p-4">
            <p class="text-sm text-muted-foreground">Assigned permissions</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <Badge
                    v-for="permission in role.permissions"
                    :key="permission.id"
                    variant="secondary"
                >
                    {{ permission.name }}
                </Badge>
                <Badge v-if="!role.permissions.length" variant="outline">
                    No permissions
                </Badge>
            </div>
        </div>
        </template>
    </div>
</template>
