<?php

namespace Tests\Feature;

use App\Models\EventOutbox;
use App\Services\OutboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutboxBackoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_should_attempt_publish_respects_backoff_schedule(): void
    {
        $service = app(OutboxService::class);
        $backoff = config('events.outbox.retry_backoff_seconds');

        $row = EventOutbox::query()->create([
            'event' => 'test.event',
            'payload' => ['sample' => true],
            'status' => 'pending',
            'attempts' => 1,
            'last_attempt_at' => now(),
            'created_at' => now(),
        ]);

        $this->assertFalse($service->shouldAttemptPublish($row, $backoff));

        $row->update(['last_attempt_at' => now()->subSeconds($backoff[0] + 1)]);

        $this->assertTrue($service->shouldAttemptPublish($row->fresh(), $backoff));
    }

    public function test_outbox_marks_failed_and_audits_after_exhausted_retries(): void
    {
        config(['events.redis_bus.port' => 1]);

        $backoff = config('events.outbox.retry_backoff_seconds');

        $row = EventOutbox::query()->create([
            'event' => config('events.channels.permission_changed'),
            'payload' => ['role_id' => 1, 'permission_ids' => [1], 'action' => 'sync'],
            'status' => 'pending',
            'attempts' => count($backoff),
            'last_attempt_at' => now()->subDay(),
            'created_at' => now()->subDay(),
        ]);

        app(OutboxService::class)->publishPending();

        $row->refresh();

        $this->assertSame('failed', $row->status);
        $this->assertSame(count($backoff) + 1, $row->attempts);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'outbox.publish_failed',
        ]);
    }

    public function test_dr_restore_drill_writes_audit_entry(): void
    {
        $this->artisan('audit:dr-restore-drill', [
            'archive' => 'wonderland-mysql-test.tar.gz',
            'tables' => 12,
        ])->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'dr.restore_drill',
        ]);
    }
}
