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
}
