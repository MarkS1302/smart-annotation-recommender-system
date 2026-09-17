<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Spatie\Permission\PermissionRegistrar;

#[Signature('permissions:sync {--prune : Remove permissions missing from config}')]
#[Description('Sync application permissions from config')]
class SyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync {--prune : Remove permissions missing from config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync application permissions from config';

    public function handle(): int
    {
        $permissionNames = collect(config('permissions.resources', []))
            ->flatMap(fn (array $resource): array => Arr::get($resource, 'permissions', []))
            ->unique()
            ->values();

        $guardName = config('auth.defaults.guard', 'web');

        foreach ($permissionNames as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guardName,
            ]);
        }

        if ($this->option('prune')) {
            Permission::query()
                ->where('guard_name', $guardName)
                ->whereNotIn('name', $permissionNames->all())
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->components->info(sprintf(
            'Synced %d permissions%s.',
            $permissionNames->count(),
            $this->option('prune') ? ' with prune' : '',
        ));

        return self::SUCCESS;
    }
}
