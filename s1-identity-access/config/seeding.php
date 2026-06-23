<?php

return [
    'super_admin_password' => env('SUPER_ADMIN_PASSWORD', 'ChangeMeNow!10'),
    'admin_must_change_password' => filter_var(env('SEED_ADMIN_MUST_CHANGE_PASSWORD', true), FILTER_VALIDATE_BOOL),
];
