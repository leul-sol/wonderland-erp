<?php

namespace App\Support;

use RuntimeException;

class CoaCatalog
{
    /**
     * @return list<array{code: string, name: string, type: string, normal_balance: string}>
     */
    public static function accounts(): array
    {
        $path = self::resolveCoaPath();
        $accounts = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (! str_contains($line, 'code:')) {
                continue;
            }

            if (preg_match(
                '/code:\s*"([^"]+)",\s*name:\s*"([^"]+)",\s*type:\s*(\w+),\s*normal_balance:\s*(\w+)/',
                $line,
                $matches
            ) !== 1) {
                continue;
            }

            $accounts[] = [
                'code' => $matches[1],
                'name' => $matches[2],
                'type' => $matches[3],
                'normal_balance' => $matches[4],
            ];
        }

        if ($accounts === []) {
            throw new RuntimeException('Chart of accounts spec contains no accounts.');
        }

        return $accounts;
    }

    private static function resolveCoaPath(): string
    {
        $candidates = [];

        $repoRoot = env('WONDERLAND_REPO_ROOT');
        if (is_string($repoRoot) && $repoRoot !== '') {
            $candidates[] = rtrim($repoRoot, '/').'/specs/s4/coa.yaml';
        }

        $candidates[] = dirname(base_path()).'/specs/s4/coa.yaml';
        $candidates[] = dirname(base_path(), 2).'/specs/s4/coa.yaml';

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException(
            'Chart of accounts spec not found. Checked: '.implode(', ', $candidates)
        );
    }
}
