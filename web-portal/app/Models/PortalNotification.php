<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PortalNotification extends Model
{
    protected $fillable = [
        'user_id',
        'source_key',
        'type',
        'category',
        'title',
        'body',
        'href',
        'priority',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toBellItem(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'category' => $this->category,
            'category_label' => $this->categoryLabel(),
            'title' => $this->title,
            'body' => $this->body ?? '',
            'href' => $this->href,
            'priority' => $this->priority,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'leave' => 'Leave request',
            'purchase_order' => 'Purchase order',
            'payroll' => 'Payroll run',
            'stock' => 'Inventory alert',
            'journal' => 'Journal entry',
            'fiscal_period' => 'Fiscal period',
            'account' => 'Account security',
            'system' => 'System message',
            default => ucwords(str_replace('_', ' ', $this->category)),
        };
    }
}
