<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Consumption\MealOrderController;
use App\Http\Controllers\Consumption\PeriodController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Fb\MenuController;
use App\Http\Controllers\Fb\OrderController as FbOrderController;
use App\Http\Controllers\Finance\PayableController;
use App\Http\Controllers\FrontDesk\CheckInController;
use App\Http\Controllers\FrontDesk\FolioController;
use App\Http\Controllers\FrontDesk\RoomController;
use App\Http\Controllers\GroupBookings\GroupBookingController;
use App\Http\Controllers\Hr\AttendanceController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\LeaveRequestController;
use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\Payroll\PayrollRunController;
use App\Http\Controllers\Payroll\SeveranceController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\ModulePlaceholderController;
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

    Route::prefix('fb')->name('fb.')->group(function () {
        Route::get('/menu', [MenuController::class, 'index'])
            ->middleware('portal.permission:S3.restaurant.menu.read')
            ->name('menu.index');

        Route::get('/orders/create', [FbOrderController::class, 'create'])
            ->middleware('portal.permission:S3.restaurant.orders.write')
            ->name('orders.create');
        Route::post('/orders', [FbOrderController::class, 'store'])
            ->middleware('portal.permission:S3.restaurant.orders.write')
            ->name('orders.store');
        Route::get('/orders/{order}', [FbOrderController::class, 'show'])
            ->middleware('portal.permission:S3.restaurant.orders.read,S3.restaurant.orders.write')
            ->name('orders.show');
        Route::post('/orders/{order}/lines', [FbOrderController::class, 'addLine'])
            ->middleware('portal.permission:S3.restaurant.orders.write')
            ->name('orders.line');
        Route::post('/orders/{order}/finalize', [FbOrderController::class, 'finalize'])
            ->middleware('portal.permission:S3.restaurant.orders.write')
            ->name('orders.finalize');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/items', [ItemController::class, 'index'])
            ->middleware('portal.permission:S3.inventory.items.read')
            ->name('items.index');
        Route::get('/suppliers', [SupplierController::class, 'index'])
            ->middleware('portal.permission:S3.inventory.suppliers.read')
            ->name('suppliers.index');

        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])
            ->middleware('portal.permission:S3.inventory.purchase_orders.read')
            ->name('purchase-orders.index');
        Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])
            ->middleware('portal.permission:S3.inventory.purchase_orders.write')
            ->name('purchase-orders.create');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])
            ->middleware('portal.permission:S3.inventory.purchase_orders.write')
            ->name('purchase-orders.store');
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
            ->middleware('portal.permission:S3.inventory.purchase_orders.read')
            ->name('purchase-orders.show');
        Route::post('/purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])
            ->middleware('portal.permission:S3.inventory.purchase_orders.write')
            ->name('purchase-orders.submit');
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])
            ->middleware('portal.permission:S3.inventory.purchase_orders.approve')
            ->name('purchase-orders.approve');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])
            ->middleware('portal.permission:S3.inventory.stock.write')
            ->name('purchase-orders.receive');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/payables', [PayableController::class, 'index'])
            ->middleware('portal.permission:S4.finance.payables.read')
            ->name('payables.index');
        Route::post('/payables/{payable}/settle', [PayableController::class, 'settle'])
            ->middleware('portal.permission:S4.finance.payables.settle')
            ->name('payables.settle');
    });

    Route::prefix('consumption')->name('consumption.')->group(function () {
        Route::get('/periods', [PeriodController::class, 'index'])
            ->middleware('portal.permission:S3.restaurant.consumption.read')
            ->name('periods.index');
        Route::post('/periods', [PeriodController::class, 'store'])
            ->middleware('portal.permission:S3.restaurant.consumption.write')
            ->name('periods.store');
        Route::post('/periods/{period}/close', [PeriodController::class, 'close'])
            ->middleware('portal.permission:S3.restaurant.consumption.write')
            ->name('periods.close');
        Route::post('/periods/{period}/orders', [PeriodController::class, 'createOrder'])
            ->middleware('portal.permission:S3.restaurant.consumption.write,S3.restaurant.orders.write')
            ->name('periods.order');

        Route::get('/orders/{order}', [MealOrderController::class, 'show'])
            ->middleware('portal.permission:S3.restaurant.consumption.read,S3.restaurant.orders.write')
            ->name('orders.show');
        Route::post('/orders/{order}/lines', [MealOrderController::class, 'addLine'])
            ->middleware('portal.permission:S3.restaurant.orders.write')
            ->name('orders.line');
        Route::post('/orders/{order}/finalize', [MealOrderController::class, 'finalize'])
            ->middleware('portal.permission:S3.restaurant.orders.write')
            ->name('orders.finalize');
    });

    Route::prefix('group-bookings')->name('group-bookings.')->group(function () {
        Route::get('/', [GroupBookingController::class, 'index'])
            ->middleware('portal.permission:S3.hotel.group_bookings.read')
            ->name('index');
        Route::get('/create', [GroupBookingController::class, 'create'])
            ->middleware('portal.permission:S3.hotel.group_bookings.create')
            ->name('create');
        Route::post('/', [GroupBookingController::class, 'store'])
            ->middleware('portal.permission:S3.hotel.group_bookings.create')
            ->name('store');
        Route::get('/{groupBooking}', [GroupBookingController::class, 'show'])
            ->middleware('portal.permission:S3.hotel.group_bookings.read')
            ->name('show');
        Route::post('/{groupBooking}/check-in', [GroupBookingController::class, 'checkIn'])
            ->middleware('portal.permission:S3.hotel.group_bookings.check_in')
            ->name('check-in');
        Route::post('/{groupBooking}/folios/{folio}/settle', [GroupBookingController::class, 'settleFolio'])
            ->middleware('portal.permission:S3.hotel.folios.write')
            ->name('folios.settle');
        Route::post('/{groupBooking}/check-out', [GroupBookingController::class, 'checkOut'])
            ->middleware('portal.permission:S3.hotel.group_bookings.check_out')
            ->name('check-out');
    });

    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])
            ->middleware('portal.permission:S2.workforce.employees.read')
            ->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])
            ->middleware('portal.permission:S2.workforce.employees.create')
            ->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])
            ->middleware('portal.permission:S2.workforce.employees.create')
            ->name('employees.store');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
            ->middleware('portal.permission:S2.workforce.employees.read')
            ->name('employees.show');

        Route::get('/leave-requests', [LeaveRequestController::class, 'index'])
            ->middleware('portal.permission:S2.workforce.leave_requests.read')
            ->name('leave.index');
        Route::post('/leave-requests', [LeaveRequestController::class, 'store'])
            ->middleware('portal.permission:S2.workforce.leave_requests.create')
            ->name('leave.store');
        Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])
            ->middleware('portal.permission:S2.workforce.leave_requests.approve')
            ->name('leave.approve');

        Route::get('/attendance', [AttendanceController::class, 'index'])
            ->middleware('portal.permission:S2.workforce.attendance.read')
            ->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])
            ->middleware('portal.permission:S2.workforce.attendance.create')
            ->name('attendance.store');
    });

    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/runs', [PayrollRunController::class, 'index'])
            ->middleware('portal.permission:S2.workforce.payroll_runs.read')
            ->name('runs.index');
        Route::get('/runs/create', [PayrollRunController::class, 'create'])
            ->middleware('portal.permission:S2.workforce.payroll_runs.create')
            ->name('runs.create');
        Route::post('/runs', [PayrollRunController::class, 'store'])
            ->middleware('portal.permission:S2.workforce.payroll_runs.create')
            ->name('runs.store');
        Route::get('/runs/{payrollRun}', [PayrollRunController::class, 'show'])
            ->middleware('portal.permission:S2.workforce.payroll_runs.read')
            ->name('runs.show');
        Route::post('/runs/{payrollRun}/submit', [PayrollRunController::class, 'submit'])
            ->middleware('portal.permission:S2.workforce.payroll_runs.create')
            ->name('runs.submit');
        Route::post('/runs/{payrollRun}/approve', [PayrollRunController::class, 'approve'])
            ->middleware('portal.permission:S2.workforce.payroll_runs.approve')
            ->name('runs.approve');

        Route::get('/severance', [SeveranceController::class, 'index'])
            ->middleware('portal.permission:S2.workforce.severance.read')
            ->name('severance.index');
        Route::post('/severance/calculate', [SeveranceController::class, 'calculate'])
            ->middleware('portal.permission:S2.workforce.severance.calculate')
            ->name('severance.calculate');
        Route::post('/severance/{severanceCalculation}/pay', [SeveranceController::class, 'pay'])
            ->middleware('portal.permission:S2.workforce.severance.pay')
            ->name('severance.pay');
    });

    Route::redirect('/procurement/purchase-orders', '/inventory/purchase-orders');
    Route::get('/procurement/purchase-orders/{purchaseOrder}', fn (int $purchaseOrder) => redirect()->route('inventory.purchase-orders.show', $purchaseOrder));
});
