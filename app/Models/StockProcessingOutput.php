<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_processing_id',
    'stock_item_id',
    'quantity_produced',
    'line_cost',
])]
class StockProcessingOutput extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_produced' => 'decimal:3',
            'line_cost' => 'decimal:2',
        ];
    }

    public function processing(): BelongsTo
    {
        return $this->belongsTo(StockProcessing::class, 'stock_processing_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }
}
