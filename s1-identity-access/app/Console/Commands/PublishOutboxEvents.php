<?php

namespace App\Console\Commands;

use App\Services\OutboxService;
use Illuminate\Console\Command;

class PublishOutboxEvents extends Command
{
    protected $signature = 'outbox:publish';

    protected $description = 'Publish pending event_outbox rows to the Redis bus';

    public function handle(OutboxService $outbox): int
    {
        $count = $outbox->publishPending();
        $this->comment("Published {$count} outbox event(s).");

        return self::SUCCESS;
    }
}
