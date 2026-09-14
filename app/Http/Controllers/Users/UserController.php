<?php

namespace App\Http\Controllers\Users;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = QueryBuilder::for(User::class)
            ->with('roles')
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);

                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }),
            )
            ->allowedSorts('name', 'email', 'created_at')
            ->defaultSort('-created_at')
            ->paginate($request->integer('pageSize', 15))
            ->withQueryString();

        return Inertia::render('users/Index', [
            'filters' => $this->filters($request),
            'roles' => Role::query()->where('name', '!=', RoleEnum::SuperAdmin->value)->get(),

            'usersResourceCollection' => UserResource::collection($users),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->syncRoles($validated['role_ids'] ?? []);

        activity('users')
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties([
                'roles' => $user->roles()->pluck('name')->all(),
            ])
            ->event('created')
            ->log('User created');

        return to_route('users.index');
    }

    public function show(User $user): Response
    {
        $user->load(['roles:id,name']);

        return Inertia::render('users/Show', [
            'user' => $this->userRecord($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();
        $user->syncRoles($validated['role_ids'] ?? []);

        activity('users')
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties([
                'roles' => $user->roles()->pluck('name')->all(),
            ])
            ->event('updated')
            ->log('User updated');

        return to_route('users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        abort_if($request->user()?->is($user), 403);

        $roles = $user->roles()->pluck('name')->all();

        activity('users')
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties([
                'roles' => $roles,
            ])
            ->event('deleted')
            ->log('User deleted');

        $user->delete();

        return to_route('users.index');
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

    /**
     * @return array{id: int, name: string, email: string, email_verified_at: ?string, created_at: string, updated_at: string, roles: array<int, array{id: int, name: string}>}
     */
    private function userRecord(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
            'role_ids' => $user->roles->pluck('id')->all(),
            'roles' => $user->roles->map(static fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
            ])->all(),
        ];
    }
}
