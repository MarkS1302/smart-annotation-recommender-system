<?php

use App\Enums\RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use OwenIt\Auditing\Models\Audit;

test('users and roles pages are displayed', function (): void {
    $user = User::factory()->create();
    grantAdminPermissions($user, [
        'view.users',
        'view.roles',
        'create.roles',
        'update.roles',
    ]);

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('usersResourceCollection.data')
        );

    $this->actingAs($user)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('permissions')
            ->has('roleResourceCollection.data')
        );
});

test('users can be searched by name or email', function (): void {
    $actor = User::factory()->create();
    grantAdminPermissions($actor, ['view.users']);

    User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);
    User::factory()->create([
        'name' => 'Borin Flint',
        'email' => 'borin@example.com',
    ]);

    $this->actingAs($actor)
        ->get(route('users.index', [
            'filter' => ['search' => 'ada@example.com'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('usersResourceCollection.meta.total', 1)
            ->where('usersResourceCollection.data.0.email', 'ada@example.com')
        );
});

test('super admin bypasses policies', function (): void {
    $user = User::factory()->create();
    Role::findOrCreate(RoleEnum::SuperAdmin->value, 'web');
    $user->assignRole(RoleEnum::SuperAdmin->value);

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('audits.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('activity-logs.index'))
        ->assertOk();
});

test('users can be created updated and deleted', function (): void {
    $actor = User::factory()->create();
    grantAdminPermissions($actor, [
        'create.users',
        'view.users',
        'update.users',
        'delete.users',
    ]);
    $role = Role::create([
        'name' => 'Editor',
        'guard_name' => 'web',
    ]);
    $newRole = Role::create([
        'name' => 'Manager',
        'guard_name' => 'web',
    ]);

    $this->actingAs($actor)
        ->post(route('users.store'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_ids' => [$role->id],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'));

    $user = User::where('email', 'ada@example.com')->firstOrFail();

    expect($user->roles)->toHaveCount(1);
    expect($user->roles->first()->id)->toBe($role->id);

    $this->actingAs($actor)
        ->put(route('users.update', $user), [
            'name' => 'Ada Updated',
            'email' => 'ada.updated@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role_ids' => [$newRole->id],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'));

    $user->refresh();

    expect($user->name)->toBe('Ada Updated');
    expect($user->email)->toBe('ada.updated@example.com');
    expect($user->roles)->toHaveCount(1);
    expect($user->roles->first()->id)->toBe($newRole->id);

    $this->actingAs($actor)
        ->delete(route('users.destroy', $user))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'));

    expect($user->fresh())->toBeNull();
});

test('users cannot delete themselves', function (): void {
    $user = User::factory()->create();
    grantAdminPermissions($user, ['delete.users']);

    $this->actingAs($user)
        ->delete(route('users.destroy', $user))
        ->assertForbidden();

    expect($user->fresh())->not->toBeNull();
});

test('roles can be created updated and toggled', function (): void {
    $actor = User::factory()->create();
    grantAdminPermissions($actor, [
        'create.roles',
        'view.roles',
        'update.roles',
        'delete.roles',
    ]);
    $permission = Permission::create([
        'name' => 'view.users',
        'guard_name' => 'web',
    ]);

    $this->actingAs($actor)
        ->post(route('roles.store'), [
            'name' => 'Manager',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('roles.index'));

    $role = Role::where('name', 'Manager')->firstOrFail();

    expect($role->permissions)->toHaveCount(0);
    expect(
        Audit::query()
            ->where('auditable_type', Role::class)
            ->where('auditable_id', $role->id)
            ->where('event', 'created')
            ->exists(),
    )->toBeTrue();

    $this->actingAs($actor)
        ->put(route('roles.update', $role), [
            'name' => 'Administrator',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('roles.index'));

    $role->refresh();

    expect($role->name)->toBe('Administrator');
    expect($role->permissions)->toHaveCount(0);
    expect(
        Audit::query()
            ->where('auditable_type', Role::class)
            ->where('auditable_id', $role->id)
            ->where('event', 'updated')
            ->exists(),
    )->toBeTrue();

    $this->actingAs($actor)
        ->put(route('roles.permissions.toggle', [$role, $permission]), [
            'enabled' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($role->fresh()->hasPermissionTo($permission))->toBeTrue();

    $role->refresh();
    expect($role->permissions)->toHaveCount(1);
    expect($role->permissions->first()->id)->toBe($permission->id);

    $this->actingAs($actor)
        ->put(route('roles.permissions.toggle', [$role, $permission]), [
            'enabled' => false,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($role->fresh()->hasPermissionTo($permission))->toBeFalse();
    expect($role->fresh()->permissions)->toHaveCount(0);
});

function grantAdminPermissions(User $user, array $permissionNames): void
{
    foreach ($permissionNames as $permissionName) {
        Permission::findOrCreate($permissionName, 'web');
    }

    $user->givePermissionTo($permissionNames);
}
