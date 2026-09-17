import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Auth } from '@/types/auth';

type PermissionSubject =
    | 'users'
    | 'roles'
    | 'audits'
    | 'activity-logs'
    | 'annotation-sources'
    | 'ai-responses';

type PageProps = {
    auth: Auth;
};

export function usePermissions() {
    const page = usePage<PageProps>();
    const auth = computed(() => page.props.auth);

    const roles = computed(() => auth.value.roles ?? []);
    const permissions = computed(() => new Set(auth.value.permissions ?? []));

    function hasRole(role: string): boolean {
        return roles.value.some((currentRole) => currentRole.toLowerCase() === role.toLowerCase());
    }

    function isSuperAdmin(): boolean {
        return hasRole('SuperAdmin');
    }

    function hasPermission(permission: string): boolean {
        if (isSuperAdmin()) {
            return true;
        }

        return permissions.value.has(permission);
    }

    function can(action: 'view' | 'create' | 'update' | 'delete', subject: PermissionSubject): boolean {
        return hasPermission(`${action}.${subject}`);
    }

    function canView(subject: PermissionSubject): boolean {
        return can('view', subject);
    }

    function canCreate(subject: PermissionSubject): boolean {
        return can('create', subject);
    }

    function canUpdate(subject: PermissionSubject): boolean {
        return can('update', subject);
    }

    function canDelete(subject: PermissionSubject): boolean {
        return can('delete', subject);
    }

    return {
        hasRole,
        isSuperAdmin,
        hasPermission,
        can,
        canView,
        canCreate,
        canUpdate,
        canDelete,
    };
}
