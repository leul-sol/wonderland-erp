<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtAuthenticate::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'service.key' => \App\Http\Middleware\ServiceKeyAuthenticate::class,
            'access' => \App\Http\Middleware\AuthorizeAccess::class,
            'throttle.login' => \App\Http\Middleware\ThrottleFailedLogins::class,
            'password.change' => \App\Http\Middleware\EnforcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
