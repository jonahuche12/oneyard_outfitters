<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_consignment_id',
    'category',
    'description',
    'amount',
    'incurred_at',
    'recorded_by',
])]
class StockConsignmentExpense extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'incurred_at' => 'datetime',
        ];
    }

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(StockConsignment::class, 'stock_consignment_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
