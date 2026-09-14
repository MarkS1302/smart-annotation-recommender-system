# Codex Prompt: Users, Roles, Audits, and Activity Logs CRUD

You are working in a fresh Laravel starter kit project using Vue, Inertia, TypeScript, Tailwind, and shadcn-vue.

Important installed packages:

- Laravel Auditing: https://laravel-auditing.com/guide/installation.html
- spatie/laravel-permission
- spatie/laravel-query-builder
- spatie/laravel-activitylog

Use Laravel Boost tools/skills before making changes. Inspect the existing project structure, routes, models, pages, components, and shadcn-vue conventions first. Follow the existing code style. Keep the implementation simple and idiomatic Laravel/Vue. Do not introduce repositories, services, DTOs, or extra packages unless absolutely necessary.

## Goal

Create full backend and frontend CRUD functionality for Users and Roles.

Also create read-only index/show pages for Audits and Activity Logs.

## General requirements

- Use Vue components only.
- Use Inertia pages.
- Use shadcn-vue components.
- Create and edit forms must open in shadcn-vue `Sheet` components.
- The User form must be a single reusable Vue component used by both create and edit sheets.
- The Role form must be a single reusable Vue component used by both create and edit sheets.
- Add frontend validation using Zod.
- Use Laravel FormRequest validation on the backend.
- Use Spatie Query Builder for index filtering/sorting/searching where appropriate.
- Add authorization checks where appropriate.
- Keep code readable, direct, and close to Laravel starter kit conventions.
- Do not use React components or React patterns.
- Use Vue Composition API and TypeScript.

## Users CRUD

### Backend

Create backend functionality to:

- List users.
- Show a user.
- Create a user.
- Update a user.
- Delete a user.

User fields should include at minimum:

- `name`
- `email`
- `password` on create
- optional `password` update on edit
- assigned roles

Implementation requirements:

- Password should be required on create and optional on update.
- Assign roles to users using `spatie/laravel-permission`.
- Users should only be assigned roles directly.
- Do not assign permissions directly to users.
- Use `syncRoles()` when updating user roles.
- Validate unique email addresses.
- Validate submitted role IDs/names exist.
- Use password confirmation if the project convention already uses it.
- Return Inertia responses for pages.
- Delete users safely.
- Do not allow the authenticated user to delete themselves unless the project already has an explicit convention for that.

### Frontend

Create frontend functionality to:

- Display a users index page.
- Display a users show page.
- Create users from a shadcn-vue `Sheet`.
- Edit users from a shadcn-vue `Sheet`.
- Delete users with a confirmation flow.

Users index page should include:

- Table or list of users.
- Search/filter where reasonable.
- Create button.
- Edit action.
- Show action.
- Delete action.

User form requirements:

- Create one reusable `UserForm.vue` component.
- Use the same `UserForm.vue` in both the create and edit sheets.
- Add Zod validation.
- Show Zod errors before submit where practical.
- Show Laravel validation errors returned by Inertia.
- Role assignment should use a multi-select/checklist UI compatible with shadcn-vue.
- Keep form state simple.

## Roles CRUD

### Backend

Create backend functionality to:

- List roles.
- Show a role.
- Create a role.
- Update a role.
- Delete a role.

Role fields should include:

- `name`
- assigned permissions

Implementation requirements:

- Use `spatie/laravel-permission`.
- Permissions should be assigned to roles only.
- Do not assign permissions directly to users.
- Use `syncPermissions()` when updating role permissions.
- Validate unique role names.
- Validate submitted permission IDs/names exist.
- Provide available permissions to the frontend.
- Use Laravel FormRequests.
- Use Spatie Query Builder for listing/searching/sorting where useful.

### Frontend

Create frontend functionality to:

- Display a roles index page.
- Display a role show page.
- Create roles from a shadcn-vue `Sheet`.
- Edit roles from a shadcn-vue `Sheet`.
- Delete roles with a confirmation flow.

Roles index page should include:

- Table or list of roles.
- Search/filter where reasonable.
- Create button.
- Edit action.
- Show action.
- Delete action.
- Below the roles table/list, display all permissions grouped or listed clearly.
- For each permission, show a shadcn-vue `Switch` to enable or disable that permission for the selected/current role context described below.

Role form requirements:

