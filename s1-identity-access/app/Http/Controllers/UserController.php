<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RecordsPermissionChanges;
use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesUsers;
use App\Http\Requests\AssignUserRolesRequest;
use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PasswordPolicyService;
use App\Services\RefreshTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use RecordsPermissionChanges;
    use RespondsWithApiErrors;
    use SerializesUsers;

    public function __construct(
        private readonly PasswordPolicyService $passwordPolicy,
        private readonly RefreshTokenService $refreshTokens,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('roles');

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOL));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('username', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('display_name', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query->orderBy('username')->paginate(
            min((int) $request->input('per_page', 25), 100)
        );

        return response()->json($this->paginatedUsers($paginator));
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $this->userPayload($user, detailed: true)]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->passwordPolicy->assertPasswordMeetsPolicy($request->string('password'));

        $user = User::query()->create([
            'username' => $request->string('username'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
            'display_name' => $request->input('display_name'),
            'employee_id' => $request->input('employee_id'),
            'is_active' => $request->boolean('is_active', true),
            'must_change_password' => true,
            'password_changed_at' => now(),
        ]);

        AuditService::logFromRequest($request, 'user.created', $request->attributes->get('auth_user_id'), [
            'target_user_id' => $user->id,
            'username' => $user->username,
        ]);

        return response()->json(['data' => $this->userPayload($user)], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->fill($request->validated());
        $user->save();

        AuditService::logFromRequest($request, 'user.updated', $request->attributes->get('auth_user_id'), [
            'target_user_id' => $user->id,
            'username' => $user->username,
        ]);

        return response()->json(['data' => $this->userPayload($user->fresh(), detailed: true)]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($this->isProtectedSuperAdmin($user)) {
            return $this->error('UNPROCESSABLE', 'The super administrator account cannot be deleted.', 422);
        }

        $user->delete();
        $this->refreshTokens->revokeAllForUser($user->id);

        AuditService::logFromRequest($request, 'user.deleted', $request->attributes->get('auth_user_id'), [
            'target_user_id' => $user->id,
            'username' => $user->username,
        ]);

        return response()->json(['message' => 'User deleted.']);
    }

    public function deactivate(Request $request, User $user): JsonResponse
    {
        if ($this->isProtectedSuperAdmin($user)) {
            return $this->error('UNPROCESSABLE', 'The super administrator account cannot be deactivated.', 422);
        }

        $user->is_active = false;
        $user->save();
        $this->refreshTokens->revokeAllForUser($user->id);

        AuditService::logFromRequest($request, 'user.deactivated', $request->attributes->get('auth_user_id'), [
            'target_user_id' => $user->id,
            'username' => $user->username,
        ]);

        return response()->json(['data' => $this->userPayload($user->fresh(), detailed: true)]);
    }

    public function forceLogout(Request $request, User $user): JsonResponse
    {
        $this->refreshTokens->revokeAllForUser($user->id);

        AuditService::logFromRequest($request, 'user.force_logout', $request->attributes->get('auth_user_id'), [
            'target_user_id' => $user->id,
            'username' => $user->username,
        ]);

        return response()->json(['message' => 'Active sessions revoked.']);
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): JsonResponse
    {
        $this->passwordPolicy->assertValidNewPassword(
            $user,
            $request->string('password'),
            allowSameAsCurrent: true,
        );
        $this->passwordPolicy->recordPasswordChange($user, $request->string('password'));

        if ($request->boolean('must_change_password', true)) {
            $user->must_change_password = true;
            $user->save();
        }

        $this->refreshTokens->revokeAllForUser($user->id);

        AuditService::logFromRequest($request, 'password.reset', $request->attributes->get('auth_user_id'), [
            'target_user_id' => $user->id,
            'username' => $user->username,
        ]);

        return response()->json(['message' => 'Password reset.']);
    }

    public function assignRoles(AssignUserRolesRequest $request, User $user): JsonResponse
    {
        $sync = [];

        foreach ($request->input('roles') as $assignment) {
            $sync[(int) $assignment['role_id']] = [
                'department_id' => $assignment['department_id'] ?? null,
                'assigned_at' => now(),
                'assigned_by' => $request->attributes->get('auth_user_id'),
            ];
        }

        DB::transaction(function () use ($request, $user, $sync) {
            $user->roles()->sync($sync);

            foreach (array_keys($sync) as $roleId) {
                $permissionIds = Role::query()->find($roleId)?->permissions()->pluck('permissions.id')->all() ?? [];
                $this->recordPermissionChange($request, (int) $roleId, $permissionIds, 'grant');
            }
        });

        return response()->json(['data' => $this->userPayload($user->fresh(), detailed: true)]);
    }

    public function removeRole(Request $request, User $user, Role $role): JsonResponse
    {
        if (! $user->roles()->where('roles.id', $role->id)->exists()) {
            return $this->error('NOT_FOUND', 'Role is not assigned to this user.', 404);
        }

        DB::transaction(function () use ($request, $user, $role) {
            $permissionIds = $role->permissions()->pluck('permissions.id')->all();
            $user->roles()->detach($role->id);
            $this->recordPermissionChange($request, $role->id, $permissionIds, 'revoke');
        });

        return response()->json(['data' => $this->userPayload($user->fresh(), detailed: true)]);
    }

    private function isProtectedSuperAdmin(User $user): bool
    {
        return $user->roles()->where('name', 'super_admin')->exists();
    }
}
