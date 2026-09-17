<?php

namespace App\Http\Controllers\AiRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiRequests\StoreAiRequestRequest;
use App\Jobs\ProcessAiRequestJob;
use App\Jobs\ProcessKnowledgeBaseRequestJob;
use App\Models\AiResponse;
use App\Models\AnnotationSource;
use App\Support\AiRequestEntityRegistry;
use App\Support\AiRequests\AnnotationSourceManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SubmitAiRequestController extends Controller
{
    public function __construct(
        private AiRequestEntityRegistry $entityRegistry,
        private AnnotationSourceManager $sourceManager,
    ) {}

    public function __invoke(StoreAiRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $mode = (string) Arr::get($validated, 'mode', 'ai');
        $prompt = Arr::get($validated, 'prompt');
        [$record, $entity, $entityLabel] = $this->resolveInputRecord($validated);
        $requestId = (string) Str::ulid();
        [$knowledgeBasePath, $tagColumn, $deleteKnowledgeBase] = $this->resolveKnowledgeBase(
            request: $request,
            validated: $validated,
            requestId: $requestId,
        );

        Cache::put($this->cacheKey($requestId), [
            'status' => 'pending',
            'entity' => $entity,
            'entity_label' => $entityLabel,
            'record_id' => $record instanceof Model ? (string) $record->getKey() : $record->recordId,
            'record' => $record instanceof Model ? $record->attributesToArray() : $record->attributes,
            'answer' => null,
            'error' => null,
            'ai_response' => null,
        ], now()->addHours(2));

        $aiResponseData = [
            'user_id' => $request->user()->id,
            'request_id' => $requestId,
            'entity' => $entity,
            'record_id' => $record instanceof Model ? (string) $record->getKey() : $record->recordId,
            'status' => 'pending',
        ];

        AiResponse::create($aiResponseData);

        if ($mode === 'without-ai') {
            ProcessKnowledgeBaseRequestJob::dispatch(
                requestId: $requestId,
                userId: $request->user()->id,
                model: $record,
                knowledgeBasePath: (string) $knowledgeBasePath,
                tagColumn: (string) $tagColumn,
                deleteKnowledgeBaseAfterProcessing: $deleteKnowledgeBase,
            );
        } else {
            ProcessAiRequestJob::dispatch(
                requestId: $requestId,
                model: $record,
                prompt: filled($prompt) ? (string) $prompt : '',
                knowledgeBasePath: $knowledgeBasePath,
                tagColumn: $tagColumn,
                userId: $request->user()->id,
                deleteKnowledgeBaseAfterProcessing: $deleteKnowledgeBase,
            );
        }

        return to_route('dashboard');
    }


    private function resolveInputRecord(array $validated): array
    {
        $inputSourceId = Arr::get($validated, 'input_source_id');
        $recordId = Arr::get($validated, 'record_id');

        if (filled($inputSourceId)) {
            $source = AnnotationSource::query()
                ->whereKey($inputSourceId)
                ->where('type', AnnotationSource::TYPE_JSON)
                ->where('active', true)
                ->firstOrFail();
            $record = $this->sourceManager->record($source, (string) $recordId);

            return [$record, $record->entity, $record->entityLabel];
        }

        $entityDefinition = $this->entityRegistry->find((string) Arr::get($validated, 'entity'));

        abort_if($entityDefinition === null, 404);

        /** @var class-string<Model> $modelClass */
        $modelClass = Arr::get($entityDefinition, 'modelClass');
        $model = filled($recordId)
            ? $modelClass::query()->find($recordId)
            : $modelClass::query()->first();

        abort_if($model === null, 404);

        return [
            $model,
            (string) Arr::get($entityDefinition, 'value'),
            (string) Arr::get($entityDefinition, 'label'),
        ];
    }

    private function resolveKnowledgeBase(
        StoreAiRequestRequest $request,
        array $validated,
        string $requestId,
    ): array {
        $knowledgeBaseSourceId = Arr::get($validated, 'knowledge_base_source_id');

        if (filled($knowledgeBaseSourceId)) {
            $source = AnnotationSource::query()
                ->whereKey($knowledgeBaseSourceId)
                ->where('type', AnnotationSource::TYPE_SQLITE)
                ->where('active', true)
                ->firstOrFail();

            abort_if(blank($source->file_path) || blank($source->tag_column), 422);

            return [$source->file_path, $source->tag_column, false];
        }

        if (! $request->hasFile('knowledge_base')) {
            return [null, null, false];
        }

        $path = $request->file('knowledge_base')->storeAs(
            sprintf('ai-requests/%s', $requestId),
            'knowledge-base.sqlite',
            'local',
        );

        return [$path, (string) Arr::get($validated, 'tag_column'), true];
    }

    private function cacheKey(string $requestId): string
    {
        return sprintf('ai-request:%s', $requestId);
    }
}
