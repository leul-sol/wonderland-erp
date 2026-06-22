<?php

namespace App\Services;

use App\Models\OperationalEvent;
use Carbon\Carbon;

class EventConsumerService
{
    public function __construct(private readonly BiCacheService $cache)
    {
    }

    /**
     * @param  array<string, mixed>  $envelope
     */
    public function handle(string $channel, array $envelope): void
    {
        $payload = is_array($envelope['payload'] ?? null) ? $envelope['payload'] : [];

        OperationalEvent::query()->create([
            'channel' => $channel,
            'source_system' => (string) ($envelope['source_system'] ?? 'unknown'),
            'request_id' => $envelope['request_id'] ?? null,
            'payload' => $payload,
            'occurred_at' => isset($envelope['occurred_at'])
                ? Carbon::parse($envelope['occurred_at'])
                : now(),
        ]);

        match ($channel) {
            config('events.channels.permission_changed') => $this->cache->invalidateIntegrationCaches(),
            config('events.channels.payroll_run_approved'),
            config('events.channels.severance_calculated'),
            config('events.channels.leave_approved') => $this->cache->invalidateS2Caches(),
            config('events.channels.goods_received'),
            config('events.channels.purchase_order_approved'),
            config('events.channels.order_finalized'),
            config('events.channels.employee_consumption_period_closed'),
            config('events.channels.guest_checked_in'),
            config('events.channels.guest_checked_out'),
            config('events.channels.folio_settled') => $this->cache->invalidateS3Caches(),
            default => null,
        };
    }
}
