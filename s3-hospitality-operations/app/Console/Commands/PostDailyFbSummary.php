<?php

namespace App\Console\Commands;

use App\Services\DailyFbSummaryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PostDailyFbSummary extends Command
{
    protected $signature = 'fb:daily-summary {--date= : Business date (Y-m-d), defaults to today}';

    protected $description = 'Post end-of-day F&B revenue summary to S4 (excludes hotel_guest folio orders)';

    public function handle(DailyFbSummaryService $service): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->startOfDay();

        $summary = $service->run($date);

        if ($summary === null) {
            $this->info('No deferred F&B orders to summarize for '.$date->toDateString().'.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Posted daily F&B summary for %s: %d orders, total %s (journal %s).',
            $summary->business_date->toDateString(),
            $summary->order_count,
            $summary->total_amount,
            $summary->s4_journal_entry_id,
        ));

        return self::SUCCESS;
    }
}
