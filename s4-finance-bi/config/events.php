<?php

return [
    'redis_bus' => [
        'host' => env('REDIS_BUS_HOST', '127.0.0.1'),
        'port' => (int) env('REDIS_BUS_PORT', 6379),
    ],
    'channels' => [
        'permission_changed' => 'wh.events.s1.permission.changed',
        'payroll_run_approved' => 'wh.events.s2.payroll_run.approved',
        'severance_calculated' => 'wh.events.s2.severance.calculated',
        'leave_approved' => 'wh.events.s2.leave.approved',
        'goods_received' => 'wh.events.s3.goods.received',
        'purchase_order_approved' => 'wh.events.s3.purchase_order.approved',
        'order_finalized' => 'wh.events.s3.order.finalized',
        'employee_consumption_period_closed' => 'wh.events.s3.employee_consumption_period.closed',
        'guest_checked_in' => 'wh.events.s3.guest.checked_in',
        'guest_checked_out' => 'wh.events.s3.guest.checked_out',
        'folio_settled' => 'wh.events.s3.folio.settled',
    ],
];
