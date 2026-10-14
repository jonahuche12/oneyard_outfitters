<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementOffer extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'procurement_id',
        'user_id',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
        'status',
        'withdrawal_reason',
        'submitted_at',
        'delivered_quantity',
        'delivered_by',
        'delivered_at',
        'delivery_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'submitted_at' => 'datetime',
            'delivered_quantity' => 'decimal:2',
            'delivered_at' => 'datetime',
        ];
    }

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }
}
