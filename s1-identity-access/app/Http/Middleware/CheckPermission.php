<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $permissions = $request->attributes->get('auth_permissions', []);

        if (! in_array($permission, $permissions, true)) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Insufficient permissions.',
                    'details' => ['required' => $permission],
                ],
            ], 403);
        }

        return $next($request);
    }
}
