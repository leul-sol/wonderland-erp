<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'system' => 'S4',
        'name' => 'Wonderland Finance & BI',
        'api' => '/api/v1/health',
    ]);
});
