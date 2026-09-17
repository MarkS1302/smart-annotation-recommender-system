<?php

use App\Models\AiResponse;
use App\Models\Permission;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('users can view their ai response', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('view.ai-responses', 'web'));
    $answer = json_encode([
        'annotations' => [],
        'ignored_fields' => [
            [
                'field' => 'id',
                'reason' => 'Primary key.',
            ],
        ],
    ], JSON_THROW_ON_ERROR);
    $aiResponse = AiResponse::create([
        'user_id' => $user->id,
        'request_id' => '01J00000000000000000000000',
        'entity' => 'user',
        'record_id' => '1',
        'status' => 'success',
        'answer' => $answer,
        'ai_response' => ['message' => ['content' => $answer]],
    ]);

    $this->actingAs($user)
        ->get(route('ai-responses.show', $aiResponse))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ai-responses/Show')
            ->where('aiResponse.data.id', $aiResponse->id)
            ->where('aiResponse.data.answer', $answer),
        );
});

test('users cannot view another users ai response', function (): void {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(Permission::findOrCreate('view.ai-responses', 'web'));
    $aiResponse = AiResponse::create([
        'user_id' => User::factory()->create()->id,
        'request_id' => '01J00000000000000000000001',
        'entity' => 'user',
        'record_id' => '1',
        'status' => 'success',
    ]);

    $this->actingAs($viewer)
        ->get(route('ai-responses.show', $aiResponse))
        ->assertForbidden();
});

test('users can view an index of their ai responses', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('view.ai-responses', 'web'));
    AiResponse::create([
        'user_id' => $user->id,
        'entity' => 'user',
        'record_id' => '1',
        'status' => 'success',
        'request_id' => '01J00000000000000000000002',
    ]);

    $this->actingAs($user)
        ->get(route('ai-responses.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ai-responses/Index')
            ->has('aiResponses.data', 1),
        );
});

test('users without ai response permission cannot view the index', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('ai-responses.index'))
        ->assertForbidden();
});
