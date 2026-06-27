<?php

return [
    'internal_key_current' => env('INTERNAL_KEY_CURRENT'),
    'internal_key_previous' => env('INTERNAL_KEY_PREVIOUS'),
    's1_url' => env('S1_SERVICE_URL', 'http://s1-identity:9001'),
    's2_url' => env('S2_SERVICE_URL', 'http://s2-workforce:9002'),
    's3_url' => env('S3_SERVICE_URL', 'http://s3-hospitality:9003'),
    'cache_ttl' => [
        'revenue' => (int) env('CACHE_TTL_REVENUE', 60),
        'occupancy' => (int) env('CACHE_TTL_OCCUPANCY', 60),
        'payroll' => (int) env('CACHE_TTL_PAYROLL', 300),
        'stock' => (int) env('CACHE_TTL_STOCK', 120),
        'employees' => (int) env('CACHE_TTL_EMPLOYEES', 300),
        'financial_reports' => (int) env('CACHE_TTL_FINANCIAL_REPORTS', 900),
        'default' => (int) env('REPORT_CACHE_DEFAULT_TTL', 300),
        's1_read' => (int) env('CACHE_TTL_EMPLOYEES', env('S4_CACHE_S1_SECONDS', 300)),
        's2_read' => (int) env('CACHE_TTL_PAYROLL', env('S4_CACHE_S2_SECONDS', 300)),
        's3_read' => (int) env('CACHE_TTL_OCCUPANCY', env('S4_CACHE_S3_SECONDS', 60)),
    ],
];
