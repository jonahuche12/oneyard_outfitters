<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference',
    'source_store_id',
    'destination_store_id',
    'status',
    'dispatched_at',
    'dispatched_by',
    'total_cost',
    'expected_sales_value',
    'expected_gross_return',
    'notes',
    'created_by',
])]
class StockConsignment extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_PARTIALLY_ACKNOWLEDGED = 'partially_acknowledged';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_RECONCILED = 'reconciled';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_DISPATCHED,
        self::STATUS_PARTIALLY_ACKNOWLEDGED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_RECONCILED,
        self::STATUS_CANCELLED,
    ];

    protected function casts(): array
    {
        return [
            'dispatched_at' => 'datetime',
            'total_cost' => 'decimal:2',
            'expected_sales_value' => 'decimal:2',
            'expected_gross_return' => 'decimal:2',
        ];
    }

    public function sourceStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'source_store_id');
    }

    public function destinationStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockConsignmentItem::class);
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(StockConsignmentAcknowledgement::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(StockConsignmentExpense::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(StockConsignmentReturn::class, 'stock_consignment_id');
    }
}
