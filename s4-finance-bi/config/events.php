<?php

return [
    'redis_bus' => [
        'host' => env('REDIS_BUS_HOST', '127.0.0.1'),
        'port' => (int) env('REDIS_BUS_PORT', 6379),
    ],
    'outbox' => [
        'publish_interval_seconds' => 10,
        'retry_backoff_seconds' => [10, 60, 300, 1800, 7200],
        'batch_size' => 50,
    ],
    'channels' => [
        'journal_posted' => 'wh.events.s4.journal.posted',
        'period_closed' => 'wh.events.s4.period.closed',
    ],
];
