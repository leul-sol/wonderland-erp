<?php

return [
    'internal_key_current' => env('INTERNAL_KEY_CURRENT'),
    'internal_key_previous' => env('INTERNAL_KEY_PREVIOUS'),
    's1_url' => env('S1_SERVICE_URL', 'http://s1-identity:9001'),
    's2_url' => env('S2_SERVICE_URL', 'http://s2-workforce:9002'),
    's3_url' => env('S3_SERVICE_URL', 'http://s3-hospitality:9003'),
    'cache_ttl' => [
        's1_read' => (int) env('S4_CACHE_S1_SECONDS', 300),
        's2_read' => (int) env('S4_CACHE_S2_SECONDS', 300),
        's3_read' => (int) env('S4_CACHE_S3_SECONDS', 60),
    ],
];
