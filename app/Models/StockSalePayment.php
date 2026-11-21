<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'stock_sale_id',
    'amount',
    'tip_amount',
    'payment_method',
    'reference',
    'paid_at',
    'notes',
    'received_by',
])]
class StockSalePayment extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tip_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Payment entries are immutable.'));
        static::deleting(fn () => throw new LogicException('Payment entries cannot be deleted.'));
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(StockSale::class, 'stock_sale_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
