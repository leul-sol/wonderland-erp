<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class S1IdentityClient
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function users(): array
    {
        return $this->cachedGet('s1.users', 'users', config('services.cache_ttl.s1_read'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function roles(): array
    {
        return $this->cachedGet('s1.roles', 'roles', config('services.cache_ttl.s1_read'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function auditLogs(): array
    {
        return $this->cachedGet('s1.audit_logs', 'audit-logs', config('services.cache_ttl.s1_read'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cachedGet(string $cacheKey, string $path, int $ttl): array
    {
        return Cache::remember($cacheKey, $ttl, function () use ($path) {
            $url = rtrim((string) config('services.s1_url'), '/').'/api/v1/'.$path;

            try {
                $response = Http::withHeaders($this->headers())
                    ->acceptJson()
                    ->timeout(10)
                    ->get($url);
            } catch (\Throwable $exception) {
                throw new RuntimeException('S1 request failed: '.$exception->getMessage(), 0, $exception);
            }

            if (! $response->successful()) {
                throw new RuntimeException('S1 '.$path.' returned HTTP '.$response->status());
            }

            $data = $response->json('data');

            return is_array($data) ? $data : [];
        });
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'X-Service-Key' => (string) config('services.internal_key_current'),
        ];
    }
}
