<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        //\App\Http\Middleware\TrimStrings::class,
        //\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        //\App\Http\Middleware\TrustProxies::class,
        \App\Http\Middleware\CrossDomainAccess::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            //\App\Http\Middleware\EncryptCookies::class,
            //\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            //\Illuminate\Session\Middleware\StartSession::class,
            //// \Illuminate\Session\Middleware\AuthenticateSession::class,
            //\Illuminate\View\Middleware\ShareErrorsFromSession::class,
            //\App\Http\Middleware\VerifyCsrfToken::class,
            //\Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            //'mythrottle:60,1',  // 访问频率限制（一分钟内访问次数超过60次，1分钟内拒绝访问），这会使用auth.guards.web
            //'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        //'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        //'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        //'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        //'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        //'can' => \Illuminate\Auth\Middleware\Authorize::class,
        //'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        //'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        //'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        //'mythrottle' => \App\Http\Middleware\ThrottleRequests::class,
        'vpt' => \App\Http\Middleware\VerifyPassportToken::class,
        'vptex' => \App\Http\Middleware\VerifyPassportTokenEx::class,
        'passport_login' => \App\Http\Middleware\PatchOauthTokenLogin::class,
    ];
}
