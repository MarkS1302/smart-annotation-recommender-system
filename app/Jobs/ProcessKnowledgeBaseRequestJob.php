<?php

namespace App\Jobs;

use App\Models\AiResponse;
use App\Support\AiRequests\SqliteKnowledgeBaseReader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProcessKnowledgeBaseRequestJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 0;

    public function __construct(
        public string $requestId,
        public int $userId,
        public Model $model,
        public string $knowledgeBasePath,
        public string $tagColumn,
    ) {
    }

    public function handle(SqliteKnowledgeBaseReader $knowledgeBaseReader): void
    {
        $entity = Str::kebab(class_basename($this->model));
        $entityLabel = Str::headline(class_basename($this->model));

        try {
            $knowledgeBase = $knowledgeBaseReader->read(
                Storage::disk('local')->path($this->knowledgeBasePath),
                $this->tagColumn,
            );
            $tags = $this->tagsBySlug($this->knowledgeBaseRows($knowledgeBase, 'tags'));
            $annotations = collect($this->knowledgeBaseRows($knowledgeBase, 'rules'))
                ->filter(fn (array $rule): bool => $this->knowledgeBaseValue($rule, 'entity') === $entity)
                ->filter(fn (array $rule): bool => $this->matchesRule($rule))
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
        } catch (Throwable $exception) {
            Log::error('Knowledge base request failed.', [
                'request_id' => $this->requestId,
                'message' => $exception->getMessage(),
            ]);

            $this->storeError($exception->getMessage());
        } finally {
            $this->deleteKnowledgeBase();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->storeError($exception?->getMessage() ?? 'The knowledge base request failed.');
        $this->deleteKnowledgeBase();
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function matchesRule(array $rule): bool
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
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function storeResult(array $result): void
    {
        Cache::put($this->cacheKey(), $result, now()->addHours(2));

        $aiResponse = AiResponse::query()
            ->where('request_id', $this->requestId)
            ->where('user_id', $this->userId)
            ->first();

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

    private function deleteKnowledgeBase(): void
    {
        Storage::disk('local')->delete($this->knowledgeBasePath);
    }

    private function cacheKey(): string
    {
        return sprintf('ai-request:%s', $this->requestId);
    }
}
