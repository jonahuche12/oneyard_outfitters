<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'store_id',
    'user_id',
    'role',
    'starts_at',
    'ends_at',
    'ended_reason',
    'assigned_by',
    'ended_by',
])]
class StoreStaff extends Model
{
    public const ROLE_COORDINATOR = 'coordinator';
    public const ROLE_ASSISTANT = 'assistant';

    public const ROLES = [self::ROLE_COORDINATOR, self::ROLE_ASSISTANT];

    protected $table = 'store_staff';

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // The slot columns exist only to let the database enforce
        // "one active store per user" and "one active coordinator per store".
        // They are always derived here, never set by callers.
        static::saving(function (StoreStaff $assignment): void {
            $active = $assignment->ends_at === null;

            $assignment->active_user_slot = $active ? 1 : null;
            $assignment->coordinator_slot = ($active && $assignment->role === self::ROLE_COORDINATOR) ? 1 : null;
        });
    }

    public function isActive(): bool
    {
        return $this->ends_at === null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ends_at');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }
}
