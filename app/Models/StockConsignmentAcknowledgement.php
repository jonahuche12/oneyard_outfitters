<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'stock_consignment_id',
    'acknowledged_by',
    'acknowledged_at',
    'notes',
])]
class StockConsignmentAcknowledgement extends Model
{
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(StockConsignment::class, 'stock_consignment_id');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            StockConsignmentAcknowledgementItem::class,
            'acknowledgement_id'
        );
    }
}
