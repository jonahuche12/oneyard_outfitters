<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityControlInspection extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public const RESULT_PASS = 'pass';
    public const RESULT_FAIL = 'fail';

    protected $fillable = [
        'order_id',
        'inspected_by',
        'approved_by',
        'status',
        'result',
        'findings',
        'correction_required',
        'correction_notes',
        'inspected_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'correction_required' => 'boolean',
            'inspected_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QualityControlInspectionItem::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPassed(): bool
    {
        return $this->result === self::RESULT_PASS;
    }

    public function isFailed(): bool
    {
        return $this->result === self::RESULT_FAIL;
    }
}
