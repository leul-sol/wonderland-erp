<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Consumption\MealOrderController;
use App\Http\Controllers\Consumption\PeriodController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Fb\MenuController;
use App\Http\Controllers\Fb\BillController;
use App\Http\Controllers\Fb\DiningTableController;
use App\Http\Controllers\Fb\MenuCategoryController;
use App\Http\Controllers\Fb\MenuItemController as FbMenuItemController;
use App\Http\Controllers\Fb\OrderController as FbOrderController;
use App\Http\Controllers\Fb\SettingsController as FbSettingsController;
use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\BiDashboardController;
use App\Http\Controllers\Finance\BiReportController;
use App\Http\Controllers\Finance\BudgetController;
use App\Http\Controllers\Finance\FiscalPeriodController;
use App\Http\Controllers\Finance\JournalController;
use App\Http\Controllers\Finance\PayableController;
use App\Http\Controllers\Finance\ReceivableController;
use App\Http\Controllers\Finance\ReportController;
use App\Http\Controllers\Finance\RtmController;
use App\Http\Controllers\Finance\UatController;
use App\Http\Controllers\FrontDesk\CashierShiftController;
use App\Http\Controllers\FrontDesk\CheckInController;
use App\Http\Controllers\FrontDesk\FolioController;
use App\Http\Controllers\FrontDesk\GuestProfileController;
use App\Http\Controllers\FrontDesk\ReservationController;
use App\Http\Controllers\FrontDesk\RoomController;
use App\Http\Controllers\FrontDesk\SettingsController as FrontDeskSettingsController;
use App\Http\Controllers\GroupBookings\GroupBookingController;
use App\Http\Controllers\Hr\AttendanceController;
use App\Http\Controllers\Hr\DepartmentController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\EmployeeDocumentController;
use App\Http\Controllers\Hr\EmployeeRecordController;
use App\Http\Controllers\Hr\LeaveRequestController;
use App\Http\Controllers\Hr\OffboardingController;
use App\Http\Controllers\Hr\OvertimeController;
use App\Http\Controllers\Hr\PositionController;
use App\Http\Controllers\Hr\SettingsController;
use App\Http\Controllers\Inventory\AlertController;
use App\Http\Controllers\Inventory\GoodsReceiptController;
use App\Http\Controllers\Inventory\ItemCategoryController;
use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\ValuationController;
use App\Http\Controllers\Payroll\PayrollRunController;
use App\Http\Controllers\Payroll\SeveranceController;
use App\Http\Controllers\ModulePlaceholderController;
use App\Http\Middleware\EnsurePortalAuthenticated;
use App\Http\Middleware\RedirectIfPortalAuthenticated;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/favicon.ico', function () {
    $logoPath = public_path(ltrim((string) config('brand.favicon', '/images/brand/logo.png'), '/'));

    abort_unless(is_file($logoPath), 404);

    return response()->file($logoPath, ['Content-Type' => 'image/png']);
});

