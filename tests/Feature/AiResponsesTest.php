<?php

use App\Models\AiResponse;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('users can view their ai response', function (): void {
    $user = User::factory()->create();
    $aiResponse = AiResponse::create([
        'user_id' => $user->id,
        'request_id' => '01J00000000000000000000000',
        'entity' => 'user',
        'record_id' => '1',
        'status' => 'success',
        'answer' => '{"annotations":[]}',
        'ai_response' => ['message' => ['content' => '{"annotations":[]}']],
    ]);

    $this->actingAs($user)
        ->get(route('ai-responses.show', $aiResponse))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ai-responses/Show')
            ->where('aiResponse.data.id', $aiResponse->id)
            ->where('aiResponse.data.answer', '{"annotations":[]}'),
        );
});

test('users cannot view another users ai response', function (): void {
    $aiResponse = AiResponse::create([
        'user_id' => User::factory()->create()->id,
        'request_id' => '01J00000000000000000000001',
        'entity' => 'user',
        'record_id' => '1',
        'status' => 'success',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('ai-responses.show', $aiResponse))
        ->assertForbidden();
});

test('users can view an index of their ai responses', function (): void {
    $user = User::factory()->create();
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
