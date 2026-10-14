<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderEarningsPool extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_HELD = 'held';

    protected $fillable = [
        'order_id',
        'quotation_id',
        'quotation_number',
        'source_amount',
        'rate_percent',
        'pool_amount',
        'source_snapshot',
        'delivered_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'source_amount' => 'decimal:2',
            'rate_percent' => 'decimal:2',
            'pool_amount' => 'decimal:2',
            'source_snapshot' => 'array',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(OrderEarningsContribution::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
