<?php

namespace App\Console\Commands;

use App\Services\EmployeeEventService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Redis;
use Throwable;

class ConsumeEmployeeEvents extends Command
{
    protected $signature = 'events:consume-s2';

    protected $description = 'Subscribe to S2 employee lifecycle events on the Redis bus';

    public function handle(EmployeeEventService $employees): int
    {
        $channels = [
            config('events.channels.employee_created'),
            config('events.channels.employee_updated'),
            config('events.channels.employee_archived'),
        ];

        $this->info('Listening on: '.implode(', ', $channels));

        while (true) {
            $redis = new Redis;

            try {
                if (! $redis->connect(config('events.redis_bus.host'), (int) config('events.redis_bus.port'), 2)) {
                    throw new \RuntimeException('Unable to connect to Redis event bus.');
                }

                $redis->subscribe($channels, function (Redis $client, string $channel, string $message) use ($employees) {
                    try {
                        $envelope = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
                        $payload = is_array($envelope['payload'] ?? null) ? $envelope['payload'] : [];

                        match ($channel) {
                            config('events.channels.employee_created') => $employees->handleCreated($payload),
                            config('events.channels.employee_updated') => $employees->handleUpdated($payload),
                            config('events.channels.employee_archived') => $employees->handleArchived($payload),
                            default => null,
                        };
                    } catch (Throwable $exception) {
                        NotificationService::notifySubscriberFailure($channel, $exception->getMessage());
                    }
                });
            } catch (Throwable $exception) {
                $this->error('Redis subscriber disconnected: '.$exception->getMessage());
                sleep(2);
            } finally {
                try {
                    $redis->close();
                } catch (Throwable) {
                }
            }
        }
    }
}
