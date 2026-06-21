<?php

return [
    'min_length' => (int) env('PASSWORD_MIN_LENGTH', 10),
    'require_upper' => filter_var(env('PASSWORD_REQUIRE_UPPER', true), FILTER_VALIDATE_BOOL),
    'expiry_days' => (int) env('PASSWORD_EXPIRY_DAYS', 90),
    'history' => (int) env('PASSWORD_HISTORY', 5),
    'max_failed_logins' => (int) env('MAX_FAILED_LOGINS', 5),
    'lockout_minutes' => (int) env('LOCKOUT_MINUTES', 30),
];
