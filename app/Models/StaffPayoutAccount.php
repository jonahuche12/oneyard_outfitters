<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPayoutAccount extends Model
{
    protected $fillable = [
        'user_id', 'bank_name', 'bank_code', 'account_name',
        'account_number', 'last_four', 'is_active', 'usable_from',
    ];

    protected $hidden = ['account_number'];

    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',
            'is_active' => 'boolean',
            'usable_from' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        return $this->is_active && $this->usable_from->lte(now());
    }
}
