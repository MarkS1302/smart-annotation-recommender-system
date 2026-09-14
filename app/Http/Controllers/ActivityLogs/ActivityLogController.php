<?php

namespace App\Http\Controllers\ActivityLogs;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $activityLogs = $this->activityLogsQuery($request)
            ->paginate($request->integer('pageSize', 20))
            ->withQueryString();

        return Inertia::render('activity-logs/Index', [
            'filters' => $this->filters($request),
            'activityLogs' => ActivityLogResource::collection($activityLogs),
        ]);
    }

    public function show(Activity $activityLog): Response
    {
        $activityLog->load(['subject', 'causer']);

        return Inertia::render('activity-logs/Show', [
            'activityLog' => ActivityLogResource::make($activityLog),
        ]);
    }

    /**
     * @return array{search: string}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->input('filter.search', ''),
        ];
    }

    private function activityLogsQuery(Request $request): QueryBuilder
    {
        return QueryBuilder::for(
            Activity::query()->with('causer'),
        )
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);

                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('log_name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('event', 'like', "%{$search}%")
                            ->orWhere('subject_type', 'like', "%{$search}%")
                            ->orWhere('causer_type', 'like', "%{$search}%");
                    });
                }),
            )
            ->allowedSorts('created_at', 'log_name', 'event', 'description')
            ->defaultSort('-created_at');
    }
}
