<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'store_id',
    'sale_number',
    'customer_id',
    'customer_name',
    'customer_phone',
    'customer_email',
    'total_amount',
    'sold_at',
    'created_by',
])]
class StockSale extends Model
{
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Recorded sales are immutable.'));
        static::deleting(fn () => throw new LogicException('Recorded sales cannot be deleted.'));
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockSaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StockSalePayment::class);
    }
}
