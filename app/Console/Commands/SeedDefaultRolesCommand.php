<?php

namespace App\Console\Commands;

use App\Enums\RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Spatie\Permission\PermissionRegistrar;

#[Signature('roles:seed-defaults')]
#[Description('Command description')]
class SeedDefaultRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:seed-defaults';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default SuperAdmin and Admin roles';

    public function handle(): int
    {
        $guardName = config('auth.defaults.guard', 'web');

        $permissionNames = collect(config('permissions.resources', []))
            ->flatMap(fn (array $resource): array => Arr::get($resource, 'permissions', []))
            ->unique()
            ->values();

        $permissions = $permissionNames->map(function (string $permissionName) use ($guardName): Permission {
            return Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guardName,
            ]);
        });

        $superAdmin = Role::query()->firstOrCreate([
            'name' => RoleEnum::SuperAdmin->value,
            'guard_name' => $guardName,
        ]);
        $superAdmin->syncPermissions([]);

        $admin = Role::query()->firstOrCreate([
            'name' => RoleEnum::Admin->value,
            'guard_name' => $guardName,
        ]);
        $admin->syncPermissions($permissions->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->components->info('Created default roles: SuperAdmin and Admin.');

        return self::SUCCESS;
    }
}
