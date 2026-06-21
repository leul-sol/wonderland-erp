<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RecordsPermissionChanges;
use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\SyncRolePermissionsRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    use RecordsPermissionChanges;
    use RespondsWithApiErrors;

    public function index(Request $request): JsonResponse
    {
        $paginator = Role::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Role $role) => $this->rolePayload($role))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return response()->json(['data' => $this->rolePayload($role, detailed: true)]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::query()->create([
            ...$request->validated(),
            'is_system' => false,
        ]);

        AuditService::logFromRequest($request, 'role.created', $request->attributes->get('auth_user_id'), [
            'role_id' => $role->id,
            'name' => $role->name,
        ]);

        return response()->json(['data' => $this->rolePayload($role)], 201);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        if ($role->is_system && $request->filled('name') && $request->string('name') !== $role->name) {
            return $this->error('UNPROCESSABLE', 'System role slug cannot be changed.', 422);
        }

        $role->fill($request->validated());
        $role->save();

        AuditService::logFromRequest($request, 'role.updated', $request->attributes->get('auth_user_id'), [
            'role_id' => $role->id,
            'name' => $role->name,
        ]);

        return response()->json(['data' => $this->rolePayload($role->fresh(), detailed: true)]);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        if ($role->is_system) {
            return $this->error('FORBIDDEN', 'System roles cannot be deleted.', 403);
        }

        if ($role->users()->exists()) {
            return $this->error('UNPROCESSABLE', 'Remove users from this role before deleting it.', 422);
        }

        $role->permissions()->detach();
        $role->delete();

        AuditService::logFromRequest($request, 'role.deleted', $request->attributes->get('auth_user_id'), [
            'role_id' => $role->id,
            'name' => $role->name,
        ]);

        return response()->json(['message' => 'Role deleted.']);
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $sync = [];

        foreach ($request->input('permission_ids') as $permissionId) {
            $sync[(int) $permissionId] = [
                'granted_at' => now(),
                'granted_by' => $request->attributes->get('auth_user_id'),
            ];
        }

        DB::transaction(function () use ($request, $role, $sync) {
            $role->permissions()->sync($sync);
            $this->recordPermissionChange($request, $role->id, array_keys($sync), 'sync');
        });

        $role->load('permissions');

        return response()->json(['data' => $this->rolePayload($role, detailed: true)]);
    }

    private function rolePayload(Role $role, bool $detailed = false): array
    {
        $payload = [
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name,
            'description' => $role->description,
            'is_system' => $role->is_system,
            'permissions_count' => $role->permissions_count ?? $role->permissions?->count(),
            'created_at' => $role->created_at?->toIso8601String(),
            'updated_at' => $role->updated_at?->toIso8601String(),
        ];

        if ($detailed) {
            $payload['permissions'] = $role->permissions?->map(fn ($permission) => [
                'id' => $permission->id,
                'action' => $permission->action,
                'display_name' => $permission->display_name,
            ])->values()->all() ?? [];
        }

        return $payload;
    }
}
