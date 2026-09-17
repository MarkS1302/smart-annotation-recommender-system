<?php

namespace App\Http\Controllers\AnnotationSources;

use App\Http\Controllers\Controller;
use App\Models\AnnotationSource;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadAnnotationSourceController extends Controller
{
    public function __invoke(AnnotationSource $annotationSource): StreamedResponse
    {
        $this->authorize('view', $annotationSource);
        abort_unless($annotationSource->isSqlite(), 422, 'Only SQLite sources can be downloaded.');
        $filePath = (string) $annotationSource->file_path;
        abort_if(blank($filePath), 404, 'The SQLite source file is missing.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($filePath), 404, 'The SQLite source file is missing.');

        return $disk->download(
            $filePath,
            sprintf('%s.sqlite', str($annotationSource->name)->slug()),
            ['Content-Type' => 'application/vnd.sqlite3'],
        );
    }
}
