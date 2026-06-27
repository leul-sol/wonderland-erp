<?php

namespace App\Http\Controllers;

use App\Models\PortalNotification;
use App\Services\Notifications\NotificationInboxService;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationInboxService $inbox,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $userId = $this->requireUserId();
        $filter = $request->string('filter')->toString() ?: 'all';

        if (! in_array($filter, ['all', 'unread'], true)) {
            $filter = 'all';
        }

        $rows = $this->inbox->inboxForUser($userId, $filter === 'unread' ? 'unread' : null);

        return Inertia::render('Notifications/Index', [
            'filter' => $filter,
            'notifications' => array_map(
                fn (PortalNotification $row) => $row->toBellItem(),
                $rows,
            ),
            'unread_count' => PortalNotification::query()->forUser($userId)->unread()->count(),
        ]);
    }

    public function markRead(int $notification): RedirectResponse
    {
        $userId = $this->requireUserId();

        abort_unless($this->inbox->markRead($userId, $notification), 404);

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        $userId = $this->requireUserId();
        $this->inbox->markAllRead($userId);

        return back()->with('success', 'All notifications marked as read.');
    }

    private function requireUserId(): int
    {
        $user = $this->auth->user();
        $id = is_array($user) ? ($user['id'] ?? null) : null;

        abort_unless(is_numeric($id), 403);

        return (int) $id;
    }
}
