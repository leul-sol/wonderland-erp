<?php

namespace Tests\Feature;

use App\Models\PortalNotification;
use App\Services\Notifications\NotificationInboxService;
use App\Support\NotificationFeedBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.user', ['id' => 7, 'username' => 'finance.manager', 'name' => 'Finance Manager']);
        Session::put('portal.permissions', [
            'S2.workforce.leave_requests.read',
            'S2.workforce.payroll_runs.read',
            'S3.inventory.purchase_orders.read',
            'S3.inventory.items.read',
        ]);
    }

    public function test_inbox_syncs_feed_items_for_user(): void
    {
        $this->mock(NotificationFeedBuilder::class, function (MockInterface $mock): void {
            $mock->shouldReceive('build')->once()->andReturn([
                [
                    'source_key' => 'leave:3',
                    'type' => 'approval',
                    'category' => 'leave',
                    'category_label' => 'Leave request',
                    'title' => 'Jane Doe',
                    'body' => 'Annual leave',
                    'href' => '/hr/leave',
                    'priority' => 'normal',
                ],
            ]);
        });

        app(NotificationInboxService::class)->sync(7);

        $this->assertDatabaseHas('portal_notifications', [
            'user_id' => 7,
            'source_key' => 'leave:3',
            'title' => 'Jane Doe',
            'read_at' => null,
        ]);
    }

    public function test_notifications_page_renders_inbox(): void
    {
        PortalNotification::query()->create([
            'user_id' => 7,
            'source_key' => 'payroll_run:2',
            'type' => 'approval',
            'category' => 'payroll',
            'title' => 'PR-00002',
            'body' => 'Pending approval',
            'href' => '/payroll/runs',
            'priority' => 'normal',
        ]);

        $this->mock(NotificationFeedBuilder::class, function (MockInterface $mock): void {
            $mock->shouldReceive('build')->andReturn([]);
        });

        $response = $this->get('/notifications');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->has('notifications', 1)
            ->where('notifications.0.title', 'PR-00002')
        );
    }

    public function test_mark_all_read_clears_unread_count(): void
    {
        PortalNotification::query()->create([
            'user_id' => 7,
            'source_key' => 'leave:1',
            'type' => 'approval',
            'category' => 'leave',
            'title' => 'Pending leave',
            'body' => 'Sick leave',
            'href' => '/hr/leave',
            'priority' => 'normal',
        ]);

        $this->withoutMiddleware();

        $response = $this->post('/notifications/read-all');

        $response->assertRedirect();
        $this->assertSame(0, PortalNotification::query()->forUser(7)->unread()->count());
    }

    public function test_refresh_notifications_query_forces_feed_sync(): void
    {
        $this->mock(NotificationFeedBuilder::class, function (MockInterface $mock): void {
            $mock->shouldReceive('build')->once()->andReturn([
                [
                    'source_key' => 'journal:draft:5',
                    'type' => 'approval',
                    'category' => 'journal',
                    'title' => 'JE-00005',
                    'body' => 'Awaiting finance approval',
                    'href' => '/finance/journals/5',
                    'priority' => 'normal',
                ],
            ]);
        });

        $this->get('/?refresh_notifications=1')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('notifications.unread_count', 1)
                ->where('notifications.items.0.title', 'JE-00005')
            );
    }
}
