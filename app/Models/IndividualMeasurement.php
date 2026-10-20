<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndividualMeasurement extends Model
{
    protected $fillable = [
        'measurement_submission_id',
        'person_label',
        'measurements',
        'notes',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'measurements' => 'array',
            'position' => 'integer',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(MeasurementSubmission::class, 'measurement_submission_id');
    }
}
