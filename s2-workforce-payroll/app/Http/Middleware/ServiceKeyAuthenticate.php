<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceKeyAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Service-Key');
        $current = config('services.internal_key_current');
        $previous = config('services.internal_key_previous');

        if ($key === null || ($key !== $current && ($previous === null || $previous === '' || $key !== $previous))) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_SERVICE_KEY',
                    'message' => 'Invalid service key.',
                    'details' => new \stdClass,
                ],
            ], 401);
        }

        return $next($request);
    }
}
