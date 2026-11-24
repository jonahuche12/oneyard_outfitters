<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'store_daily_cash_record_id',
    'business_date',
    'source',
    'purpose',
    'status',
    'requested_cash',
    'requested_bank',
    'sent_cash',
    'sent_bank',
    'requested_by',
    'handled_by',
    'approved_by',
    'approved_at',
    'dispatched_by',
    'dispatched_at',
    'confirmed_by',
    'confirmed_at',
    'rejection_reason',
    'reference',
    'note',
])]
class StoreFundAssignment extends Model
{
    public const SOURCE_STORE_REQUEST = 'store_request';
    public const SOURCE_DIRECT_ASSIGNMENT = 'direct_assignment';

    public const SOURCES = [
        self::SOURCE_STORE_REQUEST,
        self::SOURCE_DIRECT_ASSIGNMENT,
    ];

    public const PURPOSE_DAILY_TRANSACTIONS = 'daily_transactions';
    public const PURPOSE_PHYSICAL_PURCHASES = 'physical_purchases';
    public const PURPOSE_UTILITIES = 'utilities';

    public const PURPOSES = [
        self::PURPOSE_DAILY_TRANSACTIONS,
        self::PURPOSE_PHYSICAL_PURCHASES,
        self::PURPOSE_UTILITIES,
    ];

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SENT = 'sent';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_SENT,
        self::STATUS_CONFIRMED,
        self::STATUS_REJECTED,
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'requested_cash' => 'decimal:2',
            'requested_bank' => 'decimal:2',
            'sent_cash' => 'decimal:2',
            'sent_bank' => 'decimal:2',
            'approved_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function dailyCashRecord(): BelongsTo
    {
        return $this->belongsTo(
            StoreDailyCashRecord::class,
            'store_daily_cash_record_id'
        );
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            StoreFundAssignmentEvent::class,
            'store_fund_assignment_id'
        );
    }
}
