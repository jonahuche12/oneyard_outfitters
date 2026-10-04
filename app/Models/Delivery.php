<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';

    public const PAYMENT_ARRANGEMENT_PAY_NOW = 'pay_now';

    public const PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY = 'pay_on_delivery';

    public const PAYMENT_ARRANGEMENT_PAID_OFFLINE = 'paid_offline';

    public const OFFLINE_PAYMENT_STATUS_PENDING = 'pending';

    public const OFFLINE_PAYMENT_STATUS_CONFIRMED = 'confirmed';

    public const OFFLINE_PAYMENT_STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_id',
        'created_by',
        'confirmed_by',
        'status',
        'payment_arrangement',
        'offline_payment_status',
        'offline_payment_claimed_by',
        'offline_payment_claimed_at',
        'offline_payment_reviewed_by',
        'offline_payment_reviewed_at',
        'offline_payment_review_notes',
        'activated_at',
        'delivery_date',
        'confirmed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'confirmed_at' => 'datetime',
            'activated_at' => 'datetime',
            'offline_payment_claimed_at' => 'datetime',
            'offline_payment_reviewed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function activationRecipients(): HasMany
    {
        return $this->hasMany(DeliveryActivationRecipient::class);
    }
}
