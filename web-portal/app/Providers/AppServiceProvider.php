<?php

namespace App\Providers;

use App\Services\Api\S1AuthClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        if ($this->app->runningInConsole()) {
            return;
        }

        $this->configurePublicUrl(request());
    }

    /**
     * Cloudflare quick tunnel serves HTTPS while the gateway speaks HTTP on localhost.
     * Without this, @vite tags point at http://localhost/... and the browser blocks them.
     */
    private function configurePublicUrl(Request $request): void
    {
        if ($request->header('X-Forwarded-Proto') !== 'https') {
            return;
        }

        URL::forceScheme('https');

        $forwardedHost = $request->header('X-Forwarded-Host');
        if (is_string($forwardedHost) && $forwardedHost !== '') {
            URL::forceRootUrl('https://'.strtok($forwardedHost, ','));

            return;
        }

        Vite::createAssetPathsUsing(static function (string $path, ?bool $secure): string {
            return '/'.ltrim($path, '/');
        });
    }
}
