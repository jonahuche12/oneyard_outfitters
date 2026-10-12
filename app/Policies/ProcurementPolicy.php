<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Procurement;
use App\Models\User;

class ProcurementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('procurement.view');
    }

    public function view(User $user, Procurement $procurement): bool
    {
        return $user->hasPermission('procurement.view');
    }

    public function submitOffer(
        User $user,
        Procurement $procurement
    ): bool {
        return $user->hasPermission('procurement.view')
            || $user->roles()
                ->whereIn('slug', ['admin', 'super-admin'])
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('procurement.manage')
            && $user->roles()
                ->whereIn('slug', ['admin', 'super-admin'])
                ->exists();
    }

    public function createForOrder(User $user, Order $order): bool
    {
        if ($order->currentAssignment?->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('procurement.manage')
            && $user->roles()
                ->whereIn('slug', ['admin', 'super-admin'])
                ->exists();
    }

    public function update(User $user, Procurement $procurement): bool
    {
        if (! $user->hasPermission('procurement.manage')) {
            return false;
        }

        if ($user->roles()
            ->whereIn('slug', ['admin', 'super-admin'])
            ->exists()) {
            return true;
        }

        return $procurement->order_id !== null
            && $procurement->order?->currentAssignment?->user_id === $user->id;
    }
}
