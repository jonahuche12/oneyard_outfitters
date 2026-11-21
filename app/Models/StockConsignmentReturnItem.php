<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_consignment_return_id',
    'stock_consignment_item_id',
    'quantity_dispatched',
    'quantity_received',
])]
class StockConsignmentReturnItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_dispatched' => 'decimal:3',
            'quantity_received' => 'decimal:3',
        ];
    }

    public function stockReturn(): BelongsTo
    {
        return $this->belongsTo(
            StockConsignmentReturn::class,
            'stock_consignment_return_id'
        );
    }

    public function consignmentItem(): BelongsTo
    {
        return $this->belongsTo(
            StockConsignmentItem::class,
            'stock_consignment_item_id'
        );
    }
}
