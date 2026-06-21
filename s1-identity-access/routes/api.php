<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\OpenApiController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/openapi.json', OpenApiController::class);

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle.login');
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/jwks', [AuthController::class, 'jwks']);
    Route::post('/verify', [AuthController::class, 'verify'])->middleware('service.key');

    Route::middleware('jwt')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});

Route::get('/users', [UserController::class, 'index'])
    ->middleware('access:S1.identity.users.read');
Route::get('/users/{user}', [UserController::class, 'show'])
    ->middleware('access:S1.identity.users.read');

Route::middleware('jwt')->group(function () {
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:S1.identity.users.create');
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:S1.identity.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:S1.identity.users.delete');
    Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])
        ->middleware('permission:S1.identity.users.deactivate');
    Route::post('/users/{user}/force-logout', [UserController::class, 'forceLogout'])
        ->middleware('permission:S1.identity.users.force_logout');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('permission:S1.identity.users.reset_password');
    Route::match(['put', 'post'], '/users/{user}/roles', [UserController::class, 'assignRoles'])
        ->middleware('permission:S1.identity.users.assign_role');
    Route::delete('/users/{user}/roles/{role}', [UserController::class, 'removeRole'])
        ->middleware('permission:S1.identity.users.assign_role');
});

Route::get('/roles', [RoleController::class, 'index'])
    ->middleware('access:S1.identity.roles.read');
Route::get('/roles/{role}', [RoleController::class, 'show'])
    ->middleware('access:S1.identity.roles.read');

Route::middleware('jwt')->group(function () {
    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permission:S1.identity.roles.create');
    Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:S1.identity.roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:S1.identity.roles.delete');
    Route::match(['put', 'post'], '/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
        ->middleware('permission:S1.identity.roles.sync_permissions');
});

Route::get('/permissions', [PermissionController::class, 'index'])
    ->middleware('access:S1.identity.permissions.read');
Route::get('/permissions/{domain}', [PermissionController::class, 'byDomain'])
    ->middleware('access:S1.identity.permissions.read');

Route::get('/audit-logs', [AuditLogController::class, 'index'])
    ->middleware('access:S1.identity.audit_logs.read');
Route::get('/audit-logs/user/{user}', [AuditLogController::class, 'byUser'])
    ->middleware('access:S1.identity.audit_logs.read');
