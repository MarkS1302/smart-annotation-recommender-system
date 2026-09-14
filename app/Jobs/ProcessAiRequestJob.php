<?php

namespace App\Jobs;

use App\Models\AiResponse;
use App\Support\AiRequests\SqliteKnowledgeBaseReader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionClass;
use RuntimeException;
use Throwable;

class ProcessAiRequestJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 0;

    public function __construct(
        public string $requestId,
        public Model $model,
        public string $prompt,
        public ?string $knowledgeBasePath = null,
        public ?string $tagColumn = null,
        public ?int $userId = null,
        public string $mode = 'ai',
    ) {
    }

    public function handle(SqliteKnowledgeBaseReader $knowledgeBaseReader): void
    {
        $modelClass = $this->model::class;
        $entity = Str::kebab(class_basename($modelClass));
        $entityLabel = Str::headline(class_basename($modelClass));

        $input = [];

        try {
            if ($this->mode === 'without-ai') {
                $this->storeKnowledgeBaseResult($knowledgeBaseReader, $entity, $entityLabel);

                return;
            }

            $input = [
                [
                    'role' => 'developer',
                    'content' => $this->prompt,
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'model' => class_basename($modelClass),
                        'model_class' => $modelClass,
                        'model_definition' => $this->modelDefinition($modelClass),
                        'database_row' => $this->model->attributesToArray(),
                        'database_schema' => [
                            'table' => $this->model->getTable(),
                            'primary_key' => $this->model->getKeyName(),
                            'columns' => Schema::getColumnListing($this->model->getTable()),
                        ],
                        'existing_annotation_taxonomy' => $this->knowledgeBase($knowledgeBaseReader),
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ],
            ];

            $response = Http::connectTimeout(config('ai.connect_timeout'))
                ->timeout(config('ai.timeout'))
                ->retry(
                    config('ai.retry.times'),
                    config('ai.retry.sleep'),
                    throw: false,
                )
                ->post(config('ai.url'), [
                    'model' => config('ai.model'),
                    'stream' => false,
                    'messages' => $input,
                ]);

            Log::info('AI service response received.', [
                'request_id' => $this->requestId,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            $aiResponse = $response->throw()->json();
        } catch (Throwable $exception) {
            Log::error('AI service request failed.', [
                'request_id' => $this->requestId,
                'message' => $exception->getMessage(),
                'response' => $exception instanceof RequestException
                    ? ($exception->response->json() ?? $exception->response->body())
                    : null,
            ]);

            $this->storeResult([
                'status' => 'error',
                'entity' => $entity,
                'entity_label' => $entityLabel,
                'input' => $input,
                'record_id' => (string) $this->model->getKey(),
                'record' => $this->model->attributesToArray(),
                'answer' => null,
                'error' => $exception->getMessage(),
                'ai_response' => null,
            ]);

            $this->deleteKnowledgeBase();

            return;
        }

        $this->storeResult([
            'status' => 'success',
            'entity' => $entity,
            'entity_label' => $entityLabel,
            'input' => $input,
            'record_id' => (string) $this->model->getKey(),
            'record' => $this->model->attributesToArray(),
            'answer' => data_get($aiResponse, 'message.content'),
            'error' => null,
            'ai_response' => $aiResponse,
        ]);

        $this->deleteKnowledgeBase();
    }

    public function failed(?Throwable $exception): void
    {
        $this->storeError($exception?->getMessage() ?? 'The AI request failed.');
        $this->deleteKnowledgeBase();
    }

    /**
     * @param  class-string  $modelClass
     */
    private function modelDefinition(string $modelClass): string
    {
        try {
            $reflection = new ReflectionClass($modelClass);
            $fileName = $reflection->getFileName();

            if ($fileName === false) {
                return '';
            }

            return file_get_contents($fileName) ?: '';
        } catch (Throwable) {
            return '';
        }
    }

    private function storeError(string $message): void
    {
        $existing = Cache::get($this->cacheKey(), []);

        $this->storeResult([
            ...$existing,
            'status' => 'error',
            'answer' => null,
            'error' => $message,
            'ai_response' => null,
        ]);

        $this->deleteKnowledgeBase();
    }

    private function knowledgeBase(SqliteKnowledgeBaseReader $reader): ?array
    {
        if ($this->knowledgeBasePath === null) {
            return null;
        }

        return $reader->read(Storage::disk('local')->path($this->knowledgeBasePath), $this->tagColumn ?? '');
    }

    private function storeKnowledgeBaseResult(
        SqliteKnowledgeBaseReader $reader,
        string $entity,
        string $entityLabel,
    ): void {
        $knowledgeBase = $this->knowledgeBase($reader);
        $tags = $this->tagsBySlug($this->knowledgeBaseRows($knowledgeBase, 'tags'));
        $annotations = collect($this->knowledgeBaseRows($knowledgeBase, 'rules'))
            ->filter(fn (array $rule): bool => $this->knowledgeBaseValue($rule, 'entity') === $entity)
            ->filter(fn (array $rule): bool => $this->matchesKnowledgeBaseRule($rule))
            ->sortByDesc(fn (array $rule): int => (int) ($this->knowledgeBaseValue($rule, 'priority') ?? 0))
            ->groupBy(fn (array $rule): string => (string) ($this->knowledgeBaseValue($rule, 'rule_group') ?? $this->knowledgeBaseValue($rule, 'tag_slug')))
            ->map(fn ($rules): array => $this->annotationFromRule($rules->first(), $tags))
            ->values()
            ->all();

        $result = [
            'model' => class_basename($this->model),
            'entity_type' => $entity,
            'summary' => 'Rule-based annotations from the knowledge database.',
            'annotations' => $annotations,
            'uncertain_annotations' => [],
            'ignored_fields' => [],
        ];

        $this->storeResult([
            'status' => 'success',
            'entity' => $entity,
            'entity_label' => $entityLabel,
            'input' => [],
            'record_id' => (string) $this->model->getKey(),
            'record' => $this->model->attributesToArray(),
            'answer' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'error' => null,
            'ai_response' => [
                'source' => 'knowledge_base',
                ...$result,
            ],
        ]);

        $this->deleteKnowledgeBase();
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function matchesKnowledgeBaseRule(array $rule): bool
    {
        $field = $this->knowledgeBaseValue($rule, 'field');
        $operator = $this->knowledgeBaseValue($rule, 'operator');

        if (! is_string($operator)) {
            return false;
        }

        if ($operator === 'always') {
            return true;
        }

        if (! is_string($field) || ! array_key_exists($field, $this->model->getAttributes())) {
            return false;
        }

        $value = $this->model->getAttribute($field);

        return match ($operator) {
            'present' => filled($value),
            'missing' => blank($value),
            'equals' => (string) $value === (string) $this->knowledgeBaseValue($rule, 'value'),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, array<string, mixed>>  $tags
     * @return array{tag: string, label: string, category: string, confidence: float, reason: string, source_fields: array<int, string>, existing_tag: bool}
     */
    private function annotationFromRule(array $rule, array $tags): array
    {
        $tagSlug = $this->knowledgeBaseValue($rule, 'tag_slug');

        if (! is_string($tagSlug) || ! array_key_exists($tagSlug, $tags)) {
            throw new RuntimeException('Every matching rule must reference a tag in the tags table.');
        }

        $tag = $tags[$tagSlug];
        $field = $this->knowledgeBaseValue($rule, 'field');

        return [
            'tag' => $tagSlug,
            'label' => (string) ($this->knowledgeBaseValue($tag, 'label') ?? $tagSlug),
            'category' => (string) ($this->knowledgeBaseValue($tag, 'category') ?? 'custom'),
            'confidence' => 1.0,
            'reason' => (string) ($this->knowledgeBaseValue($rule, 'reason') ?? 'Matched a knowledge database rule.'),
            'source_fields' => is_string($field) ? [$field] : [],
            'existing_tag' => true,
        ];
    }

    /**
     * @param  array{tag_column: string, tables: array<int, array{name: string, columns: array<int, string>, rows: array<int, array<string, mixed>>}>}  $knowledgeBase
     * @return array<int, array<string, mixed>>
     */
    private function knowledgeBaseRows(array $knowledgeBase, string $tableName): array
    {
        $table = collect($knowledgeBase['tables'])->first(
            fn (array $table): bool => strcasecmp($table['name'], $tableName) === 0,
        );

        if ($table === null) {
            throw new RuntimeException(sprintf('The SQLite knowledge database must have a "%s" table.', $tableName));
        }

        return $table['rows'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $tags
     * @return array<string, array<string, mixed>>
     */
    private function tagsBySlug(array $tags): array
    {
        $tagsBySlug = [];

        foreach ($tags as $tag) {
            $slug = $this->knowledgeBaseValue($tag, 'slug');

            if (is_string($slug) && $slug !== '') {
                $tagsBySlug[$slug] = $tag;
            }
        }

        return $tagsBySlug;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function knowledgeBaseValue(array $row, string $column): mixed
    {
        return collect($row)->first(
            fn (mixed $_, string $candidate): bool => strcasecmp($candidate, $column) === 0,
        );
    }

    private function deleteKnowledgeBase(): void
    {
        if ($this->knowledgeBasePath !== null) {
            Storage::disk('local')->delete($this->knowledgeBasePath);
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function storeResult(array $result): void
    {
        Cache::put($this->cacheKey(), $result, now()->addHours(2));

        $aiResponse = AiResponse::query()
            ->where('request_id', $this->requestId);

        if ($this->userId !== null) {
            $aiResponse->where('user_id', $this->userId);
        }

        $aiResponse = $aiResponse->first();

        if ($aiResponse === null) {
            return;
        }

        Auth::onceUsingId($aiResponse->user_id);

        $aiResponse->update([
            'status' => $result['status'],
            'answer' => $result['answer'],
            'ai_response' => $result['ai_response'],
            'error' => $result['error'],
        ]);
    }

    private function cacheKey(): string
    {
        return sprintf('ai-request:%s', $this->requestId);
    }
}
