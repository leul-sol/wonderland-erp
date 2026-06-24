<?php

namespace App\Http\Middleware;

use App\Services\Auth\PortalAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalAuthenticated
{
    public function __construct(
        private readonly PortalAuthService $auth,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->auth->isAuthenticated()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
