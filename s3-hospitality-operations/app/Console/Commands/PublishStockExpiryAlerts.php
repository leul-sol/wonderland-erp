<?php

namespace App\Console\Commands;

use App\Services\OutboxService;
use App\Services\StockService;
use Illuminate\Console\Command;

class PublishStockExpiryAlerts extends Command
{
    protected $signature = 'stock:expiry-alerts';

    protected $description = 'Publish stock expiry alert events for batches nearing expiry';

    public function handle(StockService $stock, OutboxService $outbox): int
    {
        $batches = $stock->expiryAlerts();
        $count = 0;

        foreach ($batches as $batch) {
            $outbox->enqueue(config('events.channels.stock_expiry_alert'), [
                'batch_id' => $batch->id,
                'inventory_item_id' => $batch->inventory_item_id,
                'sku' => $batch->inventoryItem?->sku,
                'batch_code' => $batch->batch_code,
                'expiry_date' => $batch->expiry_date?->toDateString(),
                'quantity_remaining' => (string) $batch->quantity_remaining,
            ]);
            $count++;
        }

        $this->comment("Queued {$count} expiry alert(s).");

        return self::SUCCESS;
    }
}
