<?php

namespace App\Policies;

use App\Models\StaffWalletEntry;
use App\Models\User;

class StaffWalletEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasPermission('earnings.wallets.view');
    }

    public function adjust(User $user): bool
    {
        return $user->is_active && $user->hasPermission('earnings.adjustments.create');
    }
}