- Create one reusable `RoleForm.vue` component.
- Use the same `RoleForm.vue` in both the create and edit sheets.
- Add Zod validation.
- Permission assignment in the form should be checkbox-based or another simple shadcn-vue-compatible UI.
- Keep UX consistent with the Users pages.

## Roles index permissions switch requirement

On the roles index page, below all roles, display all permissions.

Add a permission toggle UI that allows enabling or disabling permissions for a role using a shadcn-vue `Switch`.

Implementation requirements:

- Each permission switch must send a request immediately when toggled.
- Use an invokable Laravel controller for the toggle endpoint.
- Do not use a generic update endpoint for this switch.
- The invokable controller should only handle toggling a single permission for a single role.
- Keep the action small and explicit.
- Validate that the role exists.
- Validate that the permission exists.
- If the switch is enabled, assign the permission to the role.
- If the switch is disabled, remove the permission from the role.
- Use Spatie permission methods such as `givePermissionTo()` and `revokePermissionTo()` or an equivalent safe approach.
- Return a lightweight response appropriate for Inertia/Vue.
- Handle frontend pending/loading state per switch.
- Revert the switch or show an error if the request fails.

Suggested route shape:

```php
Route::put('/roles/{role}/permissions/{permission}', ToggleRolePermissionController::class)
    ->name('roles.permissions.toggle');
```

Suggested request payload:

```json
{
  "enabled": true
}
```

Suggested invokable controller behavior:

```php
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
```

Adjust namespaces, imports, authorization, and response style to match the project.

The roles index page should receive enough data to know which permissions are enabled for each role. Use a simple data shape that is easy for Vue to render.

Example frontend data shape:

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

The UI can render roles and permissions in a matrix, grouped list, accordion, or simple cards. Prefer the simplest UI that is clear and maintainable.

## Audits

Create read-only audit pages.

Backend:

- Add index and show methods/pages for audits.
- Use the Laravel Auditing package models/configuration already present in the project.
- Add routes and Inertia pages.
- Use Query Builder for filtering/sorting if appropriate.
- Audits are read-only.

Display useful audit fields:

- auditable type
- auditable id
- event
- old values
- new values
- user
- IP address if available
- user agent if available
- created at

Frontend:

- Create audits index page.
- Create audit show page.
- Format old/new values clearly.
- Use existing table/card conventions.

## Activity Logs

Create read-only activity log pages.

Backend:

- Add index and show methods/pages for Spatie activity logs.
- Add routes and Inertia pages.
- Use Query Builder for filtering/sorting if appropriate.
- Activity logs are read-only.

Display useful activity log fields:

- log name
- description
- subject type/id
- causer
- event if available
- properties
- created at

Frontend:

- Create activity logs index page.
- Create activity log show page.
- Format properties clearly.
- Use existing table/card conventions.

## Routes

Add routes for:

- Users resource routes.
- Roles resource routes.
- Role permission toggle route using an invokable controller.
- Read-only audits index/show routes.
- Read-only activity logs index/show routes.

Follow the existing route file conventions in the Laravel starter kit.

Use route names consistently.

## Components

Use existing shadcn-vue components where available.

Likely components:

- `Button`
- `Input`
- `Label`
- `Sheet`
- `SheetContent`
- `SheetHeader`
- `SheetTitle`
- `SheetDescription`
- `Switch`
- `Checkbox`
- table components if already present
- alert/dialog components if already present

If a shadcn-vue component is missing, either use the project's existing pattern or add the minimal required component in the same style.

## Validation

Backend validation is authoritative.

Frontend Zod validation should mirror backend rules as much as practical.

Do not rely only on frontend validation.

Handle Laravel validation errors returned by Inertia.

## Expected implementation process

1. Inspect the project first using Laravel Boost tools.
2. List the files you plan to create or modify.
3. Implement the backend:
   - controllers
   - invokable role permission toggle controller
   - FormRequests
   - routes
   - policies if needed
   - props/resources as needed
4. Implement the frontend:
   - Inertia pages
   - Vue components
   - shadcn-vue sheets/forms/tables/switches
   - Zod schemas
5. Add or update tests if the project already has a test pattern.
6. Run formatting, type checks, and tests available in the project.
7. Fix any errors.
8. Summarize what was changed and mention any assumptions.

Keep the solution simple, maintainable, and aligned with the existing Laravel starter kit structure.
