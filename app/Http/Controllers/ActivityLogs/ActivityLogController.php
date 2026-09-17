<?php

namespace App\Http\Controllers\ActivityLogs;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\Activity as SearchableActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $activityLogs = QueryBuilder::for(
            SearchableActivity::query()->with('causer'),
        )
            ->allowedFilters(
                AllowedFilter::scope('search'),
            )
            ->allowedSorts(
                AllowedSort::field('created_at'),
                AllowedSort::field('log_name'),
                AllowedSort::field('event'),
                AllowedSort::field('description'),
            )
            ->defaultSort('-created_at')
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
}
