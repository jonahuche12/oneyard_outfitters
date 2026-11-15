<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'code', 'type', 'address', 'phone', 'is_active', 'created_by'])]
class Store extends Model
{
    public const TYPE_WAREHOUSE = 'warehouse';
    public const TYPE_STORE = 'store';

    public const TYPES = [self::TYPE_WAREHOUSE, self::TYPE_STORE];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Phase 1 allows exactly one warehouse, so a warehouse always
        // occupies the single general-warehouse slot (unique in the database).
        static::saving(function (Store $store): void {
            $store->general_warehouse_slot = $store->type === self::TYPE_WAREHOUSE ? 1 : null;
        });
    }

    public function isWarehouse(): bool
    {
        return $this->type === self::TYPE_WAREHOUSE;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Every assignment this store has had (history included).
     */
    public function staffAssignments(): HasMany
    {
        return $this->hasMany(StoreStaff::class);
    }

    public function activeStaff(): HasMany
    {
        return $this->staffAssignments()->whereNull('ends_at');
    }

    /**
     * The current coordinator assignment, if any (at most one).
     */
    public function coordinatorAssignment(): HasOne
    {
        return $this->hasOne(StoreStaff::class)
            ->whereNull('ends_at')
            ->where('role', StoreStaff::ROLE_COORDINATOR);
    }

    public function assistantAssignments(): HasMany
    {
        return $this->activeStaff()->where('role', StoreStaff::ROLE_ASSISTANT);
    }
}
