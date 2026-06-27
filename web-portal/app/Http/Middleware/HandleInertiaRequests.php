<?php

namespace App\Http\Middleware;

use App\Services\Notifications\NotificationInboxService;
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
        private readonly NotificationInboxService $notifications,
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
        $navigationVersion = hash('xxh128', json_encode([
            'revision' => config('portal.nav_revision'),
            'modules' => config('portal.modules', []),
        ]));
        $shellVersion = hash('xxh128', implode('|', $permissions).'|'.$navigationVersion);

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
            'menu' => fn () => $this->shell->menu(),
            'tasks' => fn () => $this->shell->tasks(),
            'navigation' => fn () => $this->shell->navigation(),
            'shell' => [
                'nav_revision' => (string) config('portal.nav_revision', '1'),
                'notification_poll_seconds' => (int) config('portal.notifications.poll_interval_seconds', 120),
            ],
            'notifications' => fn () => $this->notifications->bellSummary($request->boolean('refresh_notifications')),
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
            $this->shell->syncRevision();
        }

        return parent::handle($request, $next);
    }
}
