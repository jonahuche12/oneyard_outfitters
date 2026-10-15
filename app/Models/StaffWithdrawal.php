<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffWithdrawal extends Model
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public const OPEN_STATUSES = [self::STATUS_REQUESTED, self::STATUS_APPROVED];

    protected $fillable = [
        'user_id', 'amount', 'status', 'payout_account_id', 'bank_name',
        'account_name', 'account_last_four', 'requested_at', 'reviewed_by',
        'reviewed_at', 'reject_reason', 'paid_by', 'paid_at',
        'payment_reference', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public static function cents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function openCents(int $userId): int
    {
        return self::cents(
            static::query()->where('user_id', $userId)->whereIn('status', self::OPEN_STATUSES)->sum('amount')
        );
    }

    /** Ledger balance minus open (requested or approved) withdrawals, in cents. */
    public static function availableCents(int $userId): int
    {
        $ledger = self::cents(StaffWalletEntry::query()->where('user_id', $userId)->sum('amount'));

        return $ledger - self::openCents($userId);
    }

    public static function available(int $userId): string
    {
        return number_format(self::availableCents($userId) / 100, 2, '.', '');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
