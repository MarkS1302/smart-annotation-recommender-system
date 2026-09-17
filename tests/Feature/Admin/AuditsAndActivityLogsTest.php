<?php

use App\Models\Permission;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\Models\Activity;

test('audits and activity logs pages are displayed with resource data', function (): void {
    $user = User::factory()->create();

    Permission::findOrCreate('view.audits', 'web');
    Permission::findOrCreate('view.activity-logs', 'web');
    $user->givePermissionTo(['view.audits', 'view.activity-logs']);

    $audit = Audit::query()->create([
        'user_type' => User::class,
        'user_id' => $user->id,
        'event' => 'updated',
        'auditable_type' => User::class,
        'auditable_id' => $user->id,
        'old_values' => json_encode(['name' => 'Old Name']),
        'new_values' => json_encode(['name' => 'New Name']),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $activity = Activity::query()->create([
        'log_name' => 'users',
        'description' => 'User updated',
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'event' => 'updated',
        'causer_type' => User::class,
        'causer_id' => $user->id,
        'properties' => json_encode(['name' => 'New Name']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('audits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auditsResourceCollection.data.0.id', $audit->id)
            ->where('auditsResourceCollection.data.0.event', 'updated')
            ->where('auditsResourceCollection.data.0.user.id', $user->id)
            ->where('auditsResourceCollection.data.0.user.email', $user->email)
        );

    $this->actingAs($user)
        ->get(route('activity-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activityLogs.data.0.id', $activity->id)
            ->where('activityLogs.data.0.log_name', 'users')
            ->where('activityLogs.data.0.causer.id', $user->id)
            ->where('activityLogs.data.0.causer.email', $user->email)
        );

    $this->actingAs($user)
        ->get(route('audits.index', [
            'filter' => ['search' => 'Pest'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auditsResourceCollection.meta.total', 1)
        );

    $this->actingAs($user)
        ->get(route('activity-logs.index', [
            'filter' => ['search' => 'User updated'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activityLogs.meta.total', 1)
        );
});
