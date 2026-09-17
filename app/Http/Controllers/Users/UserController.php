<?php

namespace App\Http\Controllers\Users;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = QueryBuilder::for(User::class)
            ->with('roles')
            ->allowedFilters(
                AllowedFilter::scope('search'),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('email'),
                AllowedSort::field('created_at'),
            )
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
            'name' => Arr::get($validated, 'name'),
            'email' => Arr::get($validated, 'email'),
            'password' => Arr::get($validated, 'password'),
        ]);

        $user->syncRoles(Arr::get($validated, 'role_ids', []));

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

        $user->name = Arr::get($validated, 'name');
        $user->email = Arr::get($validated, 'email');

        $password = Arr::get($validated, 'password');

        if (filled($password)) {
            $user->password = $password;
        }

        $user->save();
        $user->syncRoles(Arr::get($validated, 'role_ids', []));

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
