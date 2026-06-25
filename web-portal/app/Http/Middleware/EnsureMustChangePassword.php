<?php

namespace App\Http\Middleware;

use App\Services\Auth\PortalAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMustChangePassword
{
    public function __construct(
        private readonly PortalAuthService $auth,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->auth->mustChangePassword()) {
            return $next($request);
        }

        if ($request->routeIs(
            'account.change-password.create',
            'account.change-password.store',
            'logout',
        )) {
            return $next($request);
        }

        return redirect()->route('account.change-password.create');
    }
}
