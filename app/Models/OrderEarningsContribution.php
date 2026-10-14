<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEarningsContribution extends Model
{
    public const SOURCE_WORKFLOW = 'workflow';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_OVERSIGHT = 'oversight';

    protected $fillable = [
        'order_earnings_pool_id',
        'user_id',
        'activity_key',
        'points',
        'source_type',
        'source_reference',
        'activity_at',
        'user_active_at_activity',
        'assigned_by',
        'reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:4',
            'activity_at' => 'datetime',
            'user_active_at_activity' => 'boolean',
            'metadata' => 'array',
        ];
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

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
