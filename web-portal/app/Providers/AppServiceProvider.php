<?php

namespace App\Providers;

use App\Services\Api\S1AuthClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(S1AuthClient::class, function () {
            return new S1AuthClient(config('portal.gateway_url'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
