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
        'employee_created' => 'wh.events.s2.employee.created',
        'employee_updated' => 'wh.events.s2.employee.updated',
        'employee_archived' => 'wh.events.s2.employee.archived',
        'payroll_run_approved' => 'wh.events.s2.payroll_run.approved',
        'leave_approved' => 'wh.events.s2.leave.approved',
        'severance_calculated' => 'wh.events.s2.severance.calculated',
    ],
];
