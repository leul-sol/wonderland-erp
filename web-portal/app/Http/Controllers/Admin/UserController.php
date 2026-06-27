<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S1AdminClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S1AdminClient $s1,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $query = array_filter([
            'search' => $request->input('search'),
            'page' => $request->input('page'),
            'per_page' => 25,
        ], fn ($value) => $value !== null && $value !== '');

        return Inertia::render('Admin/Users/Index', [
            'search' => $request->input('search', ''),
            'canCreate' => $this->auth->hasAnyPermission(['S1.identity.users.create']),
            'canDeactivate' => $this->auth->hasAnyPermission(['S1.identity.users.deactivate']),
            'canAssignRoles' => $this->auth->hasAnyPermission(['S1.identity.users.assign_role']),
            'pageLoad' => $this->deferPageLoad(function () use ($query) {
                $response = $this->s1->users($query);

                return [
                    'users' => $response['data'] ?? [],
                    'meta' => $response['meta'] ?? null,
                ];
            }),
        ]);
    }

    public function show(Request $request, int $user): Response|RedirectResponse
    {
        try {
            $userResponse = $this->s1->user($user);
            $rolesResponse = $this->s1->roles(['per_page' => 50]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'admin.users.index');
        }

        $userData = $userResponse['data'] ?? [];
        $assignedRoleIds = collect($userData['roles'] ?? [])->pluck('id')->all();

        $canViewAudit = $this->auth->hasAnyPermission(['S1.identity.audit_logs.read']);
        $auditLogs = [];
        $auditMeta = null;
        $auditLoadError = null;
        $auditLoadErrorCode = null;

        if ($canViewAudit) {
            try {
                $auditPage = max(1, (int) $request->input('audit_page', 1));
                $auditResponse = $this->s1->auditLogsForUser($user, [
                    'per_page' => 25,
                    'page' => $auditPage,
                ]);
                $auditLogs = $auditResponse['data'] ?? [];
                $auditMeta = $auditResponse['meta'] ?? null;
            } catch (ApiException $e) {
                $auditLoadError = $e->getMessage();
                $auditLoadErrorCode = $e->errorCode;
            }
        }

        return Inertia::render('Admin/Users/Show', [
            'user' => $userData,
            'roles' => $rolesResponse['data'] ?? [],
            'assignedRoleIds' => $assignedRoleIds,
            'canAssignRoles' => $this->auth->hasAnyPermission(['S1.identity.users.assign_role']),
            'canUpdate' => $this->auth->hasAnyPermission(['S1.identity.users.update']),
            'canResetPassword' => $this->auth->hasAnyPermission(['S1.identity.users.reset_password']),
            'canForceLogout' => $this->auth->hasAnyPermission(['S1.identity.users.force_logout']),
            'canDelete' => $this->auth->hasAnyPermission(['S1.identity.users.delete']),
            'canDeactivate' => $this->auth->hasAnyPermission(['S1.identity.users.deactivate']),
            'canViewAudit' => $canViewAudit,
            'auditLogs' => $auditLogs,
            'auditMeta' => $auditMeta,
            'auditLoadError' => $auditLoadError,
            'auditLoadErrorCode' => $auditLoadErrorCode,
        ]);
    }

    public function edit(int $user): Response|RedirectResponse
    {
        try {
            $userResponse = $this->s1->user($user);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'admin.users.index');
        }

        return Inertia::render('Admin/Users/Edit', [
            'user' => $userResponse['data'] ?? [],
        ]);
    }

    public function update(Request $request, int $user): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'display_name' => ['nullable', 'string', 'max:150'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ]);

        $payload = [
            'email' => $data['email'],
            'display_name' => $data['display_name'] ?? null,
            'employee_id' => isset($data['employee_id']) ? (int) $data['employee_id'] : null,
            'is_active' => (bool) $data['is_active'],
        ];

        try {
            $this->s1->updateUser($user, $payload);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User updated.');
    }

    public function resetPassword(Request $request, int $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:10', 'confirmed'],
            'must_change_password' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->s1->resetUserPassword($user, [
                'password' => $data['password'],
                'must_change_password' => $request->boolean('must_change_password', true),
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Password reset. The user must sign in with the new password.');
    }

    public function forceLogout(int $user): RedirectResponse
    {
        try {
            $this->s1->forceLogoutUser($user);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'All active sessions for this user were revoked.');
    }

    public function destroy(int $user): RedirectResponse
    {
        try {
            $this->s1->deleteUser($user);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted.');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.users.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/'],
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:10'],
            'display_name' => ['nullable', 'string', 'max:150'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $this->s1->createUser([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'display_name' => $data['display_name'] ?? null,
                'employee_id' => isset($data['employee_id']) ? (int) $data['employee_id'] : null,
                'is_active' => true,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Platform user created.');
    }

    public function deactivate(int $user): RedirectResponse
    {
        try {
            $this->s1->deactivateUser($user);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'User deactivated.');
    }

    public function syncRoles(Request $request, int $user): RedirectResponse
    {
        $data = $request->validate([
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'min:1'],
        ]);

        $roles = collect($data['role_ids'])
            ->map(fn ($id) => ['role_id' => (int) $id])
            ->values()
            ->all();

        try {
            $this->s1->assignUserRoles($user, $roles);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'User roles updated.');
    }
}
