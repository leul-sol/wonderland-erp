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

    public function index(): Response
    {
        try {
            $response = $this->s1->roles(['per_page' => 50]);
        } catch (ApiException $e) {
            return Inertia::render('Admin/Roles/Index', [
                'roles' => [],
                'canCreate' => $this->auth->hasAnyPermission(['S1.identity.roles.create']),
                'canUpdate' => $this->auth->hasAnyPermission(['S1.identity.roles.update']),
                'canDelete' => $this->auth->hasAnyPermission(['S1.identity.roles.delete']),
                'canSyncPermissions' => $this->auth->hasAnyPermission(['S1.identity.roles.sync_permissions']),
                'canBrowsePermissions' => $this->auth->hasAnyPermission(['S1.identity.permissions.read']),
                ...$this->apiLoadErrorProps($e),
            ]);
        }

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $response['data'] ?? [],
            'canCreate' => $this->auth->hasAnyPermission(['S1.identity.roles.create']),
            'canUpdate' => $this->auth->hasAnyPermission(['S1.identity.roles.update']),
            'canDelete' => $this->auth->hasAnyPermission(['S1.identity.roles.delete']),
            'canSyncPermissions' => $this->auth->hasAnyPermission(['S1.identity.roles.sync_permissions']),
            'canBrowsePermissions' => $this->auth->hasAnyPermission(['S1.identity.permissions.read']),
            'loadError' => null,
            'loadErrorCode' => null,
        ]);
    }

    public function create(): RedirectResponse
    {
        if (! $this->auth->hasAnyPermission(['S1.identity.roles.create'])) {
            abort(403);
        }

        return redirect()->route('admin.roles.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $response = $this->s1->createRole([
                'name' => $data['name'],
                'display_name' => $data['display_name'],
                'description' => $data['description'] ?? null,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $roleId = $response['data']['id'] ?? null;

        if ($roleId) {
            return redirect()
                ->route('admin.roles.show', $roleId)
                ->with('success', 'Role created. Assign permissions below.');
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created.');
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
            'canUpdate' => $this->auth->hasAnyPermission(['S1.identity.roles.update']),
            'canDelete' => $this->auth->hasAnyPermission(['S1.identity.roles.delete']),
            'canSyncPermissions' => $this->auth->hasAnyPermission(['S1.identity.roles.sync_permissions']),
        ]);
    }

    public function edit(int $role): Response|RedirectResponse
    {
        try {
            $roleResponse = $this->s1->role($role);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'admin.roles.index');
        }

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $roleResponse['data'] ?? [],
        ]);
    }

    public function update(Request $request, int $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
        ];

        if (isset($data['name'])) {
            $payload['name'] = $data['name'];
        }

        try {
            $this->s1->updateRole($role, $payload);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', 'Role updated.');
    }

    public function destroy(int $role): RedirectResponse
    {
        try {
            $this->s1->deleteRole($role);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted.');
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
