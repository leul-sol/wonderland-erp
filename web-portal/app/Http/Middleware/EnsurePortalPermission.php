<?php

namespace App\Http\Middleware;

use App\Services\Auth\PortalAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalPermission
{
    public function __construct(
        private readonly PortalAuthService $auth,
    ) {
    }

    /**
     * @param  string  ...$permissions  Any one permission grants access.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        abort_unless($this->auth->hasAnyPermission($permissions), 403);

        return $next($request);
    }
}
