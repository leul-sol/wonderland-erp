<?php

use App\Http\Controllers\EmployeeConsumptionController;
use App\Http\Controllers\FolioController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PurchaseOrderController;
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

    Route::get('/items', [InventoryItemController::class, 'index'])
        ->middleware('permission:S3.hospitality.items.read');
    Route::get('/items/{item}', [InventoryItemController::class, 'show'])
        ->middleware('permission:S3.hospitality.items.read');

    Route::get('/menu-items', [MenuItemController::class, 'index'])
        ->middleware('permission:S3.hospitality.menu_items.read');

    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])
        ->middleware('permission:S3.hospitality.purchase_orders.read');
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])
        ->middleware('permission:S3.hospitality.purchase_orders.create');
    Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
        ->middleware('permission:S3.hospitality.purchase_orders.read');
    Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])
        ->middleware('permission:S3.hospitality.purchase_orders.approve');
    Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])
        ->middleware('permission:S3.hospitality.purchase_orders.receive');

    Route::get('/orders', [OrderController::class, 'index'])
        ->middleware('permission:S3.hospitality.orders.read');
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('permission:S3.hospitality.orders.create');
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->middleware('permission:S3.hospitality.orders.read');
    Route::post('/orders/{order}/lines', [OrderController::class, 'addLine'])
        ->middleware('permission:S3.hospitality.orders.create');
    Route::post('/orders/{order}/finalize', [OrderController::class, 'finalize'])
        ->middleware('permission:S3.hospitality.orders.finalize');

    Route::get('/employee-consumption-periods', [EmployeeConsumptionController::class, 'index'])
        ->middleware('permission:S3.hospitality.consumption.read');
    Route::post('/employee-consumption-periods', [EmployeeConsumptionController::class, 'store'])
        ->middleware('permission:S3.hospitality.consumption.create');
    Route::post('/employee-consumption-periods/{employeeConsumptionPeriod}/close', [EmployeeConsumptionController::class, 'close'])
        ->middleware('permission:S3.hospitality.consumption.close');
});
