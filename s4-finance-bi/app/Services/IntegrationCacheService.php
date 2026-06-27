<?php

namespace App\Services;

use App\Support\FinanceCacheContext;
use App\Support\FinanceCacheRegistry;
use Illuminate\Support\Facades\Cache;

class IntegrationCacheService
{
    public function __construct(private readonly BiCacheService $biCache)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function remember(string $key, int $ttl, callable $fetcher): array
    {
        FinanceCacheRegistry::register($key);

        $cached = Cache::get($key);
        if (is_array($cached)) {
            $this->biCache->recordHit($key, $ttl);

            return $cached;
        }

        try {
            /** @var array<int, array<string, mixed>> $value */
            $value = $fetcher();
            Cache::put($key, $value, $ttl);
            Cache::put($this->staleKey($key), $value);

            return $value;
        } catch (\Throwable) {
            $stale = Cache::get($this->staleKey($key));
            if (is_array($stale)) {
                FinanceCacheContext::markStale();

                return $stale;
            }

            throw new \RuntimeException('Integration cache miss for '.$key);
        }
    }

    private function staleKey(string $key): string
    {
        return $key.'::stale';
    }
}
