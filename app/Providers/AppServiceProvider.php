<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\AuditPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Models\Role;
use Carbon\CarbonImmutable;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerPolicies();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Register application policies.
     */
    protected function registerPolicies(): void
    {
        Gate::before(static function (User $user): ?bool {
            return $user->hasRole(RoleEnum::SuperAdmin->value) ? true : null;
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Audit::class, AuditPolicy::class);
        Gate::policy(Activity::class, ActivityLogPolicy::class);
    }
}
