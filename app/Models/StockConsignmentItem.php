<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'stock_consignment_id',
    'stock_item_id',
    'quantity_dispatched',
    'unit_cost',
    'line_cost',
    'market_price',
    'expected_sales_value',
    'expected_gross_return',
])]
class StockConsignmentItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_dispatched' => 'decimal:3',
            'unit_cost' => 'decimal:6',
            'line_cost' => 'decimal:2',
            'market_price' => 'decimal:2',
            'expected_sales_value' => 'decimal:2',
            'expected_gross_return' => 'decimal:2',
        ];
    }

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(StockConsignment::class, 'stock_consignment_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function acknowledgementItems(): HasMany
    {
        return $this->hasMany(
            StockConsignmentAcknowledgementItem::class,
            'stock_consignment_item_id'
        );
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(
            StockConsignmentDiscrepancy::class,
            'stock_consignment_item_id'
        );
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(
            StockConsignmentReturnItem::class,
            'stock_consignment_item_id'
        );
    }
}
