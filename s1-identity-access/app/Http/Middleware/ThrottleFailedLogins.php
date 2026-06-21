<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleFailedLogins
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMITED',
                    'message' => 'Too many login attempts. Try again later.',
                    'details' => ['retry_after_seconds' => $seconds],
                ],
            ], 429);
        }

        /** @var Response $response */
        $response = $next($request);

        if ($response->getStatusCode() === 401) {
            RateLimiter::hit($key, 60);
        }

        if ($response->getStatusCode() === 200) {
            RateLimiter::clear($key);
        }

        return $response;
    }
}
