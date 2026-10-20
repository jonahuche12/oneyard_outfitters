<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeasurementSizeQuantity extends Model
{
    protected $fillable = [
        'measurement_submission_id',
        'size_label',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(MeasurementSubmission::class, 'measurement_submission_id');
    }
}
