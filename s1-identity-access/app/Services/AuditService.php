<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    public static function log(
        string $event,
        ?int $userId,
        string $ipAddress,
        ?string $userAgent = null,
        ?array $payload = null,
    ): void {
        AuditLog::query()->create([
            'user_id' => $userId,
            'event' => $event,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }

    public static function logFromRequest(
        Request $request,
        string $event,
        ?int $userId,
        ?array $payload = null,
    ): void {
        $merged = $payload ?? [];

        if ($request->header('X-Request-Id')) {
            $merged['request_id'] = $request->header('X-Request-Id');
        }

        self::log(
            $event,
            $userId,
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
            $merged === [] ? null : $merged,
        );
    }
}
