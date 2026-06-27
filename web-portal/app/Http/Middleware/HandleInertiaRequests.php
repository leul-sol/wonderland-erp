<?php

namespace App\Http\Middleware;

use App\Support\PortalNavigationCache;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly PortalNavigationCache $shell,
    ) {
    }

    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        $base = parent::version($request) ?? '';
        $permissions = session('portal.permissions', []);

        if (! is_array($permissions)) {
            return $base !== '' ? $base : null;
        }

        sort($permissions);
        $shellVersion = hash('xxh128', implode('|', $permissions));

        return trim("{$base}-{$shellVersion}", '-');
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('brand.product'),
            ],
            'brand' => [
                'name' => config('brand.name'),
                'product' => config('brand.product'),
                'tagline' => config('brand.tagline'),
                'logo' => config('brand.logo'),
                'logo_mark' => config('brand.logo_mark'),
                'favicon' => config('brand.favicon'),
            ],
            'auth' => [
                'user' => session('portal.user'),
                'must_change_password' => (bool) data_get(session('portal.user'), 'must_change_password', false),
            ],
            'menu' => Inertia::once(fn () => $this->shell->menu()),
            'tasks' => Inertia::once(fn () => $this->shell->tasks()),
            'navigation' => Inertia::once(fn () => $this->shell->navigation()),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'error_detail' => fn () => $request->session()->get('error_detail'),
                'attendanceGap' => fn () => $request->session()->get('attendanceGap'),
            ],
        ];
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('portal.access_token')) {
            app(\App\Services\Auth\PortalAuthService::class)->ensureFreshToken();
        }

        return parent::handle($request, $next);
    }
}
