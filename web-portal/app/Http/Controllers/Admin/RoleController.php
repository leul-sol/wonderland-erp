<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S1AdminClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S1AdminClient $s1,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s1->roles(['per_page' => 50]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $response['data'] ?? [],
            'canSyncPermissions' => $this->auth->hasAnyPermission(['S1.identity.roles.sync_permissions']),
        ]);
    }

    public function show(int $role): Response|RedirectResponse
    {
        try {
            $roleResponse = $this->s1->role($role);
            $permissionsResponse = $this->s1->permissions(['per_page' => 200]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'admin.roles.index');
        }

        $roleData = $roleResponse['data'] ?? [];
        $assignedIds = collect($roleData['permissions'] ?? [])->pluck('id')->all();

        return Inertia::render('Admin/Roles/Show', [
            'role' => $roleData,
            'permissions' => $permissionsResponse['data'] ?? [],
            'assignedPermissionIds' => $assignedIds,
            'canSyncPermissions' => $this->auth->hasAnyPermission(['S1.identity.roles.sync_permissions']),
        ]);
    }

    public function syncPermissions(Request $request, int $role): RedirectResponse
    {
        $data = $request->validate([
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer'],
        ]);

        $permissionIds = collect($data['permission_ids'])->map(fn ($id) => (int) $id)->values()->all();

        try {
            $this->s1->syncRolePermissions($role, $permissionIds);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Role permissions updated.');
    }
}
