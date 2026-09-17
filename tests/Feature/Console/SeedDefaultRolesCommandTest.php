<?php

use App\Enums\RoleEnum;
use App\Models\Permission;
use App\Models\Role;

test('default roles command creates superadmin and admin roles', function (): void {
    Permission::query()->delete();
    Role::query()->delete();

    $this->artisan('permissions:sync')
        ->assertExitCode(0);

    $this->artisan('roles:seed-defaults')
        ->assertExitCode(0);

    $superAdmin = Role::where('name', RoleEnum::SuperAdmin->value)->firstOrFail();
    $admin = Role::where('name', 'Admin')->firstOrFail();

    expect($superAdmin->permissions)->toHaveCount(0);
    expect($admin->permissions->pluck('name')->all())->toEqualCanonicalizing([
        'view.users',
        'create.users',
        'update.users',
        'delete.users',
        'view.roles',
        'create.roles',
        'update.roles',
        'delete.roles',
        'view.audits',
        'view.activity-logs',
        'view.annotation-sources',
        'create.annotation-sources',
        'update.annotation-sources',
        'delete.annotation-sources',
        'view.ai-responses',
    ]);
});
