<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'contact_id',
        'email',
        'access_token',
        'sent_at',
        'viewed_at',
        'responded_at',
        'response_status',
        'payment_percentage',
        'payment_amount',
        'amount_paid',
        'rejection_feedback',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'responded_at' => 'datetime',
            'payment_percentage' => 'integer',
            'payment_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'payment_percentage' => 'integer',
            'payment_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }


    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
