<?php

namespace App\Services;

use App\Models\OperationalEvent;

class FinanceAuditLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function log(string $action, string $resourceType, int|string $resourceId, int $actorId, array $context = []): void
    {
        OperationalEvent::query()->create([
            'channel' => 'finance.audit',
            'source_system' => 's4',
            'request_id' => request()->header('X-Request-Id'),
            'payload' => array_merge([
                'actor_id' => $actorId,
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
            ], $context),
            'occurred_at' => now(),
        ]);
    }
}
