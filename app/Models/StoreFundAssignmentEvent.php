<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'store_fund_assignment_id',
    'actor_id',
    'event_type',
    'details',
])]
class StoreFundAssignmentEvent extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // This event model is intended as an append-only audit trail.
        static::updating(function (): never {
            throw new \LogicException(
                'Store fund assignment events cannot be modified.'
            );
        });

        static::deleting(function (): never {
            throw new \LogicException(
                'Store fund assignment events cannot be deleted.'
            );
        });
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(
            StoreFundAssignment::class,
            'store_fund_assignment_id'
        );
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
