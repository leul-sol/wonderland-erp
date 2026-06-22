<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotencyHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! is_string($key) || trim($key) === '') {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Idempotency-Key header is required.',
                    'details' => new \stdClass,
                ],
            ], 400);
        }

        $request->attributes->set('idempotency_key', trim($key));

        return $next($request);
    }
}
