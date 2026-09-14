<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AiRequestEntityRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('registry exposes project models and their records', function (): void {
    $user = User::factory()->create([
        'name' => 'Ada Lovelace',
    ]);
    Role::create([
        'name' => 'Reviewer',
        'guard_name' => 'web',
    ]);
    Permission::create([
        'name' => 'view.users',
        'guard_name' => 'web',
    ]);

    $registry = app(AiRequestEntityRegistry::class);
    $entities = collect($registry->entities())->keyBy('value');

    expect($entities->keys()->all())->toBe(['permission', 'role', 'user']);
    expect($entities['user']['records'][0]['label'])->toContain('Ada Lovelace');
    expect($entities['role']['records'][0]['label'])->toContain('Reviewer');
    expect($entities['permission']['records'][0]['label'])->toContain('view.users');
    expect($registry->keys())->toBe(['permission', 'role', 'user']);
    expect($registry->find('user'))->toMatchArray([
        'value' => 'user',
        'label' => 'User',
    ]);
    expect($registry->find('missing'))->toBeNull();
    expect($registry->findRecord('user', $user->id)?->email)->toBe($user->email);
});
