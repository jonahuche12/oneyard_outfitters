<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeasurementSubmission extends Model
{
    protected $fillable = [
        'order_item_id',
        'revision',
        'measurement_method',
        'input_source',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'correction_reason',
    ];

    protected function casts(): array
    {
        return [
            'revision' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function individualMeasurements(): HasMany
    {
        return $this->hasMany(IndividualMeasurement::class)
            ->orderBy('position');
    }

    public function sizeQuantities(): HasMany
    {
        return $this->hasMany(MeasurementSizeQuantity::class)
            ->orderBy('size_label');
    }
}
