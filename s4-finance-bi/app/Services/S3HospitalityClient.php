<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class S3HospitalityClient
{
    public function __construct(private readonly IntegrationCacheService $cache)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rooms(): array
    {
        return $this->cachedGet('s3.rooms', 'rooms', config('services.cache_ttl.occupancy'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function reservations(): array
    {
        return $this->cachedGet('s3.reservations', 'reservations', config('services.cache_ttl.occupancy'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function orders(): array
    {
        return $this->cachedGet('s3.orders', 'orders', config('services.cache_ttl.occupancy'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        return $this->cachedGet('s3.items', 'items', config('services.cache_ttl.stock'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function purchaseOrders(): array
    {
        return $this->cachedGet('s3.purchase_orders', 'purchase-orders', config('services.cache_ttl.stock'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function expiryAlerts(): array
    {
        return $this->cachedGet('s3.expiry_alerts', 'stock/expiry-alerts', config('services.cache_ttl.stock'));
    }

    /**
     * @return array<string, mixed>
     */
    public function stockValuation(): array
    {
        return $this->cachedObjectGet('s3.stock_valuation', 'stock/valuation', config('services.cache_ttl.stock'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function cashierShifts(): array
    {
        $payload = $this->cachedObjectGet('s3.cashier_shifts', 'cashier-shifts', config('services.cache_ttl.occupancy'));
        $rows = $payload['data'] ?? $payload;

        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function folios(?string $status = null): array
    {
        $cacheKey = 's3.folios'.($status !== null ? '.'.$status : '');

        return $this->cache->remember($cacheKey, config('services.cache_ttl.occupancy'), function () use ($status) {
            $url = rtrim((string) config('services.s3_url'), '/').'/api/v1/folios';
            $query = ['per_page' => 100];
            if ($status !== null) {
                $query['status'] = $status;
            }

            $response = Http::withHeaders($this->headers())
                ->acceptJson()
                ->timeout(10)
                ->get($url, $query);

            if (! $response->successful()) {
                throw new RuntimeException('S3 folios returned HTTP '.$response->status());
            }

            $data = $response->json('data');
            if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
                return array_values($data['data']);
            }

            return is_array($data) ? array_values($data) : [];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function folioInvoice(int $folioId): array
    {
        $url = rtrim((string) config('services.s3_url'), '/').'/api/v1/folios/'.$folioId.'/invoice';

        $response = Http::withHeaders($this->headers())
            ->acceptJson()
            ->timeout(10)
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('S3 folios/'.$folioId.'/invoice returned HTTP '.$response->status());
        }

        $data = $response->json('data');

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function consumptionPeriods(): array
    {
        return $this->cachedGet('s3.consumption_periods', 'employee-consumption-periods', config('services.cache_ttl.stock'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function supplierPayments(): array
    {
        return $this->cachedGet('s3.supplier_payments', 'supplier-payments', config('services.cache_ttl.stock'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function itemMovements(int $itemId, int $perPage = 25): array
    {
        $url = rtrim((string) config('services.s3_url'), '/').'/api/v1/items/'.$itemId.'/movements';

        $response = Http::withHeaders($this->headers())
            ->acceptJson()
            ->timeout(10)
            ->get($url, ['per_page' => $perPage]);

        if (! $response->successful()) {
            throw new RuntimeException('S3 items/'.$itemId.'/movements returned HTTP '.$response->status());
        }

        $data = $response->json('data');
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            return array_values($data['data']);
        }

        return is_array($data) ? array_values($data) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cachedGet(string $cacheKey, string $path, int $ttl): array
    {
        return $this->cache->remember($cacheKey, $ttl, function () use ($path) {
            $url = rtrim((string) config('services.s3_url'), '/').'/api/v1/'.$path;

            $response = Http::withHeaders($this->headers())
                ->acceptJson()
                ->timeout(10)
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException('S3 '.$path.' returned HTTP '.$response->status());
            }

            $data = $response->json('data');

            return is_array($data) ? $data : [];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function cachedObjectGet(string $cacheKey, string $path, int $ttl): array
    {
        $result = $this->cache->remember($cacheKey, $ttl, function () use ($path) {
            $url = rtrim((string) config('services.s3_url'), '/').'/api/v1/'.$path;

            $response = Http::withHeaders($this->headers())
                ->acceptJson()
                ->timeout(10)
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException('S3 '.$path.' returned HTTP '.$response->status());
            }

            $data = $response->json('data');

            return is_array($data) ? $data : [];
        });

        return is_array($result) ? $result : [];
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
