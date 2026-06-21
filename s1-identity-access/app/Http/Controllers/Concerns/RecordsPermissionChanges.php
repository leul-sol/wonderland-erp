<?php

namespace App\Http\Controllers\Concerns;

use App\Services\AuditService;
use App\Services\OutboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait RecordsPermissionChanges
{
    protected function recordPermissionChange(
        Request $request,
        int $roleId,
        array $permissionIds,
        string $action,
    ): void {
        DB::transaction(function () use ($request, $roleId, $permissionIds, $action) {
            app(OutboxService::class)->enqueuePermissionChanged($roleId, $permissionIds, $action);

            if ($request->attributes->get('auth_via_service_key')) {
                return;
            }

            AuditService::logFromRequest(
                $request,
                'permission.changed',
                $request->attributes->get('auth_user_id'),
                [
                    'role_id' => $roleId,
                    'permission_ids' => array_values($permissionIds),
                    'action' => $action,
                ],
            );
        });
    }
}
