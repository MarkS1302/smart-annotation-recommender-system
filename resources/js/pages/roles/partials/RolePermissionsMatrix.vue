<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { toggle } from '@/routes/roles/permissions';

type Permission = {
    id: number;
    name: string;
};

type Role = {
    id: number;
    name: string;
    permissions: Permission[];
};

type PermissionGroup = {
    key: string;
    title: string;
    permissions: Permission[];
};

const props = defineProps<{
    role: Role;
    permissions: Permission[];
    canUpdate: boolean;
}>();

const emit = defineEmits<{
    edit: [];
}>();

const selectedPermissionIds = ref<number[]>(
    props.role.permissions.map((permission) => Number(permission.id)),
);

const pendingPermissionIds = ref<Record<number, boolean>>({});

const permissionGroups = computed<PermissionGroup[]>(() => {
    const groups = new Map<string, Permission[]>();

    props.permissions.forEach((permission) => {
        const key = permissionGroupKey(permission.name);
        const existingPermissions = groups.get(key) ?? [];

        groups.set(key, [...existingPermissions, permission]);
    });

    return [...groups.entries()].map(([key, permissions]) => ({
        key,
        title: formatTitle(key),
        permissions,
    }));
});

const activeCount = computed(() => selectedPermissionIds.value.length);

function permissionGroupKey(permissionName: string): string {
    return permissionName.split('.')[1] ?? permissionName;
}

function formatTitle(value: string): string {
    return value
        .replace(/[-_]/g, ' ')
        .split(' ')
        .filter(Boolean)
        .map((word) => {
            const normalizedWord = word.toLowerCase();

            if (
                normalizedWord.length > 3 &&
                normalizedWord.endsWith('s') &&
                !normalizedWord.endsWith('ss')
            ) {
                return `${normalizedWord.slice(0, -1).replace(/^./, (letter) => letter.toUpperCase())}`;
            }

            return normalizedWord.replace(/^./, (letter) => letter.toUpperCase());
        })
        .join(' ');
}

function formatPermissionLabel(permissionName: string): string {
    const [action, resource] = permissionName.split('.');

    return [action, resource]
        .filter(Boolean)
        .map((segment) => formatTitle(segment ?? ''))
        .join(' ');
}

function isPermissionSelected(permissionId: number): boolean {
    return selectedPermissionIds.value.includes(Number(permissionId));
}

function isPermissionPending(permissionId: number): boolean {
    return pendingPermissionIds.value[Number(permissionId)] === true;
}

function togglePermission(permissionId: number, checked: boolean): void {
    const normalizedPermissionId = Number(permissionId);
    const previous = [...selectedPermissionIds.value];

    selectedPermissionIds.value = checked
        ? [...new Set([...selectedPermissionIds.value, normalizedPermissionId])]
        : selectedPermissionIds.value.filter(
              (existingPermissionId) =>
                  existingPermissionId !== normalizedPermissionId,
          );

    pendingPermissionIds.value = {
        ...pendingPermissionIds.value,
        [normalizedPermissionId]: true,
    };

    router.put(
        toggle({
            role: props.role.id,
            permission: normalizedPermissionId,
        }),
        {
            enabled: checked,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                selectedPermissionIds.value = previous;
            },
            onFinish: () => {
                const nextPendingPermissionIds = {
                    ...pendingPermissionIds.value,
                };

                delete nextPendingPermissionIds[normalizedPermissionId];
                pendingPermissionIds.value = nextPendingPermissionIds;
            },
        },
    );
}
</script>

<template>
    <Card class="rounded-[1.75rem] border-slate-200 shadow-sm">
        <CardHeader class="flex flex-row items-start justify-between gap-4 border-b pb-6">
            <div class="space-y-1">
                <CardTitle class="text-2xl">{{ role.name }}</CardTitle>
                <CardDescription>
                    Toggle the permissions that belong to this role.
                </CardDescription>
            </div>

            <div class="flex items-center gap-3">
                <Badge variant="secondary" class="rounded-full px-3 py-1 text-sm">
                    {{ activeCount }} / {{ permissions.length }} Active
                </Badge>
                <button
                    v-if="canUpdate"
                    type="button"
                    class="rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium shadow-sm transition-colors hover:bg-muted"
                    @click="emit('edit')"
                >
                    Edit
                </button>
            </div>
        </CardHeader>

        <CardContent class="space-y-8 pt-6">
            <div
                v-for="group in permissionGroups"
                :key="group.key"
                class="space-y-4"
            >
                <div class="flex items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h3 class="text-lg font-semibold">{{ group.title }}</h3>
                        <p class="text-sm text-muted-foreground">
                            Manage access for {{ group.title.toLowerCase() }}.
                        </p>
                    </div>

                    <Badge variant="outline" class="rounded-full px-3 py-1">
                        {{
                            group.permissions.filter((permission) =>
                                isPermissionSelected(permission.id),
                            ).length
                        }}/{{ group.permissions.length }}
                    </Badge>
                </div>

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="permission in group.permissions"
                        :key="permission.id"
                        class="flex items-center justify-between gap-4 rounded-2xl border bg-background px-4 py-4 shadow-sm transition-colors hover:bg-muted/30"
                    >
                        <div class="space-y-1">
                            <p class="text-sm font-medium">
                                {{ formatPermissionLabel(permission.name) }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ permission.name }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <Switch
                                :model-value="isPermissionSelected(permission.id)"
                                :disabled="isPermissionPending(permission.id)"
                                @update:model-value="
                                    (checked: boolean) =>
                                        togglePermission(
                                            permission.id,
                                            Boolean(checked),
                                        )
                                "
                            />

                            <Spinner
                                v-if="isPermissionPending(permission.id)"
                                class="size-4"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
