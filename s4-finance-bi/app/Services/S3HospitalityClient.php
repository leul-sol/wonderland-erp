<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class S3HospitalityClient
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function rooms(): array
    {
        return $this->cachedGet('s3.rooms', 'rooms', config('services.cache_ttl.s3_read'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function reservations(): array
    {
        return $this->cachedGet('s3.reservations', 'reservations', config('services.cache_ttl.s3_read'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function orders(): array
    {
        return $this->cachedGet('s3.orders', 'orders', config('services.cache_ttl.s3_read'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cachedGet(string $cacheKey, string $path, int $ttl): array
    {
        return Cache::remember($cacheKey, $ttl, function () use ($path) {
            $url = rtrim((string) config('services.s3_url'), '/').'/api/v1/'.$path;

            try {
                $response = Http::withHeaders($this->headers())
                    ->acceptJson()
                    ->timeout(10)
                    ->get($url);
            } catch (\Throwable $exception) {
                throw new RuntimeException('S3 request failed: '.$exception->getMessage(), 0, $exception);
            }

            if (! $response->successful()) {
                throw new RuntimeException('S3 '.$path.' returned HTTP '.$response->status());
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
