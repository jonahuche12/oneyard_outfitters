<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_consignment_item_id',
    'acknowledgement_item_id',
    'type',
    'quantity',
    'status',
    'reported_by',
    'reported_at',
    'resolution',
    'resolution_reason',
    'resolved_by',
    'resolved_at',
])]
class StockConsignmentDiscrepancy extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function consignmentItem(): BelongsTo
    {
        return $this->belongsTo(
            StockConsignmentItem::class,
            'stock_consignment_item_id'
        );
    }

    public function acknowledgementItem(): BelongsTo
    {
        return $this->belongsTo(
            StockConsignmentAcknowledgementItem::class,
            'acknowledgement_item_id'
        );
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
