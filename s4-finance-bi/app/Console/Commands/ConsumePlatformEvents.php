<?php

namespace App\Console\Commands;

use App\Services\EventConsumerService;
use Illuminate\Console\Command;
use Redis;
use Throwable;

class ConsumePlatformEvents extends Command
{
    protected $signature = 'events:consume';

    protected $description = 'Subscribe to cross-system events for BI cache invalidation and operational logging';

    public function handle(EventConsumerService $consumer): int
    {
        $redis = new Redis;
        $host = config('events.redis_bus.host');
        $port = config('events.redis_bus.port');

        if (! $redis->connect($host, $port, 2)) {
            $this->error('Unable to connect to Redis event bus.');

            return self::FAILURE;
        }

        $channels = array_values(config('events.channels', []));

        $this->info('Listening on: '.implode(', ', $channels));

        $redis->subscribe($channels, function (Redis $client, string $channel, string $message) use ($consumer) {
            try {
                $envelope = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

                if (! is_array($envelope)) {
                    return;
                }

                $consumer->handle($channel, $envelope);
            } catch (Throwable $exception) {
                $this->error("Failed to process {$channel}: ".$exception->getMessage());
            }
        });

        return self::SUCCESS;
    }
}
