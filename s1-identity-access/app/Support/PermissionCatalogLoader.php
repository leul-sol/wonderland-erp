<?php

namespace App\Support;

use RuntimeException;

class PermissionCatalogLoader
{
    /**
     * @return array<int, array{action: string, domain: string, display_name: string, roles: array<int, string>}>
     */
    public function load(string $relativePath): array
    {
        $path = $this->resolvePath($relativePath);

        if (! is_readable($path)) {
            throw new RuntimeException("Permission catalog not found: {$relativePath}");
        }

        $content = file_get_contents($path);

        if (! is_string($content)) {
            throw new RuntimeException("Unable to read permission catalog: {$relativePath}");
        }

        $domain = $this->matchScalar($content, 'domain');
        $prefix = $this->matchScalar($content, 'prefix');
        $permissions = [];

        preg_match_all(
            '/- action:\s*(\S+)\s*\n\s+default_roles:\s*\[([^\]]*)\]/',
            $content,
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as $match) {
            $action = $match[1];
            if ($prefix !== null && ! preg_match('/^S\d+\./', $action)) {
                $action = rtrim($prefix, '.').'.'.$action;
            }

            $roles = array_values(array_filter(array_map(
                static fn (string $role) => trim($role),
                explode(',', $match[2]),
            )));

            $permissions[] = [
                'action' => $action,
                'domain' => $domain ?? $this->domainFromAction($action),
                'display_name' => $this->displayName($action),
                'roles' => $roles,
            ];
        }

        if ($permissions === []) {
            throw new RuntimeException("No permissions parsed from catalog: {$relativePath}");
        }

        return $permissions;
    }

    private function resolvePath(string $relativePath): string
    {
        $repoRoot = getenv('WONDERLAND_REPO_ROOT');
        if (is_string($repoRoot) && $repoRoot !== '') {
            $fromEnv = rtrim(str_replace('\\', '/', $repoRoot), '/').'/specs/'.$relativePath;
            if (is_readable($fromEnv)) {
                return $fromEnv;
            }
        }

        $candidates = [
            base_path('specs/'.$relativePath),
            base_path('../specs/'.$relativePath),
            dirname(base_path(), 2).'/specs/'.$relativePath,
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return $candidates[0];
    }

    private function matchScalar(string $content, string $key): ?string
    {
        if (preg_match('/^'.$key.':\s*(\S+)\s*$/m', $content, $match) !== 1) {
            return null;
        }

        return $match[1];
    }

    private function domainFromAction(string $action): string
    {
        $parts = explode('.', $action);

        return $parts[1] ?? 'unknown';
    }

    private function displayName(string $action): string
    {
        $suffix = preg_replace('/^S\d+\.\w+\./', '', $action) ?? $action;
        $segments = explode('.', $suffix);
        $verb = array_pop($segments);
        $resource = implode(' ', array_map(fn (string $part) => str_replace('_', ' ', $part), $segments));

        return ucfirst(str_replace('_', ' ', (string) $verb)).' '.$resource;
    }
}
