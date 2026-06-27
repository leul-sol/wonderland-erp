<?php

namespace App\Console\Commands;

use App\Services\BiCacheService;
use Illuminate\Console\Command;

class PurgeReportCache extends Command
{
    protected $signature = 's4:report-cache:purge';

    protected $description = 'Invalidate all S4 finance and integration report caches';

    public function handle(BiCacheService $cache): int
    {
        $cache->invalidate(null, 's4:report-cache:purge');
        $cache->invalidateIntegrationCaches();

        $this->info('Report and integration caches invalidated.');

        return self::SUCCESS;
    }
}
