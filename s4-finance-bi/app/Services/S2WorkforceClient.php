<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class S2WorkforceClient
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function employees(): array
    {
        return $this->cachedGet('s2.employees', 'employees', config('services.cache_ttl.s2_read'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function payrollRuns(): array
    {
        return $this->cachedGet('s2.payroll_runs', 'payroll-runs', config('services.cache_ttl.s2_read'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function leaveRequests(): array
    {
        return $this->cachedGet('s2.leave_requests', 'leave-requests', config('services.cache_ttl.s2_read'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cachedGet(string $cacheKey, string $path, int $ttl): array
    {
        return Cache::remember($cacheKey, $ttl, function () use ($path) {
            $url = rtrim((string) config('services.s2_url'), '/').'/api/v1/'.$path;

            try {
                $response = Http::withHeaders($this->headers())
                    ->acceptJson()
                    ->timeout(10)
                    ->get($url);
            } catch (\Throwable $exception) {
                throw new RuntimeException('S2 request failed: '.$exception->getMessage(), 0, $exception);
            }

            if (! $response->successful()) {
                throw new RuntimeException('S2 '.$path.' returned HTTP '.$response->status());
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
