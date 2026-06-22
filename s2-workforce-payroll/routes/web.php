<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'system' => 'S2',
        'name' => 'Wonderland Workforce & Payroll',
        'api' => '/api/v1/health',
    ]);
});
