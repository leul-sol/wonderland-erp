<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'system' => 'S3',
        'name' => 'Wonderland Hospitality Operations',
        'api' => '/api/v1/health',
    ]);
});
