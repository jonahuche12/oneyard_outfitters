<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Immutable record of one staff member's finalized share of a pool.
 * Corrections must be made through audited adjustments, never by editing.
 */
class OrderEarningsAllocation extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_earnings_pool_id',
        'user_id',
        'amount',
        'weight',
        'weight_scale',
        'effective_points',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'weight' => 'integer',
            'weight_scale' => 'integer',
            'effective_points' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException(
                'Finalized earnings allocations cannot be modified.'
            );
        });

        static::deleting(function (): void {
            throw new LogicException(
                'Finalized earnings allocations cannot be deleted.'
            );
        });
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(
            OrderEarningsPool::class,
            'order_earnings_pool_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
