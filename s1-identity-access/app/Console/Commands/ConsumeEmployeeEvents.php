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
        $redis = new Redis;
        $host = config('events.redis_bus.host');
        $port = config('events.redis_bus.port');

        if (! $redis->connect($host, $port, 2)) {
            $this->error('Unable to connect to Redis event bus.');

            return self::FAILURE;
        }

        $channels = [
            config('events.channels.employee_created'),
            config('events.channels.employee_updated'),
            config('events.channels.employee_archived'),
        ];

        $this->info('Listening on: '.implode(', ', $channels));

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

        return self::SUCCESS;
    }
}
