<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnnotationSources\StoreAnnotationSourceRequest;
use App\Http\Requests\AnnotationSources\UpdateAnnotationSourceRequest;
use App\Http\Resources\AnnotationSourceResource;
use App\Models\AnnotationSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AnnotationSourceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AnnotationSource::class);

        $sources = QueryBuilder::for(AnnotationSource::query())
            ->allowedFilters(
                AllowedFilter::scope('search'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('pageSize', 15))
            ->withQueryString();

        return Inertia::render('annotation-sources/Index', [
            'sources' => AnnotationSourceResource::collection($sources),
            'filters' => [
                'search' => (string) $request->input('filter.search', ''),
            ],
        ]);
    }

    public function store(StoreAnnotationSourceRequest $request): RedirectResponse
    {
        $this->authorize('create', AnnotationSource::class);

        $validated = $request->validated();
        $source = AnnotationSource::create([
            'name' => Arr::get($validated, 'name'),
            'type' => Arr::get($validated, 'type'),
            'tag_column' => Arr::get($validated, 'tag_column'),
            'active' => Arr::get($validated, 'active', true),
            'created_by' => $request->user()->id,
        ]);

        $path = $request->file('file')->storeAs(
            sprintf('annotation-sources/%s', $source->id),
            $source->isJson() ? 'records.json' : 'knowledge-base.sqlite',
            'local',
        );

        $source->update(['file_path' => $path]);

        activity('annotation-sources')
            ->causedBy($request->user())
            ->performedOn($source)
            ->event('created')
            ->log('Annotation source created');

        return to_route('annotation-sources.index');
    }

    public function update(
        UpdateAnnotationSourceRequest $request,
        AnnotationSource $annotationSource,
    ): RedirectResponse {
        $this->authorize('update', $annotationSource);

        $validated = $request->validated();
        $oldPath = $annotationSource->file_path;
        $newPath = null;

        if ($request->hasFile('file')) {
            $newPath = $request->file('file')->storeAs(
                sprintf('annotation-sources/%s', $annotationSource->id),
                $annotationSource->isJson() ? 'records.json' : 'knowledge-base.sqlite',
                'local',
            );
        }

        $annotationSource->update([
            'name' => Arr::get($validated, 'name'),
            'tag_column' => Arr::get($validated, 'tag_column'),
            'active' => Arr::get($validated, 'active'),
            ...($newPath === null ? [] : ['file_path' => $newPath]),
        ]);

        if ($newPath !== null && $oldPath !== null && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        activity('annotation-sources')
            ->causedBy($request->user())
            ->performedOn($annotationSource)
            ->event('updated')
            ->log('Annotation source updated');

        return to_route('annotation-sources.index');
    }

    public function destroy(Request $request, AnnotationSource $annotationSource): RedirectResponse
    {
        $this->authorize('delete', $annotationSource);

        $filePath = $annotationSource->file_path;

        activity('annotation-sources')
            ->causedBy($request->user())
            ->performedOn($annotationSource)
            ->event('deleted')
            ->log('Annotation source deleted');

        $annotationSource->delete();

        if ($filePath !== null) {
            Storage::disk('local')->delete($filePath);
        }

        return to_route('annotation-sources.index');
    }
}
