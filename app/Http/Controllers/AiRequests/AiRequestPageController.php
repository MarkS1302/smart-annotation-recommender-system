<?php

namespace App\Http\Controllers\AiRequests;

use App\Http\Controllers\Controller;
use App\Models\AiResponse;
use App\Models\AnnotationSource;
use App\Support\AiRequestEntityRegistry;
use App\Support\AiRequests\AnnotationSourceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AiRequestPageController extends Controller
{
    public function __invoke(Request $request, AiRequestEntityRegistry $entityRegistry, AnnotationSourceManager $sourceManager): Response
    {
        $requestId = $request->filled('request_id')
            ? $request->string('request_id')->toString()
            : null;
        $result = null;

        if ($requestId !== null && AiResponse::query()
            ->where('user_id', $request->user()->id)
            ->where('request_id', $requestId)
            ->exists()) {
            $result = Cache::get(sprintf('ai-request:%s', $requestId));
        }

        return Inertia::render('ai-requests/Index', [
            'defaultPrompt' => config('ai.default_prompt'),
            'entities' => $entityRegistry->entities(),
            'jsonSources' => $sourceManager->options(AnnotationSource::TYPE_JSON),
            'knowledgeBases' => $sourceManager->options(AnnotationSource::TYPE_SQLITE),
            'requestId' => $requestId,
            'result' => $result,
        ]);
    }
}
