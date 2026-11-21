<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'intake_id',
    'description',
    'category',
    'grade',
    'unit',
    'is_raw',
    'unit_cost',
    'suggested_price',
    'current_market_price',
    'approval_status',
    'approved_by',
    'approved_at',
    'rejection_reason',
    'processing_status',
    'processing_completed_at',
    'created_by',
])]
class StockItem extends Model
{
    public const UNIT_YARD = 'yard';
    public const UNIT_KG = 'kg';
    public const UNIT_BALE = 'bale';

    public const UNITS = [self::UNIT_YARD, self::UNIT_KG, self::UNIT_BALE];

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    public const APPROVAL_STATUSES = [
        self::APPROVAL_PENDING,
        self::APPROVAL_APPROVED,
        self::APPROVAL_REJECTED,
    ];

    public const PROCESSING_PENDING = 'pending';
    public const PROCESSING_PROCESSING = 'processing';
    public const PROCESSING_PROCESSED = 'processed';

    public const PROCESSING_STATUSES = [
        self::PROCESSING_PENDING,
        self::PROCESSING_PROCESSING,
        self::PROCESSING_PROCESSED,
    ];

    protected static function booted(): void
    {
        static::creating(function (StockItem $item): void {
            $item->name = static::generateUniqueName(
                $item->category,
                $item->description
            );

            $item->code = static::generateUniqueCode();
        });

        static::updating(function (StockItem $item): void {
            if ($item->isDirty(['category', 'description'])) {
                $item->name = static::generateUniqueName(
                    $item->category,
                    $item->description,
                    $item->getKey()
                );
            }

            // A stock code is assigned once and cannot be edited.
            if ($item->isDirty('code')) {
                $item->code = $item->getOriginal('code');
            }
        });
    }

    private static function generateUniqueName(
        ?string $category,
        ?string $description,
        int|string|null $ignoreId = null
    ): string {
        $categoryPart = strtoupper((string) $category);
        $categoryPart = preg_replace('/[^A-Z0-9]/', '', $categoryPart) ?: 'GENERAL';

        $descriptionPart = strtoupper((string) $description);
        $descriptionPart = preg_replace('/[^A-Z0-9]/', '', $descriptionPart) ?: 'ITEM';
        $descriptionPart = substr($descriptionPart, 0, 9);

        $baseName = "ONYD-STK-{$categoryPart}-{$descriptionPart}";
        $name = $baseName;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->where('name', $name)
            ->exists()) {
            $name = $baseName.'-'.$suffix;
            $suffix++;
        }

        return $name;
    }

    private static function generateUniqueCode(): string
    {
        do {
            $code = 'STK-'.random_int(100000, 999999);
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    protected function casts(): array
    {
        return [
            'is_raw' => 'boolean',
            'unit_cost' => 'decimal:6',
            'suggested_price' => 'decimal:2',
            'current_market_price' => 'decimal:2',
            'approved_at' => 'datetime',
            'processing_completed_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(Intake::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(StockItemPhoto::class);
    }

    public function intakeItems(): HasMany
    {
        return $this->hasMany(IntakeItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(StockItemApproval::class);
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(StockItemPriceHistory::class);
    }

    public function processingStatusEvents(): HasMany
    {
        return $this->hasMany(StockProcessingStatusEvent::class);
    }

    /**
     * An item may be produced by multiple processing sessions.
     */
    public function processingOutputs(): HasMany
    {
        return $this->hasMany(StockProcessingOutput::class);
    }

    /**
     * Processings that consumed this item as raw material.
     */
    public function processingsAsRaw(): HasMany
    {
        return $this->hasMany(StockProcessing::class, 'raw_stock_item_id');
    }

    public function consignmentItems(): HasMany
    {
        return $this->hasMany(StockConsignmentItem::class);
    }

}
