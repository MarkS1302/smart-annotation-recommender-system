<?php

namespace App\Http\Controllers\AnnotationSources;

use App\Http\Controllers\Controller;
use App\Models\AnnotationSource;
use App\Support\AiRequests\SqliteKnowledgeBasePreview;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PreviewAnnotationSourceController extends Controller
{
    public function __construct(
        private readonly SqliteKnowledgeBasePreview $sqliteKnowledgeBasePreview,
    ) {}

    public function __invoke(AnnotationSource $annotationSource): JsonResponse
    {
        $this->authorize('view', $annotationSource);

        abort_unless($annotationSource->isSqlite(), 422, 'Only SQLite sources can be previewed.');
        $filePath = (string) $annotationSource->file_path;
        abort_if(blank($filePath), 404, 'The SQLite source file is missing.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($filePath), 404, 'The SQLite source file is missing.');

        return response()->json([
            'source' => [
                'id' => $annotationSource->id,
                'name' => $annotationSource->name,
                'tag_column' => $annotationSource->tag_column,
            ],
            'tables' => $this->sqliteKnowledgeBasePreview->tables(
                $disk->path($filePath),
            ),
        ]);
    }
}
