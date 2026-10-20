<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemMeasurementEligibilityAudit extends Model
{
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \LogicException('Measurement eligibility audit entries are append-only.');
        });

        static::deleting(function (): never {
            throw new \LogicException('Measurement eligibility audit entries are append-only.');
        });
    }

    protected $fillable = [
        'order_item_id',
        'old_eligibility',
        'new_eligibility',
        'changed_by',
        'reason',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
