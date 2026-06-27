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
        return $this->navigation->build();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tasks(): array
    {
        return $this->tasks->build();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function menu(): array
    {
        return $this->menu->build();
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
        session()->forget('portal.nav_revision_seen');
    }

    public function syncRevision(): void
    {
        if (! $this->auth->isAuthenticated()) {
            return;
        }

        $revision = (string) config('portal.nav_revision', '1');
        $seen = session('portal.nav_revision_seen');

        if ($seen !== $revision) {
            self::forget();
            session(['portal.nav_revision_seen' => $revision]);
        }
    }
}
