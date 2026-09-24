<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'assessed_by',
        'assessment_date',
        'status',
        'needs',
        'current_supplier',
        'procurement_process',
        'estimated_budget',
        'estimated_demand',
        'buying_timeline',
        'decision_maker',
        'observations',
        'opportunities',
        'risks',
        'recommendation',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assessment_date' => 'date',
            'estimated_budget' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}