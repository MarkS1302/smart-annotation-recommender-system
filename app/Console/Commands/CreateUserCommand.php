<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('user:create {name? : The user name} {email? : The user email address} {--password= : The user password} {--password-confirmation= : The password confirmation} {--role=* : Role names to assign}')]
#[Description('Create a new user')]
class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create
        {name? : The user name}
        {email? : The user email address}
        {--password= : The user password}
        {--password-confirmation= : The password confirmation}
        {--role=* : Role names to assign}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    public function handle(): int
    {
        $name = $this->argument('name') ?: $this->ask('Name');
        $email = $this->argument('email') ?: $this->ask('Email');
        $password = (string) $this->option('password');
        $passwordConfirmation = (string) $this->option('password-confirmation');
        $roleNames = collect($this->option('role'))->filter()->values()->all();

        if (blank($password)) {
            $password = (string) $this->secret('Password');
        }

        if (blank($passwordConfirmation)) {
            $passwordConfirmation = (string) $this->secret('Password confirmation');
        }

        $validated = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
            'role_names' => $roleNames,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_names' => ['array'],
            'role_names.*' => [
                'string',
                Rule::exists(Role::class, 'name')->where(
                    fn ($query) => $query->where('guard_name', config('auth.defaults.guard', 'web')),
                ),
            ],
        ])->validate();

        /** @var User $user */
        $user = User::create([
            'name' => Arr::get($validated, 'name'),
            'email' => Arr::get($validated, 'email'),
            'password' => Arr::get($validated, 'password'),
        ]);

        if (filled(Arr::get($validated, 'role_names'))) {
            $user->syncRoles(
                Role::query()
                    ->whereIn('name', Arr::get($validated, 'role_names', []))
                    ->pluck('name')
                    ->all(),
            );
        }

        $this->components->info(sprintf('Created user: %s', $user->email));

        return self::SUCCESS;
    }
}