Route::middleware(RedirectIfPortalAuthenticated::class)->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(EnsurePortalAuthenticated::class)->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/account/change-password', [ChangePasswordController::class, 'create'])
        ->name('account.change-password.create');
    Route::post('/account/change-password', [ChangePasswordController::class, 'store'])
        ->name('account.change-password.store');

    Route::middleware('portal.must_change_password')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/modules/{module}', ModulePlaceholderController::class)->name('modules.placeholder');

    Route::prefix('front-desk')->name('front-desk.')->group(function () {
        Route::redirect('/overview', '/finance/dashboard/hotel');

        Route::get('/rooms', [RoomController::class, 'index'])
            ->middleware('portal.permission:S3.hotel.rooms.read')
            ->name('rooms.index');
        Route::put('/rooms/{room}/status', [RoomController::class, 'updateStatus'])
            ->middleware('portal.permission:S3.hotel.rooms.write')
            ->name('rooms.status');

        Route::get('/reservations', [ReservationController::class, 'index'])
            ->middleware('portal.permission:S3.hotel.reservations.read')
            ->name('reservations.index');
        Route::get('/reservations/create', [ReservationController::class, 'create'])
            ->middleware('portal.permission:S3.hotel.reservations.write')
            ->name('reservations.create');
        Route::post('/reservations', [ReservationController::class, 'store'])
            ->middleware('portal.permission:S3.hotel.reservations.write')
            ->name('reservations.store');
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])
            ->middleware('portal.permission:S3.hotel.reservations.read')
            ->name('reservations.show');
        Route::post('/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn'])
            ->middleware('portal.permission:S3.hotel.checkinout.write')
            ->name('reservations.check-in');
        Route::put('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
            ->middleware('portal.permission:S3.hotel.reservations.write')
            ->name('reservations.cancel');
        Route::put('/reservations/{reservation}/no-show', [ReservationController::class, 'noShow'])
            ->middleware('portal.permission:S3.hotel.reservations.write')
            ->name('reservations.no-show');

        Route::get('/guests', [GuestProfileController::class, 'index'])
            ->middleware('portal.permission:S3.hotel.guests.read')
            ->name('guests.index');
        Route::get('/guests/create', [GuestProfileController::class, 'create'])
            ->middleware('portal.permission:S3.hotel.guests.write')
            ->name('guests.create');
        Route::post('/guests', [GuestProfileController::class, 'store'])
            ->middleware('portal.permission:S3.hotel.guests.write')
            ->name('guests.store');
        Route::get('/guests/{guest}/edit', [GuestProfileController::class, 'edit'])
            ->middleware('portal.permission:S3.hotel.guests.read')
            ->name('guests.edit');
        Route::put('/guests/{guest}', [GuestProfileController::class, 'update'])
            ->middleware('portal.permission:S3.hotel.guests.write')
            ->name('guests.update');

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
        Route::get('/folios/{folio}/invoice', [FolioController::class, 'invoice'])
            ->middleware('portal.permission:S3.hotel.folios.read')
            ->name('folios.invoice');
        Route::post('/folios/{folio}/charges', [FolioController::class, 'addCharge'])
            ->middleware('portal.permission:S3.hotel.folios.write')
            ->name('folios.charge');
        Route::post('/folios/{folio}/settle', [FolioController::class, 'settle'])
            ->middleware('portal.permission:S3.hotel.folios.write')
            ->name('folios.settle');
        Route::post('/folios/{folio}/check-out', [FolioController::class, 'checkOut'])
            ->middleware('portal.permission:S3.hotel.checkinout.write')
            ->name('folios.check-out');

        Route::get('/cashier-shifts', [CashierShiftController::class, 'index'])
            ->middleware('portal.permission:S3.hotel.cashier.read')
            ->name('cashier-shifts.index');
        Route::post('/cashier-shifts', [CashierShiftController::class, 'store'])
            ->middleware('portal.permission:S3.hotel.cashier.write')
            ->name('cashier-shifts.store');
        Route::get('/cashier-shifts/{cashierShift}', [CashierShiftController::class, 'show'])
            ->middleware('portal.permission:S3.hotel.cashier.read')
            ->name('cashier-shifts.show');
        Route::post('/cashier-shifts/{cashierShift}/close', [CashierShiftController::class, 'close'])
            ->middleware('portal.permission:S3.hotel.cashier.write')
            ->name('cashier-shifts.close');

        Route::get('/settings', [FrontDeskSettingsController::class, 'index'])
            ->middleware('portal.permission:S3.hotel.rooms.read')
            ->name('settings.index');
        Route::post('/settings/room-types', [FrontDeskSettingsController::class, 'storeRoomType'])
            ->middleware('portal.permission:S3.hotel.rooms.write')
            ->name('settings.room-types.store');
        Route::put('/settings/room-types/{roomType}', [FrontDeskSettingsController::class, 'updateRoomType'])
            ->middleware('portal.permission:S3.hotel.rooms.write')
            ->name('settings.room-types.update');

        Route::get('/settings/rooms', [FrontDeskSettingsController::class, 'rooms'])
            ->middleware('portal.permission:S3.hotel.rooms.read')
            ->name('settings.rooms');
        Route::post('/settings/rooms', [FrontDeskSettingsController::class, 'storeRoom'])
            ->middleware('portal.permission:S3.hotel.rooms.write')
            ->name('settings.rooms.store');
        Route::put('/settings/rooms/{room}', [FrontDeskSettingsController::class, 'updateRoom'])
            ->middleware('portal.permission:S3.hotel.rooms.write')
            ->name('settings.rooms.update');
    });

    Route::prefix('fb')->name('fb.')->group(function () {
        Route::redirect('/overview', '/finance/dashboard/restaurant');

        Route::get('/menu', [MenuController::class, 'index'])
            ->middleware('portal.permission:S3.restaurant.menu.read')
            ->name('menu.index');

        Route::get('/orders', [FbOrderController::class, 'index'])
            ->middleware('portal.permission:S3.restaurant.orders.read')
            ->name('orders.index');
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
        Route::put('/orders/{order}/cancel', [FbOrderController::class, 'cancel'])
            ->middleware('portal.permission:S3.restaurant.orders.write')
            ->name('orders.cancel');
        Route::delete('/orders/{order}/lines/{line}', [FbOrderController::class, 'removeLine'])
            ->middleware('portal.permission:S3.restaurant.orders.write')
            ->name('orders.line.remove');
        Route::post('/bills/{bill}/payments', [BillController::class, 'pay'])
            ->middleware('portal.permission:S3.restaurant.billing.write')
            ->name('bills.pay');

        Route::get('/settings', [FbSettingsController::class, 'index'])
            ->middleware('portal.permission:S3.restaurant.menu.read')
            ->name('settings.index');

        Route::get('/menu-categories', [MenuCategoryController::class, 'index'])
            ->middleware('portal.permission:S3.restaurant.menu.read')
            ->name('menu-categories.index');
        Route::post('/menu-categories', [MenuCategoryController::class, 'store'])
            ->middleware('portal.permission:S3.restaurant.menu.write')
            ->name('menu-categories.store');
        Route::put('/menu-categories/{menuCategory}', [MenuCategoryController::class, 'update'])
            ->middleware('portal.permission:S3.restaurant.menu.write')
            ->name('menu-categories.update');

        Route::get('/menu-items', [FbMenuItemController::class, 'index'])
            ->middleware('portal.permission:S3.restaurant.menu.read')
            ->name('menu-items.index');
        Route::get('/menu-items/create', [FbMenuItemController::class, 'create'])
            ->middleware('portal.permission:S3.restaurant.menu.write')
            ->name('menu-items.create');
        Route::post('/menu-items', [FbMenuItemController::class, 'store'])
            ->middleware('portal.permission:S3.restaurant.menu.write')
            ->name('menu-items.store');
        Route::get('/menu-items/{menuItem}/edit', [FbMenuItemController::class, 'edit'])
            ->middleware('portal.permission:S3.restaurant.menu.read')
            ->name('menu-items.edit');
        Route::put('/menu-items/{menuItem}', [FbMenuItemController::class, 'update'])
            ->middleware('portal.permission:S3.restaurant.menu.write')
            ->name('menu-items.update');
        Route::put('/menu-items/{menuItem}/recipe', [FbMenuItemController::class, 'updateRecipe'])
            ->middleware('portal.permission:S3.restaurant.menu.write')
            ->name('menu-items.recipe');

        Route::get('/dining-tables', [DiningTableController::class, 'index'])
            ->middleware('portal.permission:S3.restaurant.menu.read')
            ->name('dining-tables.index');
        Route::post('/dining-tables', [DiningTableController::class, 'store'])
            ->middleware('portal.permission:S3.restaurant.menu.write')
            ->name('dining-tables.store');
        Route::put('/dining-tables/{diningTable}', [DiningTableController::class, 'update'])
            ->middleware('portal.permission:S3.restaurant.menu.write')
            ->name('dining-tables.update');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/item-categories', [ItemCategoryController::class, 'index'])
            ->middleware('portal.permission:S3.inventory.items.read')
            ->name('item-categories.index');
        Route::post('/item-categories', [ItemCategoryController::class, 'store'])
            ->middleware('portal.permission:S3.inventory.items.write')
            ->name('item-categories.store');
        Route::put('/item-categories/{itemCategory}', [ItemCategoryController::class, 'update'])
            ->middleware('portal.permission:S3.inventory.items.write')
            ->name('item-categories.update');
        Route::delete('/item-categories/{itemCategory}', [ItemCategoryController::class, 'destroy'])
            ->middleware('portal.permission:S3.inventory.items.write')
            ->name('item-categories.destroy');

        Route::get('/items', [ItemController::class, 'index'])
            ->middleware('portal.permission:S3.inventory.items.read')
            ->name('items.index');
        Route::get('/items/create', [ItemController::class, 'create'])
            ->middleware('portal.permission:S3.inventory.items.write')
            ->name('items.create');
        Route::post('/items', [ItemController::class, 'store'])
            ->middleware('portal.permission:S3.inventory.items.write')
            ->name('items.store');
        Route::get('/items/{item}', [ItemController::class, 'show'])
            ->middleware('portal.permission:S3.inventory.items.read')
            ->name('items.show');
        Route::get('/items/{item}/edit', [ItemController::class, 'edit'])
            ->middleware('portal.permission:S3.inventory.items.write')
            ->name('items.edit');
        Route::put('/items/{item}', [ItemController::class, 'update'])
            ->middleware('portal.permission:S3.inventory.items.write')
            ->name('items.update');
        Route::post('/items/{item}/adjust', [ItemController::class, 'adjust'])
            ->middleware('portal.permission:S3.inventory.stock.write')
            ->name('items.adjust');
        Route::post('/items/{item}/write-off', [ItemController::class, 'writeOff'])
            ->middleware('portal.permission:S3.inventory.stock.write')
            ->name('items.write-off');
        Route::get('/alerts', [AlertController::class, 'index'])
            ->middleware('portal.permission:S3.inventory.items.read')
            ->name('alerts.index');
        Route::get('/valuation', [ValuationController::class, 'index'])
            ->middleware('portal.permission:S3.inventory.reports.read')
            ->name('valuation.index');
        Route::get('/suppliers', [SupplierController::class, 'index'])
            ->middleware('portal.permission:S3.inventory.suppliers.read')
            ->name('suppliers.index');
        Route::get('/suppliers/create', [SupplierController::class, 'create'])
            ->middleware('portal.permission:S3.inventory.suppliers.write')
            ->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])
            ->middleware('portal.permission:S3.inventory.suppliers.write')
            ->name('suppliers.store');
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])
            ->middleware('portal.permission:S3.inventory.suppliers.read')
            ->name('suppliers.show');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])
            ->middleware('portal.permission:S3.inventory.suppliers.write')
            ->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])
            ->middleware('portal.permission:S3.inventory.suppliers.write')
            ->name('suppliers.update');
        Route::post('/suppliers/{supplier}/payments', [SupplierController::class, 'pay'])
            ->middleware('portal.permission:S3.inventory.suppliers.write')
            ->name('suppliers.pay');

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

        Route::get('/goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'show'])
            ->middleware('portal.permission:S3.inventory.stock.read')
            ->name('goods-receipts.show');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])
            ->middleware('portal.permission:S4.finance.reports.read')
            ->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])
            ->middleware('portal.permission:S4.finance.reports.read,S4.bi.export.create')
            ->name('reports.export');

        Route::get('/journals', [JournalController::class, 'index'])
            ->middleware('portal.permission:S4.finance.journal_entries.read')
            ->name('journals.index');
        Route::get('/journals/create', [JournalController::class, 'create'])
            ->middleware('portal.permission:S4.finance.journal_entries.create')
            ->name('journals.create');
        Route::post('/journals', [JournalController::class, 'store'])
            ->middleware('portal.permission:S4.finance.journal_entries.create')
            ->name('journals.store');
        Route::get('/journals/{journalEntry}', [JournalController::class, 'show'])
            ->middleware('portal.permission:S4.finance.journal_entries.read')
            ->name('journals.show');
        Route::post('/journals/{journalEntry}/approve', [JournalController::class, 'approve'])
            ->middleware('portal.permission:S4.finance.journal_entries.approve')
            ->name('journals.approve');
        Route::post('/journals/{journalEntry}/reverse', [JournalController::class, 'reverse'])
            ->middleware('portal.permission:S4.finance.journal_entries.reverse')
            ->name('journals.reverse');
        Route::delete('/journals/{journalEntry}', [JournalController::class, 'destroy'])
            ->middleware('portal.permission:S4.finance.journal_entries.create')
            ->name('journals.destroy');

        Route::get('/fiscal-periods', [FiscalPeriodController::class, 'index'])
            ->middleware('portal.permission:S4.finance.fiscal_periods.read')
            ->name('fiscal-periods.index');
        Route::post('/fiscal-periods/open-next', [FiscalPeriodController::class, 'openNext'])
            ->middleware('portal.permission:S4.finance.fiscal_periods.create')
            ->name('fiscal-periods.open-next');
        Route::post('/fiscal-periods/{fiscalPeriod}/close', [FiscalPeriodController::class, 'close'])
            ->middleware('portal.permission:S4.finance.fiscal_periods.close')
            ->name('fiscal-periods.close');
        Route::post('/fiscal-periods/{fiscalPeriod}/lock', [FiscalPeriodController::class, 'lock'])
            ->middleware('portal.permission:S4.finance.fiscal_periods.lock')
            ->name('fiscal-periods.lock');

        Route::get('/receivables', [ReceivableController::class, 'index'])
            ->middleware('portal.permission:S4.finance.receivables.read')
            ->name('receivables.index');
        Route::post('/receivables/{receivable}/settle', [ReceivableController::class, 'settle'])
            ->middleware('portal.permission:S4.finance.receivables.settle')
            ->name('receivables.settle');
        Route::post('/receivables/{receivable}/write-off', [ReceivableController::class, 'writeOff'])
            ->middleware('portal.permission:S4.finance.receivables.settle')
            ->name('receivables.write-off');

        Route::get('/budget', [BudgetController::class, 'index'])
            ->middleware('portal.permission:S4.finance.budgets.read,S4.bi.reports.read')
            ->name('budget.index');
        Route::post('/budget/lines', [BudgetController::class, 'store'])
            ->middleware('portal.permission:S4.finance.budgets.create')
            ->name('budget.store');

        Route::get('/accounts', [AccountController::class, 'index'])
            ->middleware('portal.permission:S4.finance.accounts.read')
            ->name('accounts.index');
        Route::post('/accounts', [AccountController::class, 'store'])
            ->middleware('portal.permission:S4.finance.accounts.create')
            ->name('accounts.store');
        Route::put('/accounts/{account}', [AccountController::class, 'update'])
            ->middleware('portal.permission:S4.finance.accounts.update')
            ->name('accounts.update');

        Route::get('/dashboard/executive', [BiDashboardController::class, 'executive'])
            ->middleware('portal.permission:S4.bi.dashboards.read')
            ->name('dashboard.executive');
        Route::get('/dashboard/hotel', [BiDashboardController::class, 'hotel'])
            ->middleware('portal.permission:S4.bi.dashboards.read')
            ->name('dashboard.hotel');
        Route::get('/dashboard/restaurant', [BiDashboardController::class, 'restaurant'])
            ->middleware('portal.permission:S4.bi.dashboards.read')
            ->name('dashboard.restaurant');
        Route::get('/dashboard/finance', [BiDashboardController::class, 'finance'])
            ->middleware('portal.permission:S4.bi.dashboards.read')
            ->name('dashboard.finance');
        Route::get('/dashboard/operations', [BiDashboardController::class, 'operations'])
            ->middleware('portal.permission:S4.bi.dashboards.read')
            ->name('dashboard.operations');

        Route::get('/bi-reports', [BiReportController::class, 'index'])
            ->middleware('portal.permission:S4.bi.reports.read')
            ->name('bi-reports.index');
        Route::get('/bi-reports/{slug}', [BiReportController::class, 'show'])
            ->middleware('portal.permission:S4.bi.reports.read')
            ->name('bi-reports.show');
        Route::get('/bi-reports/{slug}/export', [BiReportController::class, 'export'])
            ->middleware('portal.permission:S4.bi.reports.read,S4.bi.export.create')
            ->name('bi-reports.export');

        Route::get('/rtm', [RtmController::class, 'index'])
            ->middleware('portal.permission:S4.bi.rtm.read')
            ->name('rtm.index');
        Route::put('/rtm/{rtmEntry}', [RtmController::class, 'update'])
            ->middleware('portal.permission:S4.bi.rtm.update')
            ->name('rtm.update');

        Route::get('/uat', [UatController::class, 'index'])
            ->middleware('portal.permission:S4.bi.uat.read')
            ->name('uat.index');
        Route::post('/uat/{uatScenario}/results', [UatController::class, 'recordResult'])
            ->middleware('portal.permission:S4.bi.uat.update')
            ->name('uat.record-result');

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
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])
            ->middleware('portal.permission:S2.workforce.employees.update')
            ->name('employees.edit');
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])
            ->middleware('portal.permission:S2.workforce.employees.update')
            ->name('employees.update');

        Route::post('/employees/{employee}/disciplinary-records', [EmployeeRecordController::class, 'storeDisciplinary'])
            ->middleware('portal.permission:S2.hr.disciplinary.write')
            ->name('employees.disciplinary.store');
        Route::post('/employees/{employee}/assets', [EmployeeRecordController::class, 'storeAsset'])
            ->middleware('portal.permission:S2.hr.assets.write')
            ->name('employees.assets.store');
        Route::put('/employees/{employee}/assets/{asset}/return', [EmployeeRecordController::class, 'returnAsset'])
            ->middleware('portal.permission:S2.hr.assets.write')
            ->name('employees.assets.return');
        Route::post('/employees/{employee}/guarantors', [EmployeeRecordController::class, 'storeGuarantor'])
            ->middleware('portal.permission:S2.hr.guarantors.write')
            ->name('employees.guarantors.store');
        Route::post('/employees/{employee}/loans', [EmployeeRecordController::class, 'storeLoan'])
            ->middleware('portal.permission:S2.workforce.loans.create')
            ->name('employees.loans.store');
        Route::get('/employees/{employee}/payslip/{payrollRun}', [EmployeeDocumentController::class, 'payslip'])
            ->middleware('portal.permission:S2.payroll.payslips.read')
            ->name('employees.payslip');
        Route::get('/employees/{employee}/guarantors/{guarantor}/letter', [EmployeeDocumentController::class, 'guarantorLetter'])
            ->middleware('portal.permission:S2.hr.guarantors.read')
            ->name('employees.guarantors.letter');

        Route::get('/departments', [DepartmentController::class, 'index'])
            ->middleware('portal.permission:S2.hr.departments.read')
            ->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])
            ->middleware('portal.permission:S2.hr.departments.write')
            ->name('departments.store');
        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])
            ->middleware('portal.permission:S2.hr.departments.write')
            ->name('departments.edit');
        Route::patch('/departments/{department}', [DepartmentController::class, 'update'])
            ->middleware('portal.permission:S2.hr.departments.write')
            ->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
            ->middleware('portal.permission:S2.hr.departments.write')
            ->name('departments.destroy');

        Route::get('/positions', [PositionController::class, 'index'])
            ->middleware('portal.permission:S2.workforce.positions.read')
            ->name('positions.index');
        Route::post('/positions', [PositionController::class, 'store'])
            ->middleware('portal.permission:S2.workforce.positions.create')
            ->name('positions.store');
        Route::get('/positions/{position}/edit', [PositionController::class, 'edit'])
            ->middleware('portal.permission:S2.workforce.positions.update')
            ->name('positions.edit');
        Route::patch('/positions/{position}', [PositionController::class, 'update'])
            ->middleware('portal.permission:S2.workforce.positions.update')
            ->name('positions.update');
        Route::delete('/positions/{position}', [PositionController::class, 'destroy'])
            ->middleware('portal.permission:S2.workforce.positions.delete')
            ->name('positions.destroy');

        Route::get('/leave-requests', [LeaveRequestController::class, 'index'])
            ->middleware('portal.permission:S2.workforce.leave_requests.read')
            ->name('leave.index');
        Route::post('/leave-requests', [LeaveRequestController::class, 'store'])
            ->middleware('portal.permission:S2.workforce.leave_requests.create')
            ->name('leave.store');
        Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])
            ->middleware('portal.permission:S2.workforce.leave_requests.approve')
            ->name('leave.approve');
        Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])
            ->middleware('portal.permission:S2.workforce.leave_requests.reject')
            ->name('leave.reject');
        Route::post('/leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])
            ->middleware('portal.permission:S2.workforce.leave_requests.create')
            ->name('leave.cancel');

        Route::get('/attendance', [AttendanceController::class, 'index'])
            ->middleware('portal.permission:S2.workforce.attendance.read')
            ->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])
            ->middleware('portal.permission:S2.workforce.attendance.create')
            ->name('attendance.store');

        Route::get('/overtime', [OvertimeController::class, 'index'])
            ->middleware('portal.permission:S2.workforce.overtime.read')
            ->name('overtime.index');
        Route::post('/overtime', [OvertimeController::class, 'store'])
            ->middleware('portal.permission:S2.workforce.overtime.create')
            ->name('overtime.store');
        Route::post('/overtime/{overtimeRecord}/approve', [OvertimeController::class, 'approve'])
            ->middleware('portal.permission:S2.workforce.overtime.approve')
            ->name('overtime.approve');

        Route::get('/offboarding', [OffboardingController::class, 'index'])
            ->middleware('portal.permission:S2.workforce.offboarding.read')
            ->name('offboarding.index');
        Route::post('/offboarding', [OffboardingController::class, 'store'])
            ->middleware('portal.permission:S2.workforce.offboarding.create')
            ->name('offboarding.store');
        Route::get('/offboarding/{offboarding}', [OffboardingController::class, 'show'])
            ->middleware('portal.permission:S2.workforce.offboarding.read')
            ->name('offboarding.show');
        Route::patch('/offboarding/{offboarding}', [OffboardingController::class, 'update'])
            ->middleware('portal.permission:S2.workforce.offboarding.update')
            ->name('offboarding.update');

        Route::get('/settings', [SettingsController::class, 'index'])
            ->middleware('portal.permission:S2.workforce.leave_types.read,S2.workforce.overtime.read,S2.hr.assets.read')
            ->name('settings.index');
        Route::patch('/settings/overtime-rates/{overtimeRate}', [SettingsController::class, 'updateOvertimeRate'])
            ->middleware('portal.permission:S2.workforce.overtime.update')
            ->name('settings.overtime-rates.update');
        Route::post('/settings/asset-types', [SettingsController::class, 'storeAssetType'])
            ->middleware('portal.permission:S2.hr.assets.write')
            ->name('settings.asset-types.store');
        Route::delete('/settings/asset-types/{assetType}', [SettingsController::class, 'destroyAssetType'])
            ->middleware('portal.permission:S2.hr.assets.write')
            ->name('settings.asset-types.destroy');
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
        Route::post('/runs/{payrollRun}/lock', [PayrollRunController::class, 'lock'])
            ->middleware('portal.permission:S2.workforce.payroll_runs.approve')
            ->name('runs.lock');

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

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])
            ->middleware('portal.permission:S1.identity.users.read')
            ->name('users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])
            ->middleware('portal.permission:S1.identity.users.create')
            ->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])
            ->middleware('portal.permission:S1.identity.users.create')
            ->name('users.store');
        Route::post('/users/{user}/deactivate', [AdminUserController::class, 'deactivate'])
            ->middleware('portal.permission:S1.identity.users.deactivate')
            ->name('users.deactivate');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])
            ->middleware('portal.permission:S1.identity.users.read')
            ->name('users.show');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])
            ->middleware('portal.permission:S1.identity.users.update')
            ->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])
            ->middleware('portal.permission:S1.identity.users.update')
            ->name('users.update');
        Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])
            ->middleware('portal.permission:S1.identity.users.reset_password')
            ->name('users.reset-password');
        Route::post('/users/{user}/force-logout', [AdminUserController::class, 'forceLogout'])
            ->middleware('portal.permission:S1.identity.users.force_logout')
            ->name('users.force-logout');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])
            ->middleware('portal.permission:S1.identity.users.delete')
            ->name('users.destroy');
        Route::post('/users/{user}/roles', [AdminUserController::class, 'syncRoles'])
            ->middleware('portal.permission:S1.identity.users.assign_role')
            ->name('users.roles');

        Route::get('/roles', [AdminRoleController::class, 'index'])
            ->middleware('portal.permission:S1.identity.roles.read')
            ->name('roles.index');
        Route::get('/roles/create', [AdminRoleController::class, 'create'])
            ->middleware('portal.permission:S1.identity.roles.create')
            ->name('roles.create');
        Route::post('/roles', [AdminRoleController::class, 'store'])
            ->middleware('portal.permission:S1.identity.roles.create')
            ->name('roles.store');
        Route::get('/roles/{role}/edit', [AdminRoleController::class, 'edit'])
            ->middleware('portal.permission:S1.identity.roles.update')
            ->name('roles.edit');
        Route::put('/roles/{role}', [AdminRoleController::class, 'update'])
            ->middleware('portal.permission:S1.identity.roles.update')
            ->name('roles.update');
        Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])
            ->middleware('portal.permission:S1.identity.roles.delete')
            ->name('roles.destroy');
        Route::get('/roles/{role}', [AdminRoleController::class, 'show'])
            ->middleware('portal.permission:S1.identity.roles.read')
            ->name('roles.show');
        Route::post('/roles/{role}/permissions', [AdminRoleController::class, 'syncPermissions'])
            ->middleware('portal.permission:S1.identity.roles.sync_permissions')
            ->name('roles.permissions');

        Route::get('/permissions', [AdminPermissionController::class, 'index'])
            ->middleware('portal.permission:S1.identity.permissions.read')
            ->name('permissions.index');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->middleware('portal.permission:S1.identity.audit_logs.read')
            ->name('audit.index');
        Route::get('/audit-logs/export', [AuditLogController::class, 'export'])
            ->middleware('portal.permission:S1.identity.audit_logs.read')
            ->name('audit.export');
    });

    Route::redirect('/procurement/purchase-orders', '/inventory/purchase-orders');
    Route::get('/procurement/purchase-orders/{purchaseOrder}', fn (int $purchaseOrder) => redirect()->route('inventory.purchase-orders.show', $purchaseOrder));
    });
});
