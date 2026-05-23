<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\AIModel::class => \App\Policies\AIModelPolicy::class,
        \App\Models\Experiment::class => \App\Policies\ExperimentPolicy::class,
        \App\Models\Deployment::class => \App\Policies\DeploymentPolicy::class,
        \App\Models\Repository::class => \App\Policies\RepositoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manage-ai', function ($user = null) {
            if (! $user) return false;
            // Admin list can be set via AI_ADMINS env (comma-separated emails)
            $admins = array_filter(array_map('trim', explode(',', env('AI_ADMINS', ''))));
            if (in_array($user->email, $admins)) return true;
            // allow local development
            if (app()->environment('local')) return true;
            return false;
        });
    }
}
