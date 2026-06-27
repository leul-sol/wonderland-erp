<?php

namespace App\Services;

use App\Models\ReportCacheLog;
use App\Support\FinanceCacheRegistry;
use Illuminate\Support\Facades\Cache;

class BiCacheService
{
    /**
     * @var list<string>
     */
    private const INVALIDATION_PREFIXES = [
        'finance.revenue.',
        'finance.reports.',
    ];

    public function invalidate(?int $fiscalPeriodId = null, ?string $sourceEvent = null): void
    {
        $this->forgetKey('finance.revenue.today', $sourceEvent);

        if ($fiscalPeriodId !== null) {
            $this->forgetKeysForPrefix('finance.period.'.$fiscalPeriodId.'.', $sourceEvent);
            $this->forgetKeysForPrefix('finance.reports.', $sourceEvent);
        }

        foreach (self::INVALIDATION_PREFIXES as $prefix) {
            $this->forgetKeysForPrefix($prefix, $sourceEvent);
        }

        $this->invalidateS2Caches($sourceEvent);
        $this->invalidateS3Caches($sourceEvent);
    }

    public function invalidateS2Caches(?string $sourceEvent = null): void
    {
        foreach (['s2.employees', 's2.payroll_runs', 's2.leave_requests', 's2.attendance_records', 's2.overtime_records', 's2.offboarding_records'] as $key) {
            $this->forgetKey($key, $sourceEvent);
        }
    }

    public function invalidateS3Caches(?string $sourceEvent = null): void
    {
        foreach (['s3.rooms', 's3.reservations', 's3.orders', 's3.items', 's3.purchase_orders', 's3.folios', 's3.consumption_periods', 's3.supplier_payments'] as $key) {
            $this->forgetKey($key, $sourceEvent);
        }
    }

    public function invalidateIntegrationCaches(): void
    {
        $this->invalidateS1Caches();
        $this->invalidateS2Caches();
        $this->invalidateS3Caches();
    }

    public function invalidateS1Caches(?string $sourceEvent = null): void
    {
        foreach (['s1.users', 's1.roles', 's1.audit_logs'] as $key) {
            $this->forgetKey($key, $sourceEvent);
        }
    }

    public function recordHit(string $reportKey, int $ttlSeconds): void
    {
        ReportCacheLog::query()->create([
            'report_key' => $reportKey,
            'event' => 'hit',
            'ttl_seconds' => $ttlSeconds,
            'cached_at' => now(),
        ]);
    }

    private function forgetKeysForPrefix(string $prefix, ?string $sourceEvent): void
    {
        foreach (FinanceCacheRegistry::keysMatchingPrefix($prefix) as $key) {
            $this->forgetKey($key, $sourceEvent);
        }
    }

    private function forgetKey(string $key, ?string $sourceEvent): void
    {
        if (config('cache.default') !== 'array') {
            Cache::forget($key);
        }

        ReportCacheLog::query()->create([
            'report_key' => $key,
            'event' => 'invalidated',
            'source_event' => $sourceEvent,
            'invalidated_at' => now(),
        ]);
    }
}
