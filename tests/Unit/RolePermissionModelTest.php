<?php

uses(Tests\TestCase::class);

use App\Models\Permission;
use App\Models\Role;

test('role and permission models extend spatie models', function (): void {
    expect(is_subclass_of(Role::class, Spatie\Permission\Models\Role::class))->toBeTrue();
    expect(is_subclass_of(Permission::class, Spatie\Permission\Models\Permission::class))->toBeTrue();
});

test('permission config uses custom models', function (): void {
    expect(config('permission.models.role'))->toBe(Role::class);
    expect(config('permission.models.permission'))->toBe(Permission::class);
});
