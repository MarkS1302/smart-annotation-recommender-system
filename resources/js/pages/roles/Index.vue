<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import PermissionDeniedState from '@/components/PermissionDeniedState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import CreateRoleSheet from '@/pages/roles/partials/CreateRoleSheet.vue';
import EditRoleSheet from '@/pages/roles/partials/EditRoleSheet.vue';
import RolePermissionsMatrix from '@/pages/roles/partials/RolePermissionsMatrix.vue';
import { index } from '@/routes/roles';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { BreadcrumbItem } from '@/types';
import type { Permission } from '@/types/models/permission';
import type { Role } from '@/types/models/role';
import type { ResourceCollection } from '@/types/response/resource-collection';

type Props = {
    permissions: Permission[];
    roleResourceCollection: ResourceCollection<Role>;
};

const props = defineProps<Props>();
const { canCreate, canView, canUpdate } = usePermissions();

const activeRoleId = ref<string>(
    String(props.roleResourceCollection.data[0]?.id ?? ''),
);
const openCreateSheet = ref(false);
const openEditSheet = ref(false);
const selectedRole = ref<Role | null>(null);

watch(
    () => props.roleResourceCollection.data,
    (roles) => {
        if (
            activeRoleId.value &&
            roles.some((role) => String(role.id) === activeRoleId.value)
        ) {
            return;
        }

        activeRoleId.value = String(roles[0]?.id ?? '');
    },
    { immediate: true },
);

function openEditRoleSheet(role: Role): void {
    selectedRole.value = role;
    openEditSheet.value = true;
}

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
    <Head title="Roles & Permissions" />

    <div class="space-y-8">
        <PermissionDeniedState
            v-if="!canView('roles')"
            resource="roles"
            action="view"
        />

        <template v-else>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <Heading
                    title="Roles & Permissions"
                    description="Manage permissions and roles for your application."
                />

                <Button
                    v-if="canCreate('roles')"
                    @click="openCreateSheet = true"
                >
                    Create Role
                </Button>
            </div>

            <div v-if="roleResourceCollection.data.length" class="space-y-6">
                <Tabs
                    v-model="activeRoleId"
                    :unmount-on-hide="false"
                    class="space-y-6"
                >
                    <TabsList
                        class="justify-start overflow-x-auto p-1.5 shadow-inner"
                    >
                        <TabsTrigger
                            v-for="role in roleResourceCollection.data"
                            :key="role.id"
                            :value="String(role.id)"
                            class="min-w-40 px-6"
                        >
                            {{ role.name }}
                            <Badge variant="secondary" class="ml-2">
                                {{ role.permissions.length }}
                            </Badge>
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent
                        v-for="role in roleResourceCollection.data"
                        :key="role.id"
                        :value="String(role.id)"
                        class="outline-none"
                    >
                        <RolePermissionsMatrix
                            :key="role.id"
                            :role="role"
                            :permissions="permissions"
                            :can-update="canUpdate('roles')"
                            @edit="openEditRoleSheet(role)"
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </template>
    </div>

    <CreateRoleSheet
        v-if="canCreate('roles')"
        v-model:is-open="openCreateSheet"
    />

    <EditRoleSheet
        v-if="canUpdate('roles') && selectedRole"
        v-model:is-open="openEditSheet"
        :role="selectedRole"
    />
</template>
