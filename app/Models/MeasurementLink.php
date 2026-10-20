<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeasurementLink extends Model
{
    protected $fillable = [
        'order_id',
        'contact_id',
        'token_hash',
        'expires_at',
        'sent_at',
        'first_accessed_at',
        'last_accessed_at',
        'revoked_at',
        'revoked_by',
        'revoke_reason',
        'created_by',
        'is_active_slot',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'first_accessed_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'revoked_at' => 'datetime',
            'is_active_slot' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
