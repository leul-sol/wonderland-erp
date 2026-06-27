<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Wonderland Hotel brand assets
    |--------------------------------------------------------------------------
    |
    | Place your logo files in public/images/brand/ (see README in that folder).
    | Override paths here or via .env when deploying.
    |
    */

    'name' => env('PORTAL_BRAND_NAME', 'Wonderland Hotel'),

    'product' => env('PORTAL_PRODUCT_NAME', 'Wonderland ERP'),

    'tagline' => env('PORTAL_BRAND_TAGLINE', 'Hospitality operations portal'),

    'logo' => env('PORTAL_LOGO_URL', '/images/brand/logo.png'),

    // Falls back to the main logo when you only have one file.
    'logo_mark' => env('PORTAL_LOGO_MARK_URL', env('PORTAL_LOGO_URL', '/images/brand/logo.png')),

    'favicon' => env('PORTAL_FAVICON_URL', env('PORTAL_LOGO_URL', '/images/brand/logo.png')),

];
