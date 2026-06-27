<?php

namespace App\Services\Notifications;

use App\Models\PortalNotification;
use App\Services\Auth\PortalAuthService;
use App\Support\NotificationFeedBuilder;
use Illuminate\Support\Facades\Session;

class NotificationInboxService
{
    private const SYNC_SESSION_KEY = 'portal.notifications_synced_at';

    private const SYNC_INTERVAL_SECONDS = 120;

    public function __construct(
        private readonly PortalAuthService $auth,
        private readonly NotificationFeedBuilder $feed,
    ) {
    }

    /**
     * @return array{unread_count: int, items: list<array<string, mixed>>}
     */
    public function bellSummary(bool $forceSync = false): array
    {
        $userId = $this->userId();

        if ($userId === null) {
            return ['unread_count' => 0, 'items' => []];
        }

        if ($forceSync || $this->shouldSync()) {
            $this->sync($userId);
        }

        $items = PortalNotification::query()
            ->forUser($userId)
            ->unread()
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (PortalNotification $row) => $row->toBellItem())
            ->values()
            ->all();

        return [
            'unread_count' => PortalNotification::query()->forUser($userId)->unread()->count(),
            'items' => $items,
        ];
    }

    /**
     * @return list<PortalNotification>
     */
    public function inboxForUser(int $userId, ?string $filter = null, int $limit = 50): array
    {
        $this->sync($userId);

        $query = PortalNotification::query()
            ->forUser($userId)
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($filter === 'unread') {
            $query->unread();
        }

        return $query->get()->all();
    }

    public function sync(?int $userId = null): void
    {
        $userId ??= $this->userId();

        if ($userId === null) {
            return;
        }

        $feedItems = $this->feed->build();
        $activeKeys = [];

        foreach ($feedItems as $item) {
            $sourceKey = (string) ($item['source_key'] ?? '');

            if ($sourceKey === '') {
                continue;
            }

            $activeKeys[] = $sourceKey;

            PortalNotification::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'source_key' => $sourceKey,
                ],
                [
                    'type' => (string) ($item['type'] ?? 'system'),
                    'category' => (string) ($item['category'] ?? 'general'),
                    'title' => (string) ($item['title'] ?? 'Notification'),
                    'body' => (string) ($item['body'] ?? ''),
                    'href' => (string) ($item['href'] ?? '/'),
                    'priority' => (string) ($item['priority'] ?? 'normal'),
                ],
            );
        }

        if ($activeKeys === []) {
            PortalNotification::query()
                ->forUser($userId)
                ->unread()
                ->whereIn('type', ['approval', 'alert', 'reminder', 'system'])
                ->delete();
        } else {
            PortalNotification::query()
                ->forUser($userId)
                ->unread()
                ->whereIn('type', ['approval', 'alert', 'reminder', 'system'])
                ->whereNotIn('source_key', $activeKeys)
                ->delete();
        }

        Session::put(self::SYNC_SESSION_KEY, now()->timestamp);
    }

    public function markRead(int $userId, int $notificationId): bool
    {
        $notification = PortalNotification::query()
            ->forUser($userId)
            ->whereKey($notificationId)
            ->first();

        if ($notification === null) {
            return false;
        }

        $notification->markRead();

        return true;
    }

    public function markAllRead(int $userId): int
    {
        return PortalNotification::query()
            ->forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    private function shouldSync(): bool
    {
        $syncedAt = Session::get(self::SYNC_SESSION_KEY);

        if (! is_int($syncedAt) && ! is_numeric($syncedAt)) {
            return true;
        }

        return (time() - (int) $syncedAt) >= self::SYNC_INTERVAL_SECONDS;
    }

    private function userId(): ?int
    {
        $user = $this->auth->user();
        $id = is_array($user) ? ($user['id'] ?? null) : null;

        return is_numeric($id) ? (int) $id : null;
    }
}
