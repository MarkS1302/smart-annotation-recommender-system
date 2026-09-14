<?php

namespace App\Http\Controllers\AiRequests;

use App\Http\Controllers\Controller;
use App\Models\AiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use SplFileInfo;

class AiRequestPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $entities = collect(File::allFiles(app_path('Models')))
            ->map(function (SplFileInfo $file): string {
                $relativePath = Str::after(
                    $file->getRealPath(),
                    app_path('Models').DIRECTORY_SEPARATOR,
                );

                return 'App\\Models\\'.str_replace(
                    [DIRECTORY_SEPARATOR, '/'],
                    '\\',
                    Str::beforeLast($relativePath, '.php'),
                );
            })
            ->filter(fn (string $modelClass): bool => class_exists($modelClass)
                && is_subclass_of($modelClass, Model::class)
                && $modelClass !== AiResponse::class
            )
            ->map(function (string $modelClass): array {
                $name = class_basename($modelClass);

                return [
                    'value' => Str::kebab($name),
                    'label' => Str::headline($name),
                ];
            })
            ->values()
            ->all();

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
            'entities' => $entities,
            'requestId' => $requestId,
            'result' => $result,
        ]);
    }
}
