<?php

namespace App\Http\Middleware;

use App\Support\PermissionMenuBuilder;
use App\Support\SidebarNavBuilder;
use App\Support\TaskMenuBuilder;
use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly PermissionMenuBuilder $menu,
        private readonly TaskMenuBuilder $tasks,
        private readonly SidebarNavBuilder $navigation,
    ) {
    }

    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => 'Wonderland ERP',
            ],
            'auth' => [
                'user' => session('portal.user'),
                'must_change_password' => (bool) data_get(session('portal.user'), 'must_change_password', false),
            ],
            'menu' => fn () => $this->menu->build(),
            'tasks' => fn () => $this->tasks->build(),
            'navigation' => fn () => $this->navigation->build(),
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
