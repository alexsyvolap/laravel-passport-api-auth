<?php

namespace App\Providers;

use App\Enums\UserRoles;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::tokensCan([
            UserRoles::getInstance(UserRoles::Administrator)->key => 'Administrator roles scope',
            UserRoles::getInstance(UserRoles::Subscriber)->key => 'Subscriber roles scope',
        ]);

        Passport::routes();

        Passport::tokensExpireIn(now()->addDays(1));
        Passport::refreshTokensExpireIn(now()->addDays(2));
        Passport::personalAccessTokensExpireIn(now()->addMonths(1));
    }
}
