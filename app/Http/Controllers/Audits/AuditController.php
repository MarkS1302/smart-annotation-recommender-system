<?php

namespace App\Http\Controllers\Audits;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditResource;
use App\Models\Audit as SearchableAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use OwenIt\Auditing\Models\Audit;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $audits = QueryBuilder::for(
            SearchableAudit::query()->with('user'),
        )
            ->allowedFilters(
                AllowedFilter::scope('search'),
            )
            ->allowedSorts(
                AllowedSort::field('created_at'),
                AllowedSort::field('event'),
                AllowedSort::field('auditable_type'),
                AllowedSort::field('auditable_id'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('pageSize', 20))
            ->withQueryString();

        return Inertia::render('audits/Index', [
            'filters' => $this->filters($request),
            'auditsResourceCollection' => AuditResource::collection($audits),
        ]);
    }

    public function show(Audit $audit): Response
    {
        $audit->load('user');

        return Inertia::render('audits/Show', [
            'audit' => AuditResource::make($audit),
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
