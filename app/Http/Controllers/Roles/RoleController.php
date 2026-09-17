<?php

namespace App\Http\Controllers\Roles;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use OwenIt\Auditing\Models\Audit;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', Role::class);

        $query = Role::query()->where('name', '!=', RoleEnum::SuperAdmin->value);

        $roleResourceCollection = RoleResource::collection(
            QueryBuilder::for($query)
                ->with(['permissions:id,name'])
                ->allowedSorts(
                    AllowedSort::field('id'),
                    AllowedSort::field('name'),
                    AllowedSort::field('created_at'),
                )
                ->paginate($request->integer('pageSize', 10))
                ->withQueryString(),
        );

        return Inertia::render('roles/Index', [
            'permissions' => Permission::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'roleResourceCollection' => $roleResourceCollection,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): RedirectResponse
    {
        return to_route('roles.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role, Request $request)
    {
        $this->authorize('view', $role);

        return Inertia::render('', [
            'role' => RoleResource::make($role->loadMissing('permissions')),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): RedirectResponse
    {
        return to_route('roles.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $this->authorize('update', $role);

        $data = $request->validated();
        $oldValues = $this->roleAuditValues($role);

        $role->update([
            'name' => Arr::get($data, 'name'),
        ]);

        $this->recordAudit(
            request: $request,
            auditable: $role,
            event: 'updated',
            oldValues: $oldValues,
            newValues: $this->roleAuditValues($role->refresh()),
        );

        return to_route('roles.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        $this->authorize('create', Role::class);

        $data = $request->validated();
        $role = Role::create([
            'name' => Arr::get($data, 'name'),
            'guard_name' => 'web',
        ]);

        $this->recordAudit(
            request: $request,
            auditable: $role,
            event: 'created',
            oldValues: [],
            newValues: $this->roleAuditValues($role),
        );

        return to_route('roles.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function roleAuditValues(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function recordAudit(
        Request $request,
        Model $auditable,
        string $event,
        array $oldValues,
        array $newValues,
    ): void {
        $user = $request->user();

        Audit::create([
            'user_type' => $user?->getMorphClass(),
            'user_id' => $user?->getKey(),
            'event' => $event,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tags' => null,
        ]);
    }
}
