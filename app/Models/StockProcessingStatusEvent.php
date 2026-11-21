<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_item_id',
    'event_type',
    'previous_status',
    'new_status',
    'reason',
    'recorded_by',
    'recorded_at',
])]
class StockProcessingStatusEvent extends Model
{
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
