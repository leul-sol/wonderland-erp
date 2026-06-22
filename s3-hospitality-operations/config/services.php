<?php

return [
    'internal_key_current' => env('INTERNAL_KEY_CURRENT'),
    'internal_key_previous' => env('INTERNAL_KEY_PREVIOUS'),
    's1_url' => env('S1_SERVICE_URL', 'http://s1-identity:9001'),
    's2_url' => env('S2_SERVICE_URL', 'http://s2-workforce:9002'),
    's4_url' => env('S4_SERVICE_URL', 'http://s4-finance-bi:9004'),
];
