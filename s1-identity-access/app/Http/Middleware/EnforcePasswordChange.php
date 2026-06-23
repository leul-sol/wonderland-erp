<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\User;
use App\Services\PasswordPolicyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePasswordChange
{
    use RespondsWithApiErrors;

    public function __construct(private readonly PasswordPolicyService $passwordPolicy)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);

        if ($userId <= 0) {
            return $next($request);
        }

        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            return $this->error('UNAUTHENTICATED', 'User not found.', 401);
        }

        if (! $user->is_active) {
            return $this->error('FORBIDDEN', 'Account is deactivated.', 403);
        }

        if ($user->must_change_password || $this->passwordPolicy->isExpired($user)) {
            return $this->error(
                'PASSWORD_CHANGE_REQUIRED',
                'Password change required before accessing this resource.',
                403,
            );
        }

        return $next($request);
    }

    private function isExemptRoute(Request $request): bool
    {
        return $request->is(
            'api/v1/auth/change-password',
            'api/v1/auth/logout',
            'api/v1/auth/me',
        );
    }
}
