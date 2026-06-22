<?php

use App\Http\Controllers\FolioController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::middleware('jwt')->group(function () {
    Route::get('/rooms', [RoomController::class, 'index'])
        ->middleware('permission:S3.hospitality.rooms.read');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])
        ->middleware('permission:S3.hospitality.rooms.read');

    Route::get('/reservations', [ReservationController::class, 'index'])
        ->middleware('permission:S3.hospitality.reservations.read');
    Route::post('/reservations', [ReservationController::class, 'store'])
        ->middleware('permission:S3.hospitality.reservations.create');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])
        ->middleware('permission:S3.hospitality.reservations.read');
    Route::post('/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn'])
        ->middleware('permission:S3.hospitality.reservations.check_in');
    Route::post('/reservations/{reservation}/check-out', [ReservationController::class, 'checkOut'])
        ->middleware('permission:S3.hospitality.reservations.check_out');

    Route::get('/folios/{folio}', [FolioController::class, 'show'])
        ->middleware('permission:S3.hospitality.folios.read');
    Route::post('/folios/{folio}/charges', [FolioController::class, 'addCharge'])
        ->middleware('permission:S3.hospitality.folios.charge');
    Route::post('/folios/{folio}/settle', [FolioController::class, 'settle'])
        ->middleware('permission:S3.hospitality.folios.settle');
});
