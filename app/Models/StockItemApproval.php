<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_item_id',
    'decision',
    'reason',
    'reviewed_by',
    'reviewed_at',
])]
class StockItemApproval extends Model
{
    public const DECISION_APPROVED = 'approved';
    public const DECISION_REJECTED = 'rejected';

    public const DECISIONS = [
        self::DECISION_APPROVED,
        self::DECISION_REJECTED,
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
