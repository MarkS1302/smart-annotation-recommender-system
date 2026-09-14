<?php

namespace App\Http\Controllers\Audits;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use OwenIt\Auditing\Models\Audit;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $audits = QueryBuilder::for(
            Audit::query()->with('user'),
        )
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);

                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('auditable_type', 'like', "%{$search}%")
                            ->orWhere('event', 'like', "%{$search}%")
                            ->orWhere('ip_address', 'like', "%{$search}%")
                            ->orWhere('user_agent', 'like', "%{$search}%")
                            ->orWhereHas('user', function (Builder $query) use ($search): void {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
                }),
            )
            ->allowedSorts('created_at', 'event', 'auditable_type', 'auditable_id')
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
