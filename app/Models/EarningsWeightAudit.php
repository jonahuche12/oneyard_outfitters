<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EarningsWeightAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'activity_weight_id',
        'activity_key',
        'old_points',
        'new_points',
        'old_enabled',
        'new_enabled',
        'changed_by',
        'reason',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_points' => 'decimal:4',
            'new_points' => 'decimal:4',
            'old_enabled' => 'boolean',
            'new_enabled' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function activityWeight(): BelongsTo
    {
        return $this->belongsTo(
            EarningsActivityWeight::class,
            'activity_weight_id'
        );
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
