<?php

use App\Jobs\ProcessAiRequestJob;
use App\Jobs\ProcessKnowledgeBaseRequestJob;
use App\Models\AiResponse;
use App\Models\User;
use App\Support\AiRequests\SqliteKnowledgeBaseReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('process ai request job stores ai service success result in cache', function (): void {
    $user = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    Http::fake([
        'http://localhost:11434/api/chat' => Http::response([
            'message' => [
                'content' => '{"annotations":[]}',
            ],
        ], 200),
    ]);

    $aiResponse = AiResponse::create([
        'user_id' => $user->id,
        'request_id' => 'job-test-request',
        'entity' => 'user',
        'record_id' => (string) $user->id,
        'status' => 'pending',
    ]);

    $job = new ProcessAiRequestJob(
        requestId: 'job-test-request',
        userId: $user->id,
        model: $user,
        prompt: 'Recommend annotation tags.',
    );

    $job->handle(
        app(SqliteKnowledgeBaseReader::class),
    );

    expect(Cache::get('ai-request:job-test-request'))->toMatchArray([
        'status' => 'success',
        'entity' => 'user',
        'entity_label' => 'User',
        'answer' => '{"annotations":[]}',
        'error' => null,
    ]);

    expect(AiResponse::query()->firstOrFail())
        ->status->toBe('success')
        ->answer->toBe('{"annotations":[]}');

    $audit = Audit::query()
        ->where('auditable_type', AiResponse::class)
        ->where('auditable_id', $aiResponse->id)
        ->latest('id')
        ->firstOrFail();

    expect($audit->event)
        ->toBe('updated')
        ->and($audit->user_id)
        ->toBe($user->id)
        ->and($audit->getModified()['status'])
        ->toMatchArray([
            'old' => 'pending',
            'new' => 'success',
        ]);
});

test('process ai request job handles jobs queued before user tracking was added', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    AiResponse::create([
        'user_id' => $user->id,
        'request_id' => 'legacy-job-request',
        'entity' => 'user',
        'record_id' => (string) $user->id,
        'status' => 'pending',
    ]);

    Http::fake([
        'http://localhost:11434/api/chat' => Http::response([
            'message' => [
                'content' => '{"annotations":[]}',
            ],
        ], 200),
    ]);

    $job = new ProcessAiRequestJob(
        requestId: 'legacy-job-request',
        model: $user,
        prompt: 'Recommend annotation tags.',
    );

    $job->handle(app(SqliteKnowledgeBaseReader::class));

    expect(AiResponse::query()->firstOrFail()->status)->toBe('success');
});

test('process knowledge base request job returns matching tags without calling ai', function (): void {
    $user = User::factory()->unverified()->create();
    $knowledgeBasePath = 'knowledge-base-test.sqlite';
    Storage::disk('local')->delete($knowledgeBasePath);
    $database = new \PDO(sprintf('sqlite:%s', Storage::disk('local')->path($knowledgeBasePath)));
    $database->exec('CREATE TABLE tags (slug TEXT PRIMARY KEY, label TEXT NOT NULL, category TEXT NOT NULL)');
    $database->exec('CREATE TABLE rules (entity TEXT NOT NULL, tag_slug TEXT NOT NULL, field TEXT, operator TEXT NOT NULL, value TEXT, rule_group TEXT, priority INTEGER NOT NULL, reason TEXT)');
    $database->exec("INSERT INTO tags (slug, label, category) VALUES ('user', 'User', 'entity'), ('email-unverified-user', 'Email unverified user', 'lifecycle')");
    $database->exec("INSERT INTO rules (entity, tag_slug, field, operator, value, rule_group, priority, reason) VALUES ('user', 'user', NULL, 'always', NULL, 'entity', 100, 'The record is a User entity.'), ('user', 'email-unverified-user', 'email_verified_at', 'missing', NULL, 'email-verification', 10, 'The account email is not verified.')");
    $database = null;

    $aiResponse = AiResponse::create([
        'user_id' => $user->id,
        'request_id' => 'knowledge-base-request',
        'entity' => 'user',
        'record_id' => (string) $user->id,
        'status' => 'pending',
    ]);

    Http::fake();

    $job = new ProcessKnowledgeBaseRequestJob(
        requestId: 'knowledge-base-request',
        userId: $user->id,
        model: $user,
        knowledgeBasePath: $knowledgeBasePath,
        tagColumn: 'slug',
    );

    $job->handle(app(SqliteKnowledgeBaseReader::class));

    $result = Cache::get('ai-request:knowledge-base-request');

    expect($result)
        ->toMatchArray(['status' => 'success'])
        ->and($result['ai_response']['source'])->toBe('knowledge_base');

    $annotations = json_decode($result['answer'], true)['annotations'];

    expect(collect($annotations)->pluck('tag')->all())
        ->toBe(['user', 'email-unverified-user'])
        ->and(collect($annotations)->every(fn (array $annotation): bool => $annotation['existing_tag']))->toBeTrue();

    $audit = Audit::query()
        ->where('auditable_type', AiResponse::class)
        ->where('auditable_id', $aiResponse->id)
        ->latest('id')
        ->firstOrFail();

    expect($audit->event)
        ->toBe('updated')
        ->and($audit->user_id)
        ->toBe($user->id)
        ->and($audit->getModified()['status'])
        ->toMatchArray([
            'old' => 'pending',
            'new' => 'success',
        ]);

    Http::assertNothingSent();
    expect(Storage::disk('local')->exists($knowledgeBasePath))->toBeFalse();
});
