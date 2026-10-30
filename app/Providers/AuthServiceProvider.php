<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function register()
    {
        parent::register();

        // Apply these before Passport registers its routes or creates clients.
        Passport::$clientUuids = true;
        Passport::$registersJsonApiRoutes = true;
    }

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        // Keep the password grant available for existing OAuth clients.
        Passport::enablePasswordGrant();
        Passport::tokensExpireIn(now()->addMinutes(30));
        Passport::refreshTokensExpireIn(now()->addDays(3));
        

        //Passport::loadKeysFrom(__DIR__ . '/storage');
        // }
    }
}
