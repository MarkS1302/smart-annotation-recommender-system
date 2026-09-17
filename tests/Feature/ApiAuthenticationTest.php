<?php

use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\PersonalAccessToken;

test('users can log in to the api and receive a token', function (): void {
    $user = User::factory()->create([
        'email' => 'api-user@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson(route('api.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email']])
        ->assertJson([
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);

    $token = $response->json('token');

    expect($token)->toBeString()->not->toBeEmpty();
    $personalAccessToken = PersonalAccessToken::findToken($token);

    expect($personalAccessToken)->not->toBeNull()
        ->and($personalAccessToken?->tokenable->is($user))->toBeTrue();
});

test('api login rejects invalid credentials', function (): void {
    $user = User::factory()->create();

    $this->postJson(route('api.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])
        ->assertUnauthorized()
        ->assertJson(['message' => 'The provided credentials are incorrect.']);
});

test('a generated api token authenticates annotation requests as its user', function (): void {
    $user = User::factory()->create();
    Queue::fake();

    $token = $this->postJson(route('api.login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->json('token');

    $this->withToken($token)
        ->postJson(route('api.annotations.recommendations.store'), [
            'entity' => 'user',
            'record_id' => (string) $user->id,
            'record' => ['email' => $user->email],
            'mode' => 'ai',
            'prompt' => 'Summarize this entity.',
        ])
        ->assertAccepted()
        ->assertJsonStructure(['request_id', 'status', 'status_url'])
        ->assertJson(['status' => 'pending']);

    $personalAccessToken = PersonalAccessToken::findToken($token);

    expect($personalAccessToken)->not->toBeNull()
        ->and($personalAccessToken?->last_used_at)->not->toBeNull();
    $this->assertDatabaseHas('ai_responses', ['user_id' => $user->id]);
});

test('annotation requests require a generated api token', function (): void {
    $this->postJson(route('api.annotations.recommendations.store'), [
        'entity' => 'user',
        'record' => ['email' => 'api-user@example.com'],
    ])
        ->assertUnauthorized();
});
