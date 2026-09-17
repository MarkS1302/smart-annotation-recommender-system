<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use OwenIt\Auditing\Models\Audit;

class ToggleRolePermissionController extends Controller
{
    public function __invoke(Request $request, Role $role, Permission $permission): RedirectResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);
        $enabled = (bool) Arr::get($validated, 'enabled');

        $oldValues = [
            'permissions' => $role->permissions()->pluck('name')->all(),
        ];

        if ($enabled) {
            $role->givePermissionTo($permission);
        } else {
            $role->revokePermissionTo($permission);
        }

        $role->refresh();

        activity('roles')
            ->causedBy($request->user())
            ->performedOn($role)
            ->withProperties([
                'permission' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ],
                'enabled' => $enabled,
            ])
            ->event('updated')
            ->log('Role permission toggled');

        Audit::create([
            'user_type' => $request->user()?->getMorphClass(),
            'user_id' => $request->user()?->getKey(),
            'event' => 'updated',
            'auditable_type' => $role::class,
            'auditable_id' => $role->getKey(),
            'old_values' => $oldValues,
            'new_values' => [
                'permissions' => $role->permissions()->pluck('name')->all(),
                'permission' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ],
                'enabled' => $enabled,
            ],
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tags' => null,
        ]);

        return back();
    }
}
