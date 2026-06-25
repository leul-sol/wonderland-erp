<?php

namespace App\Support;

use App\Services\Auth\PortalAuthService;
use Illuminate\Support\Facades\Route;

class SidebarNavBuilder
{
    public function __construct(
        private readonly PortalAuthService $auth,
    ) {
    }

    /**
     * @return list<array{key: string, label: string, items: list<array<string, mixed>>}>
     */
    public function build(): array
    {
        $modules = $this->buildModuleItems();

        if ($modules === []) {
            return [];
        }

        return [
            [
                'key' => 'modules',
                'label' => 'Modules',
                'items' => $modules,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildModuleItems(): array
    {
        $items = [];

        foreach (config('portal.modules', []) as $module) {
            if (! is_array($module)) {
                continue;
            }

            $permissions = is_array($module['permissions'] ?? null) ? $module['permissions'] : [];

            if ($permissions !== [] && ! $this->auth->hasAnyPermission($permissions)) {
                continue;
            }

            $children = $this->buildChildren($module['children'] ?? []);
            $routeName = (string) ($module['route'] ?? 'dashboard');
            $routeParams = is_array($module['route_params'] ?? null) ? $module['route_params'] : [];
            $moduleKey = (string) ($module['key'] ?? 'module');
            $href = null;

            if ($children === [] && Route::has($routeName)) {
                $href = route($routeName, $routeParams);
            }

            if ($children === [] && $href === null) {
                continue;
            }

            $items[] = [
                'key' => $moduleKey,
                'label' => (string) ($module['label'] ?? 'Module'),
                'icon' => (string) ($module['icon'] ?? $this->moduleIcon($moduleKey)),
                'href' => $href,
                'children' => $children,
                'phase' => (int) ($module['phase'] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * @param  mixed  $children
     * @return list<array{key: string, label: string, href: string}>
     */
    private function buildChildren(mixed $children): array
    {
        if (! is_array($children)) {
            return [];
        }

        $items = [];

        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }

            $permissions = is_array($child['permissions'] ?? null) ? $child['permissions'] : [];

            if ($permissions !== [] && ! $this->auth->hasAnyPermission($permissions)) {
                continue;
            }

            $routeName = (string) ($child['route'] ?? '');

            if ($routeName === '' || ! Route::has($routeName)) {
                continue;
            }

            $routeParams = is_array($child['route_params'] ?? null) ? $child['route_params'] : [];

            $items[] = [
                'key' => (string) ($child['key'] ?? 'child'),
                'label' => (string) ($child['label'] ?? 'Link'),
                'href' => route($routeName, $routeParams),
            ];
        }

        return $items;
    }

    private function moduleIcon(string $moduleKey): string
    {
        $icons = config('portal.module_icons', []);

        return is_array($icons) && isset($icons[$moduleKey])
            ? (string) $icons[$moduleKey]
            : 'circle';
    }
}
