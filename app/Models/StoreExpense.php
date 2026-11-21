<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'store_id',
    'store_daily_cash_record_id',
    'amount',
    'category',
    'payment_method',
    'description',
    'recorded_by',
])]
class StoreExpense extends Model
{
    public const CATEGORY_TRANSPORTATION = 'transportation';
    public const CATEGORY_LOADING_OFFLOADING = 'loading_offloading';
    public const CATEGORY_PACKAGING = 'packaging';
    public const CATEGORY_UTILITIES = 'utilities';
    public const CATEGORY_CLEANING_MAINTENANCE = 'cleaning_maintenance';
    public const CATEGORY_STORE_SUPPLIES = 'store_supplies';
    public const CATEGORY_STAFF_EXPENSES = 'staff_expenses';
    public const CATEGORY_GOODS_PURCHASE = 'goods_purchase';
    public const CATEGORY_DEBT_REPAYMENT = 'debt_repayment';
    public const CATEGORY_OTHER = 'other';

    public const CATEGORIES = [
        self::CATEGORY_TRANSPORTATION,
        self::CATEGORY_LOADING_OFFLOADING,
        self::CATEGORY_PACKAGING,
        self::CATEGORY_UTILITIES,
        self::CATEGORY_CLEANING_MAINTENANCE,
        self::CATEGORY_STORE_SUPPLIES,
        self::CATEGORY_STAFF_EXPENSES,
        self::CATEGORY_GOODS_PURCHASE,
        self::CATEGORY_DEBT_REPAYMENT,
        self::CATEGORY_OTHER,
    ];

    public const PAYMENT_CASH = 'cash';
    public const PAYMENT_POS = 'pos';
    public const PAYMENT_TRANSFER = 'transfer';

    public const PAYMENT_METHODS = [
        self::PAYMENT_CASH,
        self::PAYMENT_POS,
        self::PAYMENT_TRANSFER,
    ];

    public const THRESHOLD_EXCLUDED_CATEGORIES = [
        self::CATEGORY_GOODS_PURCHASE,
        self::CATEGORY_DEBT_REPAYMENT,
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function countsTowardDailyThreshold(): bool
    {
        return ! in_array(
            $this->category,
            self::THRESHOLD_EXCLUDED_CATEGORIES,
            true
        );
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function dailyCashRecord(): BelongsTo
    {
        return $this->belongsTo(StoreDailyCashRecord::class, 'store_daily_cash_record_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
