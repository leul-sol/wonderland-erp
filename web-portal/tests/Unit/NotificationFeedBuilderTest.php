<?php

namespace Tests\Unit;

use App\Services\Api\S4FinanceClient;
use App\Support\NotificationFeedBuilder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class NotificationFeedBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_includes_journal_drafts_for_approvers(): void
    {
        Session::put('portal.permissions', ['S4.finance.journal_entries.approve']);

        $this->mock(S4FinanceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchMany')->once()->andReturn([
                's4_journals_draft' => [
                    'data' => [[
                        'id' => 9,
                        'entry_number' => 'JE-00009',
                        'description' => 'Office supplies',
                        'source_module' => 'manual',
                        'status' => 'draft',
                    ]],
                ],
                's4_journals_approved' => ['data' => []],
            ]);
        });

        $items = app(NotificationFeedBuilder::class)->build();

        $this->assertCount(1, $items);
        $this->assertSame('journal:draft:9', $items[0]['source_key']);
        $this->assertSame('approval', $items[0]['type']);
    }

    public function test_includes_gm_journal_approval_for_large_entries(): void
    {
        Session::put('portal.permissions', ['S4.finance.journal_entries.approve']);

        $this->mock(S4FinanceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchMany')->once()->andReturn([
                's4_journals_draft' => ['data' => []],
                's4_journals_approved' => [
                    'data' => [[
                        'id' => 12,
                        'entry_number' => 'JE-00012',
                        'source_module' => 'manual',
                        'status' => 'approved',
                        'total_debit' => '75000.00',
                        'second_approved_by' => null,
                    ]],
                ],
            ]);
        });

        $items = app(NotificationFeedBuilder::class)->build();

        $this->assertCount(1, $items);
        $this->assertSame('journal:gm:12', $items[0]['source_key']);
        $this->assertSame('high', $items[0]['priority']);
    }

    public function test_includes_fiscal_period_ending_soon_reminder(): void
    {
        Carbon::setTestNow('2026-06-27');

        Session::put('portal.permissions', ['S4.finance.fiscal_periods.close']);

        $this->mock(S4FinanceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchMany')->once()->andReturn([
                's4_fiscal_periods' => [
                    'data' => [[
                        'id' => 4,
                        'year' => 2026,
                        'period_number' => 6,
                        'start_date' => '2026-06-01',
                        'end_date' => '2026-06-30',
                        'status' => 'open',
                    ]],
                ],
            ]);
        });

        $items = app(NotificationFeedBuilder::class)->build();

        $this->assertCount(1, $items);
        $this->assertSame('fiscal_period:ending:4', $items[0]['source_key']);
        $this->assertSame('reminder', $items[0]['type']);
    }

    public function test_includes_must_change_password_system_message(): void
    {
        Session::put('portal.user', ['id' => 1, 'must_change_password' => true]);
        Session::put('portal.permissions', []);

        $this->mock(S4FinanceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchMany')->never();
        });

        $items = app(NotificationFeedBuilder::class)->build();

        $this->assertCount(1, $items);
        $this->assertSame('system:must_change_password', $items[0]['source_key']);
        $this->assertSame('system', $items[0]['type']);
    }
}
