<?php

use App\Http\Controllers\BillController;
use App\Http\Controllers\CashierShiftController;
use App\Http\Controllers\DiningTableController;
use App\Http\Controllers\EmployeeConsumptionController;
use App\Http\Controllers\FolioController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\GroupBookingController;
use App\Http\Controllers\GuestProfileController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::middleware('jwt')->group(function () {
    // Inventory — §4.1
    Route::get('/item-categories', [ItemCategoryController::class, 'index'])
        ->middleware('permission:S3.inventory.items.read');
    Route::post('/item-categories', [ItemCategoryController::class, 'store'])
        ->middleware('permission:S3.inventory.items.write');
    Route::put('/item-categories/{itemCategory}', [ItemCategoryController::class, 'update'])
        ->middleware('permission:S3.inventory.items.write');
    Route::delete('/item-categories/{itemCategory}', [ItemCategoryController::class, 'destroy'])
        ->middleware('permission:S3.inventory.items.write');

    Route::get('/items', [InventoryItemController::class, 'index'])
        ->middleware('permission:S3.inventory.items.read');
    Route::post('/items', [InventoryItemController::class, 'store'])
        ->middleware('permission:S3.inventory.items.write');
    Route::get('/items/{item}', [InventoryItemController::class, 'show'])
        ->middleware('permission:S3.inventory.items.read');
    Route::put('/items/{item}', [InventoryItemController::class, 'update'])
        ->middleware('permission:S3.inventory.items.write');
    Route::get('/items/{item}/stock', [InventoryItemController::class, 'stock'])
        ->middleware('permission:S3.inventory.items.read');
    Route::get('/items/{item}/movements', [InventoryItemController::class, 'movements'])
        ->middleware('permission:S3.inventory.items.read');

    Route::post('/stock/adjustments', [StockController::class, 'adjust'])
        ->middleware('permission:S3.inventory.stock.write');
    Route::post('/stock/write-offs', [StockController::class, 'writeOff'])
        ->middleware('permission:S3.inventory.stock.write');
    Route::get('/stock/expiry-alerts', [StockController::class, 'expiryAlerts'])
        ->middleware('permission:S3.inventory.items.read');
    Route::get('/stock/low-stock-alerts', [StockController::class, 'lowStockAlerts'])
        ->middleware('permission:S3.inventory.items.read');
    Route::get('/stock/valuation', [StockController::class, 'valuation'])
        ->middleware('permission:S3.inventory.reports.read');

    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->middleware('permission:S3.inventory.suppliers.read');
    Route::post('/suppliers', [SupplierController::class, 'store'])
        ->middleware('permission:S3.inventory.suppliers.write');
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])
        ->middleware('permission:S3.inventory.suppliers.read');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:S3.inventory.suppliers.write');
    Route::post('/suppliers/{supplier}/payments', [SupplierController::class, 'recordPayment'])
        ->middleware(['permission:S3.inventory.suppliers.write', 'idempotent']);

    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])
        ->middleware('permission:S3.inventory.purchase_orders.read');
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])
        ->middleware('permission:S3.inventory.purchase_orders.write');
    Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
        ->middleware('permission:S3.inventory.purchase_orders.read');
    Route::post('/purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])
        ->middleware('permission:S3.inventory.purchase_orders.write');
    Route::put('/purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])
        ->middleware('permission:S3.inventory.purchase_orders.write');
    Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])
        ->middleware(['permission:S3.inventory.purchase_orders.approve', 'idempotent']);
    Route::put('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])
        ->middleware(['permission:S3.inventory.purchase_orders.approve', 'idempotent']);
    Route::put('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])
        ->middleware('permission:S3.inventory.purchase_orders.write');
    Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])
        ->middleware('permission:S3.inventory.stock.write');
    Route::post('/purchase-orders/{purchaseOrder}/goods-receipts', [PurchaseOrderController::class, 'receive'])
        ->middleware('permission:S3.inventory.stock.write');
    Route::get('/goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'show'])
        ->middleware('permission:S3.inventory.stock.read');

    // Restaurant — §4.2
    Route::get('/menu-categories', [MenuCategoryController::class, 'index'])
        ->middleware('permission:S3.restaurant.menu.read');
    Route::post('/menu-categories', [MenuCategoryController::class, 'store'])
        ->middleware('permission:S3.restaurant.menu.write');
    Route::put('/menu-categories/{menuCategory}', [MenuCategoryController::class, 'update'])
        ->middleware('permission:S3.restaurant.menu.write');

    Route::get('/menu-items', [MenuItemController::class, 'index'])
        ->middleware('permission:S3.restaurant.menu.read');
    Route::post('/menu-items', [MenuItemController::class, 'store'])
        ->middleware('permission:S3.restaurant.menu.write');
    Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show'])
        ->middleware('permission:S3.restaurant.menu.read');
    Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update'])
        ->middleware('permission:S3.restaurant.menu.write');
    Route::put('/menu-items/{menuItem}/recipe', [MenuItemController::class, 'updateRecipe'])
        ->middleware('permission:S3.restaurant.menu.write');

    Route::get('/dining-tables', [DiningTableController::class, 'index'])
        ->middleware('permission:S3.restaurant.orders.read');
    Route::post('/dining-tables', [DiningTableController::class, 'store'])
        ->middleware('permission:S3.restaurant.menu.write');
    Route::put('/dining-tables/{diningTable}', [DiningTableController::class, 'update'])
        ->middleware('permission:S3.restaurant.menu.write');

    Route::get('/orders', [OrderController::class, 'index'])
        ->middleware('permission:S3.restaurant.orders.read');
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('permission:S3.restaurant.orders.write');
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->middleware('permission:S3.restaurant.orders.read');
    Route::post('/orders/{order}/lines', [OrderController::class, 'addLine'])
        ->middleware('permission:S3.restaurant.orders.write');
    Route::post('/orders/{order}/items', [OrderController::class, 'addLine'])
        ->middleware('permission:S3.restaurant.orders.write');
    Route::delete('/orders/{order}/lines/{line}', [OrderController::class, 'removeLine'])
        ->middleware('permission:S3.restaurant.orders.write');
    Route::put('/orders/{order}/cancel', [OrderController::class, 'cancel'])
        ->middleware('permission:S3.restaurant.orders.write');
    Route::post('/orders/{order}/finalize', [OrderController::class, 'finalize'])
        ->middleware('permission:S3.restaurant.orders.write');

    Route::get('/bills/{bill}', [BillController::class, 'show'])
        ->middleware('permission:S3.restaurant.orders.read');
    Route::post('/bills/{bill}/payments', [BillController::class, 'pay'])
        ->middleware(['permission:S3.restaurant.billing.write', 'idempotent']);

    Route::get('/employee-consumption-periods', [EmployeeConsumptionController::class, 'index'])
        ->middleware('permission:S3.restaurant.consumption.read');
    Route::get('/employee-consumption', [EmployeeConsumptionController::class, 'index'])
        ->middleware('permission:S3.restaurant.consumption.read');
    Route::post('/employee-consumption-periods', [EmployeeConsumptionController::class, 'store'])
        ->middleware('permission:S3.restaurant.consumption.write');
    Route::post('/employee-consumption-periods/{employeeConsumptionPeriod}/close', [EmployeeConsumptionController::class, 'close'])
        ->middleware('permission:S3.restaurant.consumption.write');

    // Hotel — §4.3
    Route::get('/room-types', [RoomTypeController::class, 'index'])
        ->middleware('permission:S3.hotel.rooms.read');
    Route::post('/room-types', [RoomTypeController::class, 'store'])
        ->middleware('permission:S3.hotel.rooms.write');
    Route::put('/room-types/{roomType}', [RoomTypeController::class, 'update'])
        ->middleware('permission:S3.hotel.rooms.write');

    Route::get('/rooms', [RoomController::class, 'index'])
        ->middleware('permission:S3.hotel.rooms.read');
    Route::post('/rooms', [RoomController::class, 'store'])
        ->middleware('permission:S3.hotel.rooms.write');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])
        ->middleware('permission:S3.hotel.rooms.read');
    Route::put('/rooms/{room}', [RoomController::class, 'update'])
        ->middleware('permission:S3.hotel.rooms.write');
    Route::put('/rooms/{room}/status', [RoomController::class, 'updateStatus'])
        ->middleware('permission:S3.hotel.rooms.write');

    Route::get('/guest-profiles', [GuestProfileController::class, 'index'])
        ->middleware('permission:S3.hotel.guests.read');
    Route::post('/guest-profiles', [GuestProfileController::class, 'store'])
        ->middleware('permission:S3.hotel.guests.write');
    Route::get('/guest-profiles/{guestProfile}', [GuestProfileController::class, 'show'])
        ->middleware('permission:S3.hotel.guests.read');
    Route::put('/guest-profiles/{guestProfile}', [GuestProfileController::class, 'update'])
        ->middleware('permission:S3.hotel.guests.write');

    Route::get('/reservations', [ReservationController::class, 'index'])
        ->middleware('permission:S3.hotel.reservations.read');
    Route::post('/reservations', [ReservationController::class, 'store'])
        ->middleware('permission:S3.hotel.reservations.write');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])
        ->middleware('permission:S3.hotel.reservations.read');
    Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])
        ->middleware('permission:S3.hotel.reservations.write');
    Route::put('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
        ->middleware('permission:S3.hotel.reservations.write');
    Route::put('/reservations/{reservation}/no-show', [ReservationController::class, 'noShow'])
        ->middleware('permission:S3.hotel.reservations.write');
    Route::post('/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn'])
        ->middleware('permission:S3.hotel.checkinout.write');
    Route::post('/reservations/{reservation}/check-out', [ReservationController::class, 'checkOut'])
        ->middleware('permission:S3.hotel.checkinout.write');

    Route::get('/folios', [FolioController::class, 'index'])
        ->middleware('permission:S3.hotel.folios.read');
    Route::get('/folios/{folio}', [FolioController::class, 'show'])
        ->middleware('permission:S3.hotel.folios.read');
    Route::post('/folios/{folio}/charges', [FolioController::class, 'addCharge'])
        ->middleware('permission:S3.hotel.folios.write');
    Route::post('/folios/{folio}/payments', [FolioController::class, 'recordPayment'])
        ->middleware(['permission:S3.hotel.folios.write', 'idempotent']);
    Route::post('/folios/{folio}/settle', [FolioController::class, 'settle'])
        ->middleware('permission:S3.hotel.folios.write');
    Route::get('/folios/{folio}/invoice', [FolioController::class, 'invoice'])
        ->middleware('permission:S3.hotel.folios.read');

    Route::get('/cashier-shifts', [CashierShiftController::class, 'index'])
        ->middleware('permission:S3.hotel.cashier.read');
    Route::post('/cashier-shifts', [CashierShiftController::class, 'store'])
        ->middleware('permission:S3.hotel.cashier.write');
    Route::get('/cashier-shifts/{cashierShift}', [CashierShiftController::class, 'show'])
        ->middleware('permission:S3.hotel.cashier.read');
    Route::post('/cashier-shifts/{cashierShift}/close', [CashierShiftController::class, 'close'])
        ->middleware('permission:S3.hotel.cashier.write');
    Route::get('/cashier-shifts/{cashierShift}/report', [CashierShiftController::class, 'report'])
        ->middleware('permission:S3.hotel.cashier.read');

    Route::get('/group-bookings', [GroupBookingController::class, 'index'])
        ->middleware('permission:S3.hotel.group_bookings.read');
    Route::post('/group-bookings', [GroupBookingController::class, 'store'])
        ->middleware('permission:S3.hotel.group_bookings.create');
    Route::get('/group-bookings/{groupBooking}', [GroupBookingController::class, 'show'])
        ->middleware('permission:S3.hotel.group_bookings.read');
    Route::post('/group-bookings/{groupBooking}/check-in', [GroupBookingController::class, 'checkIn'])
        ->middleware('permission:S3.hotel.group_bookings.check_in');
    Route::post('/group-bookings/{groupBooking}/check-out', [GroupBookingController::class, 'checkOut'])
        ->middleware('permission:S3.hotel.group_bookings.check_out');
});
