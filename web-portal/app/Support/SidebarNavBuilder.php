<?php

namespace App\Support;

use App\Services\Auth\PortalAuthService;
use Illuminate\Support\Facades\Route;

class SidebarNavBuilder
{
    /** @var list<string> */
    private const LEGACY_FINANCE_DASHBOARD_KEYS = [
        'executive',
        'hotel',
        'restaurant',
        'operations',
        'finance_dashboard',
        'dash_executive',
        'dash_hotel',
        'dash_restaurant',
        'dash_finance',
        'dash_operations',
    ];

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

            if ($moduleKey === 'finance') {
                $children = $this->normalizeFinanceChildren($children);
            }

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
                'active_prefixes' => is_array($module['active_prefixes'] ?? null) ? $module['active_prefixes'] : [],
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
                'active_prefixes' => is_array($child['active_prefixes'] ?? null) ? $child['active_prefixes'] : [],
            ];
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return list<array<string, mixed>>
     */
    private function normalizeFinanceChildren(array $children): array
    {
        $filtered = array_values(array_filter(
            $children,
            fn (array $child): bool => ! $this->isLegacyFinanceDashboardChild($child),
        ));

        $hasDashboards = false;

        foreach ($filtered as $child) {
            if (($child['key'] ?? '') === 'dashboards') {
                $hasDashboards = true;
                break;
            }
        }

        if (! $hasDashboards && $this->auth->hasAnyPermission(['S4.bi.dashboards.read']) && Route::has('finance.dashboard.executive')) {
            array_unshift($filtered, [
                'key' => 'dashboards',
                'label' => 'Dashboards',
                'href' => route('finance.dashboard.executive'),
                'active_prefixes' => ['/finance/dashboard'],
            ]);
        }

        return $filtered;
    }

    /**
     * @param  array<string, mixed>  $child
     */
    private function isLegacyFinanceDashboardChild(array $child): bool
    {
        $key = (string) ($child['key'] ?? '');

        if (in_array($key, self::LEGACY_FINANCE_DASHBOARD_KEYS, true)) {
            return true;
        }

        $label = strtolower((string) ($child['label'] ?? ''));
        $href = (string) ($child['href'] ?? '');

        if ($key !== 'dashboards' && str_ends_with($label, ' dashboard')) {
            return true;
        }

        if (preg_match('#/finance/dashboard/(executive|hotel|restaurant|finance|operations)(?:/|$|\?)#', $href) === 1 && $key !== 'dashboards') {
            return true;
        }

        return false;
    }

    private function moduleIcon(string $moduleKey): string
    {
        $icons = config('portal.module_icons', []);

        return is_array($icons) && isset($icons[$moduleKey])
            ? (string) $icons[$moduleKey]
            : 'circle';
    }
}
