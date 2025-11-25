<?php

use App\Http\Middleware\CheckAccessTokenExpiry;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Validation\Rules\Can;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    //global middleware
    ->withMiddleware(function (Middleware $middleware)
    {
        $middleware->alias([
            'trust_proxies'  =>TrustProxies::class,
            'cors'           =>HandleCors::class,
            'post_size'     =>ValidatePostSize::class,
            'convert_null'   => ConvertEmptyStringsToNull::class,
        ]);
    })
    //custom middleware
    ->withMiddleware(function (Middleware $middleware)
    {
        $middleware->group('api',[
           /* EnsureFrontendRequestsAreStateful::class,*/  //vetem ne rast perdorimi per cookies
            /*'throttle:api',*/
            SubstituteBindings::class,
        ]);

        $middleware->alias([
            'token_expiry'     => CheckAccessTokenExpiry::class,
            'verified'         => EnsureEmailIsVerified::class,
            'signed'           => ValidateSignature::class,
            'throttle'         => ThrottleRequests::class,
            'auth'             => Authenticate::class,
            'password.confirm' => RequirePassword::class,
            'can'              => Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
