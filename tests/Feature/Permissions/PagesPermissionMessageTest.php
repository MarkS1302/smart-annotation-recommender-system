<?php

use App\Models\User;
use App\Models\Role;

test('users index shows a permission message when user lacks access', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('You dont have permission to view users. Speak with an admin.');
});

test('roles show shows a permission message when user lacks access', function (): void {
    $user = User::factory()->create();
    $role = Role::create([
        'name' => 'Editor',
        'guard_name' => 'web',
    ]);

    $this->actingAs($user)
        ->get(route('roles.show', $role))
        ->assertOk()
        ->assertSee('You dont have permission to view roles. Speak with an admin.');
});
