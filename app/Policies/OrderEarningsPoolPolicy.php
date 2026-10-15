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

    public function assignContribution(User $user, OrderEarningsPool $pool): bool
    {
        return $user->is_active
            && $user->hasPermission('earnings.contributions.assign')
            && $pool->status !== OrderEarningsPool::STATUS_FINALIZED;
    }

    public function voidContribution(User $user, OrderEarningsPool $pool): bool
    {
        return $user->is_active
            && $user->hasPermission('earnings.contributions.assign')
            && $pool->status !== OrderEarningsPool::STATUS_FINALIZED;
    }

    public function finalize(User $user, OrderEarningsPool $pool): bool
    {
        return $user->is_active
            && $user->hasPermission('earnings.pools.finalize')
            && $pool->status !== OrderEarningsPool::STATUS_FINALIZED;
    }

    public function deriveContributions(User $user, OrderEarningsPool $pool): bool
    {
        return $user->is_active
            && $user->hasPermission('earnings.contributions.assign')
            && $pool->status !== OrderEarningsPool::STATUS_FINALIZED;
    }
}
