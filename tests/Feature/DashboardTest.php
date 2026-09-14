<?php

use App\Models\AiResponse;
use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('view.users', 'web');
    Role::findOrCreate('Editor', 'web');
    $user->givePermissionTo('view.users');
    $user->assignRole('Editor');
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.roles', ['Editor'])
            ->where('auth.permissions', ['view.users']),
        );
});

test('dashboard shows the authenticated users recent ai requests', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $request = AiResponse::create([
        'user_id' => $user->id,
        'request_id' => '01J00000000000000000000000',
        'entity' => 'user',
        'record_id' => (string) $user->id,
        'status' => 'pending',
    ]);

    AiResponse::create([
        'user_id' => $otherUser->id,
        'request_id' => '01J00000000000000000000001',
        'entity' => 'user',
        'record_id' => (string) $otherUser->id,
        'status' => 'success',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('aiRequests', 1)
            ->where('aiRequests.0.requestId', $request->request_id)
            ->where('aiRequests.0.status', 'pending'),
        );
});
