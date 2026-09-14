<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import Heading from '@/components/Heading.vue';
import PermissionDeniedState from '@/components/PermissionDeniedState.vue';
import { index } from '@/routes/users';
import { usePermissions } from '@/shared/hooks/use-permissions';
import { formatDateTime } from '@/lib/date';
import type { BreadcrumbItem } from '@/types';

type Role = {
    id: number;
    name: string;
};

type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    roles: Role[];
};

defineProps<{
    user: User;
}>();
const { canView } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: index(),
            } satisfies BreadcrumbItem,
        ],
    },
});
</script>

<template>
    <Head :title="user.name" />

    <div class="space-y-6">
        <PermissionDeniedState
            v-if="!canView('users')"
            resource="users"
            action="view"
        />

        <template v-else>
        <div class="flex items-center justify-between gap-4">
            <Heading title="User" description="Read-only user details." />

            <Button variant="secondary" as-child>
                <Link :href="index()">Back</Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Name</p>
                <p class="mt-1 font-medium">{{ user.name }}</p>
            </div>

            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Email</p>
                <p class="mt-1 font-medium">{{ user.email }}</p>
            </div>

            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Verified</p>
                <p class="mt-1 font-medium">
                    {{ user.email_verified_at ? 'Yes' : 'No' }}
                </p>
            </div>

            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Updated</p>
                <p class="mt-1 font-medium">
                    {{ formatDateTime(user.updated_at) }}
                </p>
            </div>
        </div>

        <div class="rounded-xl border p-4">
            <p class="text-sm text-muted-foreground">Roles</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <Badge
                    v-for="role in user.roles"
                    :key="role.id"
                    variant="secondary"
                >
                    {{ role.name }}
                </Badge>
                <Badge v-if="!user.roles.length" variant="outline">
                    No roles
                </Badge>
            </div>
        </div>
        </template>
    </div>
</template>
