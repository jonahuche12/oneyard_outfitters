<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'store_id',
    'store_daily_cash_record_id',
    'business_date',
    'amount_cash',
    'amount_bank',
    'method',
    'reference',
    'note',
    'status',
    'resubmits_id',
    'submitted_by',
    'submitted_at',
    'reviewed_by',
    'reviewed_at',
    'review_reason',
])]
class StoreRemittance extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_REJECTED,
    ];

    public const METHOD_CASH_HANDOVER = 'cash_handover';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_BANK_DEPOSIT = 'bank_deposit';

    public const METHODS = [
        self::METHOD_CASH_HANDOVER,
        self::METHOD_BANK_TRANSFER,
        self::METHOD_BANK_DEPOSIT,
    ];

    /** Only these attributes may change, and only once, when a pending row is reviewed. */
    private const REVIEW_FIELDS = [
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_reason',
        'updated_at',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $remittance) {
            $illegal = array_diff(array_keys($remittance->getDirty()), self::REVIEW_FIELDS);

            if ($illegal !== []) {
                throw new LogicException('Store remittances are append-only; only the review fields may change.');
            }

            if ($remittance->getOriginal('status') !== self::STATUS_PENDING) {
                throw new LogicException('A reviewed store remittance cannot be changed.');
            }

            if (! in_array($remittance->status, [self::STATUS_CONFIRMED, self::STATUS_REJECTED], true)) {
                throw new LogicException('A pending store remittance can only be confirmed or rejected.');
            }
        });

        static::deleting(function () {
            throw new LogicException('Store remittances cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'amount_cash' => 'decimal:2',
            'amount_bank' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function totalAmount(): string
    {
        return bcadd((string) $this->amount_cash, (string) $this->amount_bank, 2);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function dailyCashRecord(): BelongsTo
    {
        return $this->belongsTo(StoreDailyCashRecord::class, 'store_daily_cash_record_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function resubmits(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resubmits_id');
    }

    public function resubmissions(): HasMany
    {
        return $this->hasMany(self::class, 'resubmits_id');
    }
}
