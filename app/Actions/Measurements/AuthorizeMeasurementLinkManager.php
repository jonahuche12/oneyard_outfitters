<?php

namespace App\Actions\Measurements;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class AuthorizeMeasurementLinkManager
{
    public function handle(User $actor, Order $order): void
    {
        if (! $actor->is_active || ! $actor->hasPermission('measurements.links.manage')) {
            throw new AuthorizationException('You are not authorized to manage measurement links.');
        }

        $isAdmin = $actor->roles()->whereIn('slug', ['admin', 'super-admin'])->exists();
        $isCoordinator = (int) optional($order->currentAssignment()->first())->user_id === (int) $actor->id;

        if (! $isAdmin && ! $isCoordinator) {
            throw new AuthorizationException('Only the order coordinator or an administrator can manage measurement links.');
        }
    }
}
