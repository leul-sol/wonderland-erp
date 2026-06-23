<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'goods_receipt_line_id',
        'batch_code',
        'quantity_received',
        'quantity_remaining',
        'unit_cost',
        'received_date',
        'expiry_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:2',
            'quantity_remaining' => 'decimal:2',
            'unit_cost' => 'decimal:4',
            'received_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function goodsReceiptLine(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptLine::class);
    }
}
