<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'quotation_number',
        'organization_id',
        'contact_id',
        'created_by',
        'quotation_date',
        'valid_until',
        'expected_delivery_days',
        'status',
        'subtotal',
        'discount',
        'additional_charges',
        'total',
        'terms',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'expected_delivery_days' => 'integer',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'additional_charges' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(QuotationRecipient::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)
            ->orderBy('sort_order');
    }


    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
