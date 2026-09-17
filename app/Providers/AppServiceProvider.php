<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Models\AiResponse;
use App\Models\AnnotationSource;
use App\Models\Role;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\AiResponsePolicy;
use App\Policies\AnnotationSourcePolicy;
use App\Policies\AuditPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use OwenIt\Auditing\Models\Audit;
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
        Gate::policy(AnnotationSource::class, AnnotationSourcePolicy::class);
        Gate::policy(AiResponse::class, AiResponsePolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Audit::class, AuditPolicy::class);
        Gate::policy(Activity::class, ActivityLogPolicy::class);
    }
}
