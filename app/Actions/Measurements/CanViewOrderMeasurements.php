<?php

namespace App\Actions\Measurements;

use App\Models\Order;
use App\Models\User;

class CanViewOrderMeasurements
{
    public function handle(User $user, Order $order): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->roles()->whereIn('slug', ['admin', 'super-admin'])->exists()) {
            return true;
        }

        $coordinatorId = optional($order->currentAssignment()->first())->user_id;

        return $coordinatorId !== null && (int) $coordinatorId === (int) $user->id;
    }
}
