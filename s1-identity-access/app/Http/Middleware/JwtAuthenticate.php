<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Services\JwtService;
use Closure;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    use RespondsWithApiErrors;

    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (! preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
            return $this->error('UNAUTHENTICATED', 'Bearer token required.', 401);
        }

        try {
            $payload = $this->jwt->decodeAccessToken($matches[1]);
        } catch (ExpiredException) {
            return $this->error('UNAUTHENTICATED', 'Token expired.', 401);
        } catch (\Throwable) {
            return $this->error('UNAUTHENTICATED', 'Invalid token.', 401);
        }

        $request->attributes->set('auth_user_id', (int) $payload->sub);
        $request->attributes->set('auth_permissions', $payload->permissions ?? []);
        $request->attributes->set('auth_roles', $payload->roles ?? []);
        $request->attributes->set('auth_payload', $payload);

        return $next($request);
    }
}
