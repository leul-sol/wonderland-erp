<?php

namespace App\Console\Commands;

use App\Models\IdempotencyKey;
use Illuminate\Console\Command;

class PurgeIdempotencyKeys extends Command
{
    protected $signature = 'idempotency:purge';

    protected $description = 'Delete expired idempotency key records (D5)';

    public function handle(): int
    {
        $deleted = IdempotencyKey::query()
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Purged {$deleted} expired idempotency key(s).");

        return self::SUCCESS;
    }
}
