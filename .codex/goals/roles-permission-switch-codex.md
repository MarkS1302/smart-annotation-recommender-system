# Codex Prompt: Roles Index Permission Switches

You are working in a Laravel starter kit project using Vue, Inertia, TypeScript, Tailwind, shadcn-vue, and `spatie/laravel-permission`.

Use Laravel Boost tools/skills before making changes. Inspect the current roles implementation first and follow the existing project conventions.

## Goal

Update the roles index page so that below the roles list/table it displays all permissions and allows enabling/disabling permissions for roles using shadcn-vue `Switch` components.

Each switch toggle must send a request to an invokable Laravel controller.

## Backend requirements

Create an invokable controller for toggling a single permission on a single role.

Suggested controller name:

```php
App\Http\Controllers\Roles\ToggleRolePermissionController
```

Suggested route:

```php
Route::put('/roles/{role}/permissions/{permission}', ToggleRolePermissionController::class)
    ->name('roles.permissions.toggle');
```

Request payload:

```json
{
  "enabled": true
}
```

Controller requirements:

- Use route-model binding for `Role` and `Permission`.
- Validate `enabled` as required boolean.
- Authorize the action if the project uses policies/authorization.
- If `enabled` is true, assign the permission to the role.
- If `enabled` is false, remove the permission from the role.
- Use Spatie methods such as `givePermissionTo()` and `revokePermissionTo()`.
- Keep this controller focused only on toggling one permission for one role.
- Return `back()` or another lightweight response that matches the project/Inertia convention.

Suggested controller shape:

```php
<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ToggleRolePermissionController extends Controller
{
    public function __invoke(Request $request, Role $role, Permission $permission)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        if ($validated['enabled']) {
            $role->givePermissionTo($permission);
        } else {
            $role->revokePermissionTo($permission);
        }

        return back();
    }
}
```

Adjust namespaces, imports, middleware, authorization, and response style to match the actual project.

## Roles index data requirements

Update the roles index backend response so the Vue page receives:

- all roles
- each role's assigned permissions
- all permissions

Use a simple shape that is easy to render.

Example TypeScript shape:

```ts
type Role = {
  id: number
  name: string
  permissions: string[]
}

type Permission = {
  id: number
  name: string
}
```

It is also acceptable to use permission IDs instead of names if that better matches the project.

## Frontend requirements

On the roles index page:

- Keep the existing roles table/list.
- Below the roles table/list, add a permissions section.
- Display all permissions clearly.
- Add shadcn-vue `Switch` components to enable/disable permissions for roles.
- Each switch should represent whether a specific role has a specific permission.
- When toggled, send a request to the invokable toggle route.
- Add a loading/pending state per switch.
- If the request fails, revert the switch state or show a clear error.
- Do not wait for the role edit form to save these changes; each switch saves immediately.

Prefer the simplest clear UI:

- A role-permission matrix is acceptable.
- Cards grouped by role are acceptable.
- Accordions grouped by role are acceptable if the project already uses accordions.

Use the existing shadcn-vue and Inertia patterns in the project.

## Inertia/Vue notes

- Use Vue Composition API and TypeScript.
- Use existing route helper conventions if present.
- Keep state local to the roles index page unless there is already a shared pattern.
- Do not introduce a new state management package.
- Do not use React patterns.

## Testing/checks

After implementation:

- Run formatting.
- Run PHP tests if available.
- Run TypeScript checks if available.
- Run frontend build if available.
- Fix errors before finishing.

Summarize the files changed and any assumptions.
