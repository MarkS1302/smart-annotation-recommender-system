<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CircleCheck, CircleX } from '@lucide/vue';
import { computed, h, ref } from 'vue';
import CreateButton from '@/components/Buttons/CreateButton.vue';
import DeleteAction from '@/components/DataTable/Actions/DeleteAction.vue';
import EditAction from '@/components/DataTable/Actions/EditAction.vue';
import ViewAction from '@/components/DataTable/Actions/ViewAction.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import PermissionDeniedState from '@/components/PermissionDeniedState.vue';
import { Badge } from '@/components/ui/badge';
import { useDataTableColumn } from '@/composables/useDataTableColumn';
import CreateUserSheet from '@/pages/users/partials/CreateUserSheet.vue';
import EditUserSheet from '@/pages/users/partials/EditUserSheet.vue';
import { destroy, index, show } from '@/routes/users';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { User } from '@/types/models/user';
import type { ResourceCollection } from '@/types/response/resource-collection';
import { Role } from '@/types/models/role';
import { isEmpty } from 'lodash';

type Props = {
    filters: {
        search: string;
    };
    roles: Role[];
    usersResourceCollection: ResourceCollection<User>;
};

defineProps<Props>();
const { canCreate, canView, canUpdate, canDelete } = usePermissions();

const openCreateSheet = ref(false);
const openEditSheet = ref(false);
const selectedUser = ref<User | null>(null);

const columns = computed(() =>
    useDataTableColumn<User>(
        [
            {
                key: 'id',
                title: '#',
                class: 'w-16',
                sortable: true,
            },
            {
                key: 'name',
                title: 'Name',
                cellFormatter: (row, value) => value ?? row.name,
            },
            {
                key: 'email',
                title: 'Email',
            },
            {
                key: 'roles',
                title: 'Roles',
                cellFormatter: (_row, value) => {
                    if (isEmpty(value)) {
                        return '';
                    }

                    const badges = (value as Role[]).map((role) =>
                        h(
                            Badge,
                            {
                                variant: 'secondary',
                            },
                            {
                                default: () => [role.name],
                            },
                        ),
                    );

                    return h(
                        'div',
                        {
                            class: 'flex flex-wrap gap-2',
                        },
                        badges,
                    );
                },
            },
            {
                key: 'email_verified_at',
                title: 'Verified',
                cellFormatter: (_row, value) => {
                    if (value) {
                        return h(CircleCheck, {
                            class: 'h-4 w-4 text-green-600',
                        });
                    }

                    return h(CircleX, {
                        class: 'h-4 w-4 text-destructive',
                    });
                },
            },
            {
                key: 'created_at',
                title: 'Created',
            },
        ],
        canView('users') || canUpdate('users') || canDelete('users')
            ? [
                  {
                      component: ViewAction,
                      actionRoute: (user) => show.url(user),
                      hasPermission: () => canView('users'),
                  },
                  {
                      component: EditAction,
                      actionRoute: (user) => show.url(user),
                      hasPermission: () => canUpdate('users'),
                      onClickHandler: openEditUserSheet,
                  },
                  {
                      component: DeleteAction,
                      actionRoute: (user) => destroy.url(user),
                      hasPermission: () => canDelete('users'),
                      props: {
                          propsToReload: ['usersResourceCollection'],
                          title: 'Delete user?',
                          message:
                              'This removes the user and their role assignments.',
                      },
                  },
              ]
            : [],
        { selectable: true },
    ),
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: index.url(),
            },
        ],
    },
});

function openCreateUserSheet(): void {
    openCreateSheet.value = true;
}

function openEditUserSheet(user: User): void {
    selectedUser.value = user;
    openEditSheet.value = true;
}
</script>

<template>
    <Head title="Users" />

    <div class="space-y-6">
        <PermissionDeniedState
            v-if="!canView('users')"
            resource="users"
            action="view"
        />

        <template v-else>
            <DataTable
                title="Users"
                description="Create, edit, and inspect users."
                :columns="columns"
                :resource-collection="usersResourceCollection"
                searchable-attribute="search"
                :initial-search="filters.search"
                :props-to-reload="['usersResourceCollection']"
            >
                <template #actions>
                    <CreateButton
                        v-if="canCreate('users')"
                        label="New User"
                        :on-click="openCreateUserSheet"
                    />
                </template>
            </DataTable>
        </template>
    </div>

    <CreateUserSheet
        v-if="canCreate('users')"
        v-model:is-open="openCreateSheet"
        :roles="roles"
    />

    <EditUserSheet
        v-if="canUpdate('users') && selectedUser"
        v-model:is-open="openEditSheet"
        :roles="roles"
        :user="selectedUser"
    />
</template>
