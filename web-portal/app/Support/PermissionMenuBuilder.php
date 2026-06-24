<?php

namespace App\Support;

use App\Services\Auth\PortalAuthService;
use Illuminate\Support\Facades\Route;

class PermissionMenuBuilder
{
    public function __construct(
        private readonly PortalAuthService $auth,
    ) {
    }

    /**
     * @return list<array{key: string, label: string, href: string, phase: int}>
     */
    public function build(): array
    {
        $items = [];

        foreach (config('portal.modules', []) as $module) {
            if (! is_array($module)) {
                continue;
            }

            $permissions = $module['permissions'] ?? [];

            if (! $this->auth->hasAnyPermission(is_array($permissions) ? $permissions : [])) {
                continue;
            }

            $routeName = (string) ($module['route'] ?? 'dashboard');
            $routeParams = is_array($module['route_params'] ?? null) ? $module['route_params'] : [];

            $href = Route::has($routeName)
                ? route($routeName, $routeParams)
                : route('dashboard');

            $items[] = [
                'key' => (string) ($module['key'] ?? 'module'),
                'label' => (string) ($module['label'] ?? 'Module'),
                'href' => $href,
                'phase' => (int) ($module['phase'] ?? 0),
            ];
        }

        return $items;
    }
}
