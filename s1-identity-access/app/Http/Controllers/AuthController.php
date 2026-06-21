<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\JwtService;
use App\Services\PasswordPolicyService;
use App\Services\RefreshTokenService;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(
        private readonly JwtService $jwt,
        private readonly RefreshTokenService $refreshTokens,
        private readonly PasswordPolicyService $passwordPolicy,
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('username', $request->string('username'))->first();

        if ($user === null) {
            AuditService::logFromRequest($request, 'login.failed', null);

            return $this->error('UNAUTHENTICATED', 'Invalid credentials.', 401);
        }

        if (! $user->is_active) {
            return $this->error('FORBIDDEN', 'Account is deactivated.', 403);
        }

        if ($user->isLocked()) {
            return $this->error('FORBIDDEN', 'Account is temporarily locked.', 403);
        }

        if (! Hash::check($request->string('password'), $user->password)) {
            $user->failed_login_count++;
            if ($user->failed_login_count >= config('password_policy.max_failed_logins')) {
                $user->locked_until = now()->addMinutes(config('password_policy.lockout_minutes'));
            }
            $user->save();

            AuditService::logFromRequest($request, 'login.failed', $user->id);

            return $this->error('UNAUTHENTICATED', 'Invalid credentials.', 401);
        }

        if ($this->passwordPolicy->isExpired($user)) {
            $user->must_change_password = true;
            $user->save();
        }

        $user->failed_login_count = 0;
        $user->locked_until = null;
        $user->last_login_at = now();
        $user->last_login_ip = $request->ip();
        $user->save();

        $accessToken = $this->jwt->issueAccessToken($user);
        $refreshToken = $this->refreshTokens->issue($user, $request);

        AuditService::logFromRequest($request, 'login.success', $user->id);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'must_change_password' => (bool) $user->must_change_password,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $raw = $request->input('refresh_token');

        if (! is_string($raw) || $raw === '') {
            return $this->error('VALIDATION_ERROR', 'refresh_token is required.', 400);
        }

        try {
            [$user, $newRefresh] = $this->refreshTokens->rotate($raw, $request);
        } catch (Throwable) {
            return $this->error('UNAUTHENTICATED', 'Invalid refresh token.', 401);
        }

        if (! $user->is_active) {
            return $this->error('FORBIDDEN', 'Account is deactivated.', 403);
        }

        return response()->json([
            'access_token' => $this->jwt->issueAccessToken($user),
            'refresh_token' => $newRefresh,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $token = $this->bearerToken($request);

        if ($token === null) {
            return response()->json(['valid' => false], 401);
        }

        try {
            $payload = $this->jwt->decodeAccessToken($token);
        } catch (ExpiredException) {
            return response()->json(['valid' => false, 'reason' => 'expired'], 401);
        } catch (SignatureInvalidException) {
            return response()->json(['valid' => false, 'reason' => 'invalid_signature'], 401);
        } catch (Throwable) {
            return response()->json(['valid' => false], 401);
        }

        return response()->json([
            'valid' => true,
            'user' => [
                'roles' => $payload->roles ?? [],
                'permissions' => $payload->permissions ?? [],
                'name' => $payload->name ?? null,
                'employee_id' => $payload->employee_id ?? null,
                'dept_scope' => $payload->dept_scope ?? null,
                'sub' => $payload->sub ?? null,
                'username' => $payload->username ?? null,
            ],
        ]);
    }

    public function jwks(): JsonResponse
    {
        return response()->json($this->jwt->jwks());
    }

    public function logout(Request $request): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id');
        $this->refreshTokens->revokeAllForUser($userId);
        AuditService::logFromRequest($request, 'logout', $userId);

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->attributes->get('auth_user_id'));
        $user->load('roles');

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'name' => $user->display_name,
            'employee_id' => $user->employee_id,
            'roles' => $user->roleSlugs(),
            'permissions' => $user->permissionStrings(),
            'dept_scope' => $user->departmentScope(),
            'must_change_password' => $user->must_change_password,
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->attributes->get('auth_user_id'));

        if (! Hash::check($request->string('current_password'), $user->password)) {
            return $this->error('UNAUTHENTICATED', 'Current password is incorrect.', 401);
        }

        $this->passwordPolicy->assertValidNewPassword($user, $request->string('password'));
        $this->passwordPolicy->recordPasswordChange($user, $request->string('password'));
        $this->refreshTokens->revokeAllForUser($user->id);

        AuditService::logFromRequest($request, 'password.changed', $user->id);

        return response()->json(['message' => 'Password updated.']);
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (preg_match('/Bearer\s+(\S+)/', $header, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
