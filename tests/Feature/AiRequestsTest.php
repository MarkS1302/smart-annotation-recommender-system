<?php

use App\Jobs\ProcessAiRequestJob;
use App\Jobs\ProcessKnowledgeBaseRequestJob;
use App\Models\AiResponse;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use OwenIt\Auditing\Models\Audit;

test('authenticated users can visit the ai requests page', function (): void {
    $user = User::factory()->create();
    config(['ai.default_prompt' => 'Configured annotation prompt.']);

    $this->actingAs($user)
        ->get(route('ai-requests.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ai-requests/Index')
            ->where('defaultPrompt', 'Configured annotation prompt.')
            ->has('entities', 3)
            ->where('entities.0.label', 'Permission')
            ->where('entities.1.label', 'Role')
            ->where('entities.2.label', 'User')
            ->where('requestId', null)
            ->where('result', null),
        );
});

test('ai requests can be submitted', function (): void {
    $user = User::factory()->create();
    Queue::fake();

    $response = $this->actingAs($user)
        ->post(route('ai-requests.store'), [
            'entity' => 'user',
            'mode' => 'ai',
            'prompt' => 'Summarize the important fields.',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));

    Queue::assertPushed(ProcessAiRequestJob::class, function (ProcessAiRequestJob $job) use ($user): bool {
        return $job->model->is($user)
            && $job->prompt === 'Summarize the important fields.'
            && $job->mode === 'ai';
    });

    $aiResponse = AiResponse::query()->firstOrFail();
    $requestId = $aiResponse->request_id;

    expect($requestId)->toBeString();
    $cachedRequest = Cache::get(sprintf('ai-request:%s', $requestId));

    expect($cachedRequest)->toMatchArray([
        'status' => 'pending',
        'entity' => 'user',
        'entity_label' => 'User',
        'record_id' => (string) $user->id,
    ])->and($cachedRequest['record']['email'])->toBe($user->email);

    expect(
        Audit::query()
            ->where('auditable_type', AiResponse::class)
            ->where('auditable_id', $aiResponse->id)
            ->where('event', 'created')
            ->where('user_id', $user->id)
            ->exists(),
    )->toBeTrue();
});

test('ai requests page loads cached result by request id', function (): void {
    $user = User::factory()->create();

    Cache::put('ai-request:test-request', [
        'status' => 'success',
        'entity' => 'user',
        'entity_label' => 'User',
        'record_id' => (string) $user->id,
        'record' => [
            'email' => $user->email,
        ],
        'answer' => '{"annotations":[]}',
        'error' => null,
        'ai_response' => [
            'message' => [
                'content' => '{"annotations":[]}',
            ],
        ],
    ], now()->addHour());

    AiResponse::create([
        'user_id' => $user->id,
        'request_id' => 'test-request',
        'entity' => 'user',
        'record_id' => (string) $user->id,
        'status' => 'success',
    ]);

    $this->actingAs($user)
        ->get(route('ai-requests.index', ['request_id' => 'test-request']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('requestId', 'test-request')
            ->where('result.status', 'success')
            ->where('result.entity', 'user')
            ->where('result.answer', '{"annotations":[]}'),
        );
});

test('entity and prompt are required to submit an ai request', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('ai-requests.store'), [
            'entity' => '',
            'mode' => 'ai',
            'prompt' => '',
        ])
        ->assertSessionHasErrors(['entity', 'prompt']);
});

test('a knowledge database is required without ai', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('ai-requests.store'), [
            'entity' => 'user',
            'mode' => 'without-ai',
        ])
        ->assertSessionHasErrors('knowledge_base');
});

test('a valid sqlite knowledge database can be submitted without ai', function (): void {
    $user = User::factory()->create();
    Queue::fake();

    $this->actingAs($user)
        ->post(route('ai-requests.store'), [
            'entity' => 'user',
            'mode' => 'without-ai',
            'knowledge_base' => UploadedFile::fake()->createWithContent(
                'user-annotations.sqlite',
                "SQLite format 3\000".str_repeat("\000", 64),
            ),
            'tag_column' => 'slug',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    Queue::assertPushed(ProcessKnowledgeBaseRequestJob::class);
    Queue::assertNotPushed(ProcessAiRequestJob::class);
});
