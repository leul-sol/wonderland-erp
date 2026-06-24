<?php

namespace App\Support;

use App\Services\Auth\PortalAuthService;
use Illuminate\Support\Facades\Route;

class TaskMenuBuilder
{
    public function __construct(
        private readonly PortalAuthService $auth,
    ) {
    }

    /**
     * @return list<array{key: string, label: string, href: string, module: string}>
     */
    public function build(): array
    {
        $items = [];

        foreach (config('portal.tasks', []) as $task) {
            if (! is_array($task)) {
                continue;
            }

            $permissions = $task['permissions'] ?? [];

            if (! $this->auth->hasAnyPermission(is_array($permissions) ? $permissions : [])) {
                continue;
            }

            $routeName = (string) ($task['route'] ?? '');
            $routeParams = is_array($task['route_params'] ?? null) ? $task['route_params'] : [];

            if (! Route::has($routeName)) {
                continue;
            }

            $items[] = [
                'key' => (string) ($task['key'] ?? 'task'),
                'label' => (string) ($task['label'] ?? 'Task'),
                'href' => route($routeName, $routeParams),
                'module' => (string) ($task['module'] ?? 'general'),
            ];
        }

        return $items;
    }
}
