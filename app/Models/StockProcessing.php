<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'raw_stock_item_id',
    'quantity_consumed',
    'consumed_cost',
    'allocated_cost',
    'cost_difference',
    'recoverable_waste_kg',
    'discarded_waste_kg',
    'processed_on',
    'notes',
    'processed_by',
])]
class StockProcessing extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_consumed' => 'decimal:3',
            'consumed_cost' => 'decimal:2',
            'allocated_cost' => 'decimal:2',
            'cost_difference' => 'decimal:2',
            'recoverable_waste_kg' => 'decimal:3',
            'discarded_waste_kg' => 'decimal:3',
            'processed_on' => 'date',
        ];
    }

    /**
     * Display number derived from the id, for example PRC-000045.
     */
    protected function number(): Attribute
    {
        return Attribute::get(fn (): string => sprintf('PRC-%06d', $this->id));
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function rawItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'raw_stock_item_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(StockProcessingOutput::class);
    }
}
