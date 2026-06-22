<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Services\S1AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    use RespondsWithApiErrors;

    public function __construct(private readonly S1AuthService $s1Auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->hasValidServiceKey($request)) {
            $request->attributes->set('auth_via_service_key', true);
            $request->attributes->set('auth_user_id', 0);
            $request->attributes->set('auth_permissions', []);

            return $next($request);
        }

        $header = $request->header('Authorization', '');

        if (! preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
            return $this->error('UNAUTHENTICATED', 'Bearer token required.', 401);
        }

        $payload = $this->s1Auth->verify($matches[1]);

        if ($payload === null) {
            return $this->error('UNAUTHENTICATED', 'Invalid token.', 401);
        }

        $user = $payload['user'] ?? [];

        $request->attributes->set('auth_user_id', (int) ($user['sub'] ?? 0));
        $request->attributes->set('auth_permissions', $user['permissions'] ?? []);
        $request->attributes->set('auth_roles', $user['roles'] ?? []);
        $request->attributes->set('auth_dept_scope', $user['dept_scope'] ?? null);
        $request->attributes->set('auth_user', $user);

        return $next($request);
    }

    private function hasValidServiceKey(Request $request): bool
    {
        $key = $request->header('X-Service-Key');
        $current = config('services.internal_key_current');
        $previous = config('services.internal_key_previous');

        if ($key === null || $key === '') {
            return false;
        }

        return $key === $current || ($previous !== null && $previous !== '' && $key === $previous);
    }
}
