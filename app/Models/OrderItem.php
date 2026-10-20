<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'quotation_item_id',
        'product_specification_id',
        'product_type',
        'item_name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'line_total',
        'sort_order',
        'measurement_eligibility',
        'measurement_eligibility_reason',
        'measurement_eligibility_decided_by',
        'measurement_eligibility_decided_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'measurement_eligibility_decided_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class);
    }

    public function productSpecification(): BelongsTo
    {
        return $this->belongsTo(ProductSpecification::class);
    }

    public function measurementEligibilityAudits(): HasMany
    {
        return $this->hasMany(OrderItemMeasurementEligibilityAudit::class)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function measurementSubmissions(): HasMany
    {
        return $this->hasMany(MeasurementSubmission::class)
            ->orderByDesc('revision');
    }
}
