<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_item_id',
    'market_price',
    'effective_at',
    'reason',
    'recorded_by',
])]
class StockItemPriceHistory extends Model
{
    protected $table = 'stock_item_price_history';

    protected function casts(): array
    {
        return [
            'market_price' => 'decimal:2',
            'effective_at' => 'datetime',
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
