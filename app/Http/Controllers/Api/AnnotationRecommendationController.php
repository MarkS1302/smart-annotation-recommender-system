<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AnnotationRecommendationRequest;
use App\Jobs\ProcessAiRequestJob;
use App\Jobs\ProcessKnowledgeBaseRequestJob;
use App\Models\AiResponse;
use App\Models\AnnotationSource;
use App\Support\AiRequests\AnnotationRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AnnotationRecommendationController extends Controller
{
    public function __invoke(AnnotationRecommendationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $recordId = Arr::get($validated, 'record_id');
        $mode = (string) Arr::get($validated, 'mode', 'ai');
        $knowledgeBaseSourceId = Arr::get($validated, 'knowledge_base_source_id');
        $prompt = Arr::get($validated, 'prompt');

        $record = AnnotationRecord::fromJson(
            entity: (string) Arr::get($validated, 'entity'),
            record: (array) Arr::get($validated, 'record'),
            recordId: filled($recordId) ? (string) $recordId : (string) Str::ulid(),
        );
        $knowledgeBase = $this->knowledgeBase($knowledgeBaseSourceId);
        
        $requestId = (string) Str::ulid();

        Cache::put($this->cacheKey($requestId), [
            'status' => 'pending',
            'entity' => $record->entity,
            'entity_label' => $record->entityLabel,
            'record_id' => $record->recordId,
            'record' => $record->attributes,
            'answer' => null,
            'error' => null,
            'ai_response' => null,
        ], now()->addHours(2));

        $aiResponseData = [
            'user_id' => $request->user()->id,
            'request_id' => $requestId,
            'entity' => $record->entity,
            'record_id' => $record->recordId,
            'status' => 'pending',
        ];

        AiResponse::create($aiResponseData);

        if ($mode === 'without-ai') {
            ProcessKnowledgeBaseRequestJob::dispatch(
                requestId: $requestId,
                userId: $request->user()->id,
                model: $record,
                knowledgeBasePath: (string) $knowledgeBase->file_path,
                tagColumn: (string) $knowledgeBase->tag_column,
                deleteKnowledgeBaseAfterProcessing: false,
            );
        } else {
            ProcessAiRequestJob::dispatch(
                requestId: $requestId,
                model: $record,
                prompt: filled($prompt) ? (string) $prompt : (string) config('ai.default_prompt'),
                knowledgeBasePath: $knowledgeBase?->file_path,
                tagColumn: $knowledgeBase?->tag_column,
                userId: $request->user()->id,
                deleteKnowledgeBaseAfterProcessing: false,
            );
        }

        return response()->json([
            'request_id' => $requestId,
            'status' => 'pending',
            'status_url' => route('api.annotations.recommendations.show', $requestId),
        ], 202);
    }

    private function knowledgeBase(mixed $sourceId): ?AnnotationSource
    {
        if (blank($sourceId)) {
            return null;
        }

        return AnnotationSource::query()
            ->whereKey($sourceId)
            ->where('type', AnnotationSource::TYPE_SQLITE)
            ->where('active', true)
            ->first();
    }

    private function cacheKey(string $requestId): string
    {
        return sprintf('ai-request:%s', $requestId);
    }
}
