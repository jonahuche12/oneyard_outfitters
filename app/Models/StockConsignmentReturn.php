<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'stock_consignment_id',
    'reference',
    'dispatched_by',
    'dispatched_at',
    'received_by',
    'received_at',
    'dispatch_notes',
    'receipt_notes',
])]
class StockConsignmentReturn extends Model
{
    protected function casts(): array
    {
        return [
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(StockConsignment::class, 'stock_consignment_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockConsignmentReturnItem::class, 'stock_consignment_return_id');
    }

    public function isReceived(): bool
    {
        return $this->received_at !== null;
    }
}
