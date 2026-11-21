<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'type',
    'supplier_name',
    'reference',
    'received_on',
    'bale_count',
    'weight',
    'weight_unit',
    'weight_kg',
    'rate',
    'total_cost',
    'notes',
    'received_by',
])]
class Intake extends Model
{
    public const TYPE_PICKING = 'picking';
    public const TYPE_BALE = 'bale';
    public const TYPE_LOT = 'lot';

    public const TYPES = [self::TYPE_PICKING, self::TYPE_BALE, self::TYPE_LOT];

    public const WEIGHT_UNIT_KG = 'kg';
    public const WEIGHT_UNIT_LB = 'lb';

    public const WEIGHT_UNITS = [self::WEIGHT_UNIT_KG, self::WEIGHT_UNIT_LB];

    protected function casts(): array
    {
        return [
            'received_on' => 'date',
            'bale_count' => 'integer',
            'weight' => 'decimal:3',
            'weight_kg' => 'decimal:3',
            'rate' => 'decimal:4',
            'total_cost' => 'decimal:2',
        ];
    }

    /**
     * Display number derived from the id, for example INT-000123.
     */
    protected function number(): Attribute
    {
        return Attribute::get(fn (): string => sprintf('INT-%06d', $this->id));
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(IntakeItem::class);
    }
}
