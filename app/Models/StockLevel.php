<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_item_id',
    'store_id',
    'quantity',
    'quantity_quarantined',
    'average_unit_cost',
    'inventory_value',
    'quarantine_value',
    'store_price',
])]
class StockLevel extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'quantity_quarantined' => 'decimal:3',
            'average_unit_cost' => 'decimal:6',
            'inventory_value' => 'decimal:2',
            'quarantine_value' => 'decimal:2',
            'store_price' => 'decimal:2',
        ];
    }

    /**
     * Store-specific guide price, falling back to the item's suggested price.
     */
    public function effectivePrice(): ?string
    {
        return $this->store_price ?? $this->item->suggested_price;
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
