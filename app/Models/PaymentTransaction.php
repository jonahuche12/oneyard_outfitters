<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_recipient_id',
        'delivery_id',
        'reference',
        'amount',
        'currency',
        'gateway',
        'status',
        'gateway_transaction_id',
        'access_code',
        'authorization_url',
        'initialized_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'initialized_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function quotationRecipient(): BelongsTo
    {
        return $this->belongsTo(QuotationRecipient::class);
    }


    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }


    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
