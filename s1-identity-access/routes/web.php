<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'system' => 'S1',
        'name' => 'Wonderland Identity & Access Platform',
        'api' => '/api/v1/health',
    ]);
});
