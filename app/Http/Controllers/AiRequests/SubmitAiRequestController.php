<?php

namespace App\Http\Controllers\AiRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiRequests\StoreAiRequestRequest;
use App\Jobs\ProcessAiRequestJob;
use App\Jobs\ProcessKnowledgeBaseRequestJob;
use App\Models\AiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SubmitAiRequestController extends Controller
{
    public function __invoke(StoreAiRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $entity = $validated['entity'];
        $modelClass = 'App\\Models\\'.Str::studly($entity);

        abort_if(
            ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class),
            404,
        );

        /** @var Model|null $model */
        $model = $modelClass::query()->first();

        abort_if($model === null, 404);

        $requestId = (string) Str::ulid();
        $cacheKey = $this->cacheKey($requestId);
        $knowledgeBasePath = null;

        if ($request->hasFile('knowledge_base')) {
            $knowledgeBasePath = $request->file('knowledge_base')->storeAs(
                sprintf('ai-requests/%s', $requestId),
                'knowledge-base.sqlite',
                'local',
            );
        }

        Cache::put($cacheKey, [
            'status' => 'pending',
            'entity' => $entity,
            'entity_label' => Str::headline(class_basename($model)),
            'record_id' => (string) $model->getKey(),
            'record' => $model->attributesToArray(),
            'answer' => null,
            'error' => null,
            'ai_response' => null,
        ], now()->addHours(2));

        AiResponse::create([
            'user_id' => $request->user()->id,
            'request_id' => $requestId,
            'entity' => $entity,
            'record_id' => (string) $model->getKey(),
            'status' => 'pending',
        ]);

        if ($validated['mode'] === 'without-ai') {
            ProcessKnowledgeBaseRequestJob::dispatch(
                requestId: $requestId,
                userId: $request->user()->id,
                model: $model,
                knowledgeBasePath: $knowledgeBasePath,
                tagColumn: $validated['tag_column'],
            );
        } else {
            ProcessAiRequestJob::dispatch(
                requestId: $requestId,
                userId: $request->user()->id,
                model: $model,
                prompt: $validated['prompt'] ?? '',
                knowledgeBasePath: $knowledgeBasePath,
                tagColumn: $validated['tag_column'] ?? null,
            );
        }

        return to_route('dashboard');
    }

    private function cacheKey(string $requestId): string
    {
        return sprintf('ai-request:%s', $requestId);
    }
}
