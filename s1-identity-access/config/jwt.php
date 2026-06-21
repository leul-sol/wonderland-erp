<?php

return [
    'algo' => env('JWT_ALGO', 'RS256'),
    'ttl' => (int) env('JWT_TTL', 60),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 43200),
    'issuer' => env('JWT_ISSUER', 'wonderland-identity'),
    'private_key_path' => env('JWT_PRIVATE_KEY_PATH', storage_path('secrets/jwt_private.pem')),
    'public_key_path' => env('JWT_PUBLIC_KEY_PATH', storage_path('secrets/jwt_public.pem')),
    'kid' => env('JWT_KID', 'wonderland-s1-primary'),
];
