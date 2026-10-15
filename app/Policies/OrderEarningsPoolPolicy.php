<?php

namespace App\Policies;

use App\Models\OrderEarningsPool;
use App\Models\User;

class OrderEarningsPoolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasPermission('earnings.pools.view');
    }

    public function view(User $user, OrderEarningsPool $pool): bool
    {
        return $this->viewAny($user);
    }
}
