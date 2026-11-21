<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'acknowledgement_id',
    'stock_consignment_item_id',
    'quantity_good',
    'quantity_damaged',
    'quantity_shortage',
])]
class StockConsignmentAcknowledgementItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_good' => 'decimal:3',
            'quantity_damaged' => 'decimal:3',
            'quantity_shortage' => 'decimal:3',
        ];
    }

    public function acknowledgement(): BelongsTo
    {
        return $this->belongsTo(
            StockConsignmentAcknowledgement::class,
            'acknowledgement_id'
        );
    }

    public function consignmentItem(): BelongsTo
    {
        return $this->belongsTo(
            StockConsignmentItem::class,
            'stock_consignment_item_id'
        );
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(
            StockConsignmentDiscrepancy::class,
            'acknowledgement_item_id'
        );
    }
}
