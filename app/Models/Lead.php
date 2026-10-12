<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_LOST = 'lost';

    public const SOURCE_WEBSITE = 'website';
    public const SOURCE_FACEBOOK = 'facebook';
    public const SOURCE_INSTAGRAM = 'instagram';
    public const SOURCE_WHATSAPP = 'whatsapp';
    public const SOURCE_REFERRAL = 'referral';
    public const SOURCE_PHYSICAL_VISIT = 'physical_visit';
    public const SOURCE_EXISTING_CLIENT = 'existing_client';
    public const SOURCE_OTHER = 'other';

    protected $fillable = [
        'name',
        'email',
        'organization_name',
        'phone',
        'category',
        'message',
        'source',
        'status',
        'created_by',
        'assigned_to',
        'organization_id',
        'converted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
