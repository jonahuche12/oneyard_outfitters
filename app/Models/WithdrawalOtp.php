<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalOtp extends Model
{
    protected $table = 'withdrawal_otps';

    protected $fillable = [
        'user_id', 'payout_account_id', 'amount_cents', 'code_hash',
        'expires_at', 'attempts', 'consumed_at', 'invalidated_at', 'sent_at',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'invalidated_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}
