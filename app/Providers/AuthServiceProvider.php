<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Organisation;
use App\Models\Reunion;
use App\Policies\OrganisationPolicy;
use App\Policies\ReunionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Reunion::class => ReunionPolicy::class,
        Organisation::class => OrganisationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        
        $this->registerPolicies();

        //
    }
}
