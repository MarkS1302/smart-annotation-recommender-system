<?php

use App\Models\Permission;

test('permissions sync command creates configured permissions', function (): void {
    Permission::query()->delete();

    $this->artisan('permissions:sync')
        ->assertExitCode(0);

    expect(Permission::query()->pluck('name')->all())->toEqualCanonicalizing([
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
    ]);
});
