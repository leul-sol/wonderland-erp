<?php

namespace App\Console\Commands;

use App\Services\OutboxService;
use Illuminate\Console\Command;

class PublishOutboxEvents extends Command
{
    protected $signature = 'outbox:publish';

    protected $description = 'Publish pending S4 finance events to the Redis bus';

    public function handle(OutboxService $outbox): int
    {
        $count = $outbox->publishPending();
        $this->info("Published {$count} outbox event(s).");

        return self::SUCCESS;
    }
}
