<?php

use App\Models\User;
use App\Models\Role;

test('user create command creates a user and assigns roles', function (): void {
    Role::create([
        'name' => 'Editor',
        'guard_name' => 'web',
    ]);

    $this->artisan('user:create', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        '--password' => 'password',
        '--password-confirmation' => 'password',
        '--role' => ['Editor'],
    ])->assertExitCode(0);

    $user = User::where('email', 'ada@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user?->name)->toBe('Ada Lovelace');
    expect($user?->hasRole('Editor'))->toBeTrue();
});
