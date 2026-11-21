<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'stock_sale_id',
    'stock_item_id',
    'quantity',
    'unit_selling_price',
    'line_total',
    'unit_cost_snapshot',
    'line_cost_snapshot',
])]
class StockSaleItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_selling_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'unit_cost_snapshot' => 'decimal:6',
            'line_cost_snapshot' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Recorded sale lines are immutable.'));
        static::deleting(fn () => throw new LogicException('Recorded sale lines cannot be deleted.'));
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(StockSale::class, 'stock_sale_id');
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
}
