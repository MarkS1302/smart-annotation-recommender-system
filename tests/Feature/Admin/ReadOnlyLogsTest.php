<?php

use App\Models\User;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\Models\Activity;

test('audit and activity log pages are displayed', function (): void {
    $user = User::factory()->create();
    grantLogPermissions($user, [
        'view.audits',
        'view.activity-logs',
    ]);

    $audit = Audit::create([
        'user_type' => User::class,
        'user_id' => $user->id,
        'event' => 'updated',
        'auditable_type' => User::class,
        'auditable_id' => $user->id,
        'old_values' => json_encode(['name' => 'Old Name']),
        'new_values' => json_encode(['name' => 'New Name']),
        'url' => '/users',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'tags' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $activity = Activity::create([
        'log_name' => 'users',
        'description' => 'User updated',
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'causer_type' => User::class,
        'causer_id' => $user->id,
        'event' => 'updated',
        'attribute_changes' => json_encode(['name' => ['Old Name', 'New Name']]),
        'properties' => json_encode(['name' => 'New Name']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('audits.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('audits.show', $audit))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('activity-logs.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('activity-logs.show', $activity))
        ->assertOk();
});

function grantLogPermissions(User $user, array $permissionNames): void
{
    foreach ($permissionNames as $permissionName) {
        \App\Models\Permission::findOrCreate($permissionName, 'web');
    }

    $user->givePermissionTo($permissionNames);
}
