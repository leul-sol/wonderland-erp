<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontDesk\CheckInController;
use App\Http\Controllers\FrontDesk\FolioController;
use App\Http\Controllers\FrontDesk\RoomController;
use App\Http\Controllers\ModulePlaceholderController;
use App\Http\Controllers\Procurement\PurchaseOrderController;
use App\Http\Middleware\EnsurePortalAuthenticated;
use App\Http\Middleware\RedirectIfPortalAuthenticated;
use Illuminate\Support\Facades\Route;

Route::middleware(RedirectIfPortalAuthenticated::class)->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(EnsurePortalAuthenticated::class)->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/modules/{module}', ModulePlaceholderController::class)->name('modules.placeholder');

    Route::prefix('front-desk')->name('front-desk.')->group(function () {
        Route::get('/rooms', [RoomController::class, 'index'])
            ->middleware('portal.permission:S3.hotel.rooms.read')
            ->name('rooms.index');

        Route::get('/check-in', [CheckInController::class, 'create'])
            ->middleware('portal.permission:S3.hotel.checkinout.write,S3.hotel.reservations.write')
            ->name('check-in.create');
        Route::post('/check-in', [CheckInController::class, 'store'])
            ->middleware('portal.permission:S3.hotel.checkinout.write,S3.hotel.reservations.write')
            ->name('check-in.store');

        Route::get('/folios', [FolioController::class, 'index'])
            ->middleware('portal.permission:S3.hotel.folios.read')
            ->name('folios.index');
        Route::get('/folios/{folio}', [FolioController::class, 'show'])
            ->middleware('portal.permission:S3.hotel.folios.read')
            ->name('folios.show');
        Route::post('/folios/{folio}/charges', [FolioController::class, 'addCharge'])
            ->middleware('portal.permission:S3.hotel.folios.write')
            ->name('folios.charge');
        Route::post('/folios/{folio}/settle', [FolioController::class, 'settle'])
            ->middleware('portal.permission:S3.hotel.folios.write')
            ->name('folios.settle');
        Route::post('/folios/{folio}/check-out', [FolioController::class, 'checkOut'])
            ->middleware('portal.permission:S3.hotel.checkinout.write')
            ->name('folios.check-out');
    });

    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])
            ->middleware('portal.permission:S3.inventory.purchase_orders.read')
            ->name('purchase-orders.index');
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
            ->middleware('portal.permission:S3.inventory.purchase_orders.read')
            ->name('purchase-orders.show');
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])
            ->middleware('portal.permission:S3.inventory.purchase_orders.approve')
            ->name('purchase-orders.approve');
    });
});
