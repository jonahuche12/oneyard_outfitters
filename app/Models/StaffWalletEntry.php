<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only wallet ledger entry. Balances are always derived from entries.
 * Corrections are new adjustment/reversal entries, never edits.
 */
class StaffWalletEntry extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_EARNING = 'earning';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_REVERSAL = 'reversal';
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_WITHDRAWAL = 'withdrawal';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'order_earnings_pool_id',
        'order_earnings_allocation_id',
        'reverses_entry_id',
        'procurement_offer_id',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Wallet entries cannot be modified.');
        });

        static::deleting(function (): void {
            throw new LogicException('Wallet entries cannot be deleted.');
        });
    }

    public static function balanceFor(User|int $user): string
    {
        $id = $user instanceof User ? $user->id : $user;

        return number_format(
            (float) static::query()->where('user_id', $id)->sum('amount'),
            2,
            '.',
            ''
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(OrderEarningsPool::class, 'order_earnings_pool_id');
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(OrderEarningsAllocation::class, 'order_earnings_allocation_id');
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
