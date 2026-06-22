<?php

namespace App\Services;

use App\Models\EventOutbox;
use Illuminate\Support\Str;
use Redis;
use RuntimeException;
use Throwable;

class OutboxService
{
    public function enqueue(string $channel, array $payload): EventOutbox
    {
        return EventOutbox::query()->create([
            'event' => $channel,
            'payload' => $payload,
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => now(),
        ]);
    }

    public function publishPending(): int
    {
        $published = 0;
        $backoff = config('events.outbox.retry_backoff_seconds');
        $maxAttempts = count($backoff);

        $rows = EventOutbox::query()
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit(config('events.outbox.batch_size'))
            ->get();

        foreach ($rows as $row) {
            try {
                $this->publishToBus($row);
                $row->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                $published++;
            } catch (Throwable) {
                $row->increment('attempts');
                $row->refresh();

                if ($row->attempts >= $maxAttempts) {
                    $row->update(['status' => 'failed']);
                }
            }
        }

        return $published;
    }

    public function publishToBus(EventOutbox $row): void
    {
        $envelope = [
            'event' => $row->event,
            'version' => '1.0',
            'occurred_at' => now()->toIso8601String(),
            'source_system' => 'S2',
            'request_id' => (string) Str::uuid(),
            'payload' => $row->payload,
        ];

        $redis = $this->busConnection();
        $redis->publish($row->event, json_encode($envelope, JSON_THROW_ON_ERROR));
    }

    public function busConnection(): Redis
    {
        $redis = new Redis;
        $host = config('events.redis_bus.host');
        $port = config('events.redis_bus.port');

        if (! $redis->connect($host, $port, 2)) {
            throw new RuntimeException('Unable to connect to Redis event bus.');
        }

        return $redis;
    }
}
