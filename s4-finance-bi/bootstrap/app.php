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
        $middleware->appendToGroup('api', \App\Http\Middleware\AppendIntegrationCacheHeaders::class);

        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtAuthenticate::class,
            'journal.post' => \App\Http\Middleware\JournalPostAuthenticate::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'service.key' => \App\Http\Middleware\ServiceKeyAuthenticate::class,
            'idempotency' => \App\Http\Middleware\EnsureIdempotencyHeader::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
