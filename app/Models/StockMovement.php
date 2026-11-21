<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'stock_item_id',
    'store_id',
    'type',
    'quantity',
    'unit_cost',
    'movement_value',
    'source_type',
    'source_id',
    'reversal_of_id',
    'reason',
    'created_by',
])]
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_OPENING_STOCK = 'opening_stock';
    public const TYPE_INTAKE = 'intake';
    public const TYPE_PROCESSING_OUT = 'processing_out';
    public const TYPE_PROCESSING_IN = 'processing_in';
    public const TYPE_CONSIGNMENT_OUT = 'consignment_out';
    public const TYPE_CONSIGNMENT_IN = 'consignment_in';
    public const TYPE_CONSIGNMENT_RELEASE = 'consignment_release';
    public const TYPE_CONSIGNMENT_SHORTAGE = 'consignment_shortage';
    public const TYPE_CONSIGNMENT_WRITE_OFF = 'consignment_write_off';
    public const TYPE_CONSIGNMENT_RETURN_OUT = 'consignment_return_out';
    public const TYPE_CONSIGNMENT_RETURN_IN = 'consignment_return_in';
    public const TYPE_SALE_OUT = 'sale_out';

    public const TYPES = [
        self::TYPE_OPENING_STOCK,
        self::TYPE_INTAKE,
        self::TYPE_PROCESSING_OUT,
        self::TYPE_PROCESSING_IN,
        self::TYPE_CONSIGNMENT_OUT,
        self::TYPE_CONSIGNMENT_IN,
        self::TYPE_CONSIGNMENT_RELEASE,
        self::TYPE_CONSIGNMENT_SHORTAGE,
        self::TYPE_CONSIGNMENT_WRITE_OFF,
        self::TYPE_CONSIGNMENT_RETURN_OUT,
        self::TYPE_CONSIGNMENT_RETURN_IN,
        self::TYPE_SALE_OUT,
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \LogicException('Stock movements are append-only.');
        });

        static::deleting(function (): never {
            throw new \LogicException('Stock movements are append-only.');
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:6',
            'movement_value' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }
}
