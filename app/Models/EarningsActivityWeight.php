<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EarningsActivityWeight extends Model
{
    protected $fillable = [
        'activity_key',
        'label',
        'points',
        'is_enabled',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:4',
            'is_enabled' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(
            EarningsWeightAudit::class,
            'activity_weight_id'
        );
    }
}
