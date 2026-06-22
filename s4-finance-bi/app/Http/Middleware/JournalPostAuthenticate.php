<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Services\S1AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JournalPostAuthenticate
{
    use RespondsWithApiErrors;

    public function __construct(private readonly S1AuthService $s1Auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $serviceKey = $request->header('X-Service-Key');
        $current = config('services.internal_key_current');
        $previous = config('services.internal_key_previous');

        if ($serviceKey !== null && $serviceKey !== '' && ($serviceKey === $current || ($previous && $serviceKey === $previous))) {
            $request->attributes->set('auth_via_service_key', true);

            return $next($request);
        }

        $header = $request->header('Authorization', '');
        if (! preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
            return $this->error('UNAUTHENTICATED', 'Service key or Bearer token required.', 401);
        }

        $verified = $this->s1Auth->verify($matches[1]);
        if ($verified === null) {
            return $this->error('UNAUTHENTICATED', 'Invalid token.', 401);
        }

        $permissions = $verified['user']['permissions'] ?? [];
        if (! in_array('S4.finance.journal_entries.create', $permissions, true)) {
            return $this->error('FORBIDDEN', 'Insufficient permissions.', 403, [
                'required' => 'S4.finance.journal_entries.create',
            ]);
        }

        $request->attributes->set('auth_via_service_key', false);
        $request->attributes->set('auth_user_id', (int) ($verified['user']['sub'] ?? 0));
        $request->attributes->set('auth_permissions', $permissions);

        return $next($request);
    }
}
