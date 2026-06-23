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
        'guest_checked_in' => 'wh.events.s3.guest.checked_in',
        'guest_checked_out' => 'wh.events.s3.guest.checked_out',
        'folio_settled' => 'wh.events.s3.folio.settled',
        'goods_received' => 'wh.events.s3.goods.received',
        'purchase_order_approved' => 'wh.events.s3.purchase_order.approved',
        'purchase_order_cancelled' => 'wh.events.s3.purchase_order.cancelled',
        'order_finalized' => 'wh.events.s3.order.finalized',
        'employee_consumption_period_closed' => 'wh.events.s3.employee_consumption_period.closed',
        'stock_expiry_alert' => 'wh.events.s3.stock.expiry_alert',
        'employee_consumption_pushed' => 'wh.events.s3.employee_consumption.pushed',
    ],
];
