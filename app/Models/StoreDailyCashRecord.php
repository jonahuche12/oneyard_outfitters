<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'business_date',
    'opening_cash',
    'opening_bank',
    'entered_opening_cash',
    'entered_opening_bank',
    'assigned_opening_cash',
    'assigned_opening_bank',
    'closing_cash',
    'closing_bank',
    'opened_by',
    'closed_by',
])]
class StoreDailyCashRecord extends Model
{
    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'opening_cash' => 'decimal:2',
            'opening_bank' => 'decimal:2',
            'entered_opening_cash' => 'decimal:2',
            'entered_opening_bank' => 'decimal:2',
            'assigned_opening_cash' => 'decimal:2',
            'assigned_opening_bank' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'closing_bank' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(StoreExpense::class);
    }

    /** Fund assignments associated with this daily cash record. */
    public function fundAssignments(): HasMany
    {
        return $this->hasMany(
            StoreFundAssignment::class,
            'store_daily_cash_record_id'
        );
    }

    public function remittances(): HasMany
    {
        return $this->hasMany(StoreRemittance::class, 'store_daily_cash_record_id');
    }
}
