<?php

namespace App\Http\Controllers;

use App\Models\ReportCacheLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->databaseCheck(),
            'redis' => $this->redisCheck(),
        ];

        $healthy = collect($checks)->every(fn (array $check) => $check['status'] === 'ok' || $check['status'] === 'skipped');

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'system' => 'S4',
            'version' => config('app.version', '1.0'),
            'checks' => $checks,
            'observability' => [
                'report_cache_log' => [
                    'hits_last_hour' => ReportCacheLog::query()
                        ->where('event', 'hit')
                        ->where('cached_at', '>=', now()->subHour())
                        ->count(),
                    'invalidations_last_hour' => ReportCacheLog::query()
                        ->where('event', 'invalidated')
                        ->where('invalidated_at', '>=', now()->subHour())
                        ->count(),
                ],
            ],
        ], $healthy ? 200 : 503);
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function databaseCheck(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1 as ok');

            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Database unreachable'];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function redisCheck(): array
    {
        if (config('cache.default') !== 'redis') {
            return ['status' => 'skipped', 'message' => 'Redis not configured as cache driver'];
        }

        try {
            $pong = Redis::connection()->ping();

            return ['status' => ($pong === true || $pong === 'PONG') ? 'ok' : 'error'];
        } catch (\Throwable) {
            return ['status' => 'error', 'message' => 'Redis unreachable'];
        }
    }
}
