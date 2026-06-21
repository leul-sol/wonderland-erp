<?php

namespace App\Services;

use App\Models\EventOutbox;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public static function notifyOutboxFailure(EventOutbox $row, string $reason): void
    {
        Log::error('Outbox publish failed permanently.', [
            'outbox_id' => $row->id,
            'event' => $row->event,
            'attempts' => $row->attempts,
            'reason' => $reason,
        ]);

        $superAdminId = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->value('id');

        AuditService::log(
            'outbox.publish_failed',
            $superAdminId,
            '0.0.0.0',
            null,
            [
                'outbox_id' => $row->id,
                'event' => $row->event,
                'reason' => $reason,
            ],
        );
    }

    public static function notifySubscriberFailure(string $channel, string $reason, array $payload = []): void
    {
        Log::error('Event subscriber failure.', [
            'channel' => $channel,
            'reason' => $reason,
            'payload' => $payload,
        ]);

        $superAdminId = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->value('id');

        AuditService::log(
            'subscriber.failed',
            $superAdminId,
            '0.0.0.0',
            null,
            [
                'channel' => $channel,
                'reason' => $reason,
                'payload' => $payload,
            ],
        );
    }
}
