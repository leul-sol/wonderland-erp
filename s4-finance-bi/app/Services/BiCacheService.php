<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class BiCacheService
{
    public function invalidate(?int $fiscalPeriodId = null): void
    {
        if (config('cache.default') === 'array') {
            return;
        }

        Cache::forget('finance.revenue.today');

        if ($fiscalPeriodId !== null) {
            Cache::forget('finance.period.'.$fiscalPeriodId.'.summary');
        }
    }

    public function invalidateS2Caches(): void
    {
        if (config('cache.default') === 'array') {
            return;
        }

        foreach (['s2.employees', 's2.payroll_runs', 's2.leave_requests', 's2.attendance_records'] as $key) {
            Cache::forget($key);
        }
    }

    public function invalidateS3Caches(): void
    {
        if (config('cache.default') === 'array') {
            return;
        }

        foreach (['s3.rooms', 's3.reservations', 's3.orders', 's3.items', 's3.purchase_orders'] as $key) {
            Cache::forget($key);
        }
    }

    public function invalidateIntegrationCaches(): void
    {
        $this->invalidateS1Caches();
        $this->invalidateS2Caches();
        $this->invalidateS3Caches();
    }

    public function invalidateS1Caches(): void
    {
        if (config('cache.default') === 'array') {
            return;
        }

        foreach (['s1.users', 's1.roles', 's1.audit_logs'] as $key) {
            Cache::forget($key);
        }
    }
}
