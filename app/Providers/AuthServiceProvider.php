<?php

namespace App\Providers;


use Laravel\Passport\Passport;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        
        $this->registerPolicies();
        Passport::tokensCan([
            'customer' => 'Customer Access',
            'admin' => 'Admin Access',
        ]);

        // Set access token expiration time using environment variables
        // Passport::tokensExpireIn(now()->addDays(config('app.passport_oauth_access_token'))); 

        // Set refresh token expiration time using environment variables
        // Passport::refreshTokensExpireIn(now()->addDays(env('PASSPORT_REFRESH_TOKENS_EXPIRE_IN')));

        // Set personal access token expiration time using environment variables
        // Passport::personalAccessTokensExpireIn(now()->addMonths(env('PASSPORT_PERSONAL_ACCESS_TOKENS_EXPIRE_IN')));

    }

}
