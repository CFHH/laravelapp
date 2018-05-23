<?php

namespace App\Providers;

use Carbon\Carbon;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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

        Passport::routes();  //访问频率限制, passport的route有throttle，在Laravel\Passport\src\RouteRegistrar.php，function all()->forAccessTokens()
        Passport::tokensExpireIn(Carbon::now()->addDays(1));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(2));
        //Passport::revokeOtherTokens();
        //Passport::pruneRevokedTokens();
    }
}
