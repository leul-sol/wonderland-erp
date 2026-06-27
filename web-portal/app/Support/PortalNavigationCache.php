<?php

namespace App\Support;

use App\Services\Auth\PortalAuthService;

class PortalNavigationCache
{
    private const SESSION_KEY = 'portal.shell_cache';

    public function __construct(
        private readonly PortalAuthService $auth,
        private readonly SidebarNavBuilder $navigation,
        private readonly TaskMenuBuilder $tasks,
        private readonly PermissionMenuBuilder $menu,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function navigation(): array
    {
        return $this->remember('navigation', fn () => $this->navigation->build());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tasks(): array
    {
        return $this->remember('tasks', fn () => $this->tasks->build());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function menu(): array
    {
        return $this->remember('menu', fn () => $this->menu->build());
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @param  callable(): array<int, array<string, mixed>>  $builder
     * @return list<array<string, mixed>>
     */
    private function remember(string $section, callable $builder): array
    {
        if (! $this->auth->isAuthenticated()) {
            return $builder();
        }

        $fingerprint = $this->fingerprint();
        $cached = session(self::SESSION_KEY);

        if (
            is_array($cached)
            && ($cached['fingerprint'] ?? null) === $fingerprint
            && is_array($cached[$section] ?? null)
        ) {
            return $cached[$section];
        }

        $payload = is_array($cached) ? $cached : ['fingerprint' => $fingerprint];
        $payload['fingerprint'] = $fingerprint;
        $payload[$section] = $builder();
        session([self::SESSION_KEY => $payload]);

        return $payload[$section];
    }

    private function fingerprint(): string
    {
        $permissions = $this->auth->permissions();
        sort($permissions);

        return hash('xxh128', implode('|', $permissions));
    }
}
