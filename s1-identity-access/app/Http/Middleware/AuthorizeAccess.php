<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Services\JwtService;
use Closure;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAccess
{
    use RespondsWithApiErrors;

    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if ($this->hasValidServiceKey($request)) {
            $request->attributes->set('auth_via_service_key', true);

            return $next($request);
        }

        $header = $request->header('Authorization', '');

        if (! preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
            return $this->error('UNAUTHENTICATED', 'Bearer token or service key required.', 401);
        }

        try {
            $payload = $this->jwt->decodeAccessToken($matches[1]);
        } catch (ExpiredException) {
            return $this->error('UNAUTHENTICATED', 'Token expired.', 401);
        } catch (\Throwable) {
            return $this->error('UNAUTHENTICATED', 'Invalid token.', 401);
        }

        $permissions = $payload->permissions ?? [];
        $request->attributes->set('auth_user_id', (int) $payload->sub);
        $request->attributes->set('auth_permissions', $permissions);
        $request->attributes->set('auth_via_service_key', false);

        if (! in_array($permission, $permissions, true)) {
            return $this->error('FORBIDDEN', 'Insufficient permissions.', 403, ['required' => $permission]);
        }

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
