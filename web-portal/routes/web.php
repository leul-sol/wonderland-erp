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
use App\Http\Controllers\Fb\OrderController as FbOrderController;
use App\Http\Controllers\Finance\BiDashboardController;
use App\Http\Controllers\Finance\BudgetController;
use App\Http\Controllers\Finance\FiscalPeriodController;
use App\Http\Controllers\Finance\JournalController;
use App\Http\Controllers\Finance\PayableController;
use App\Http\Controllers\Finance\ReceivableController;
use App\Http\Controllers\Finance\ReportController;
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
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/account/change-password', [ChangePasswordController::class, 'create'])
        ->name('account.change-password.create');
    Route::post('/account/change-password', [ChangePasswordController::class, 'store'])
        ->name('account.change-password.store');

    Route::middleware('portal.must_change_password')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
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
        Route::delete('/journals/{journalEntry}', [JournalController::class, 'destroy'])
            ->middleware('portal.permission:S4.finance.journal_entries.create')
            ->name('journals.destroy');

        Route::get('/fiscal-periods', [FiscalPeriodController::class, 'index'])
            ->middleware('portal.permission:S4.finance.fiscal_periods.read')
            ->name('fiscal-periods.index');
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

        Route::get('/dashboard/executive', [BiDashboardController::class, 'executive'])
            ->middleware('portal.permission:S4.bi.dashboards.read')
            ->name('dashboard.executive');
        Route::get('/dashboard/operations', [BiDashboardController::class, 'operations'])
            ->middleware('portal.permission:S4.bi.dashboards.read')
            ->name('dashboard.operations');

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
