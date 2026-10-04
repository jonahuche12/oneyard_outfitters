<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityControlInspectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_control_inspection_id',
        'order_item_id',
        'item_name',
        'unit',
        'quantity',
        'failed_quantity',
        'findings',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'failed_quantity' => 'decimal:2',
        ];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(
            QualityControlInspection::class,
            'quality_control_inspection_id'
        );
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function passedQuantity(): float
    {
        if ($this->failed_quantity === null) {
            return (float) $this->quantity;
        }

        return max(
            0,
            (float) $this->quantity - (float) $this->failed_quantity
        );
    }

    public function isPiece(): bool
    {
        return strtolower((string) $this->unit) === 'piece';
    }
}
