<?php

namespace App\Http\Middleware;

use App\Support\FinanceCacheContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppendIntegrationCacheHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        FinanceCacheContext::reset();

        $response = $next($request);

        if (FinanceCacheContext::isStale()) {
            $response->headers->set('X-Cache', 'STALE');
        }

        return $response;
    }
}
