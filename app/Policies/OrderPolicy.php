<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\ProductionPlanActivity;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.update');
    }

    public function approve(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.approve')
            && $order->status === Order::STATUS_PENDING;
    }

    public function resendApproval(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.approve')
            && $order->status === Order::STATUS_APPROVED;
    }

    public function assign(User $user, Order $order): bool
    {
        $canAssign = $user->hasPermission('orders.assign')
            || $user->roles()
                ->whereIn('slug', ['super-admin', 'admin'])
                ->exists();

        return $canAssign
            && in_array($order->status, [
                Order::STATUS_APPROVED,
                Order::STATUS_IN_PRODUCTION,
            ], true);
    }


    public function startActivity(
        User $user,
        Order $order,
        ProductionPlanActivity $activity
    ): bool {
        return $order->status === Order::STATUS_IN_PRODUCTION
            && $order->currentAssignment?->user_id === $user->id
            && $user->hasPermission('production.manage')
            && $activity->production_plan_id === $order->productionPlan?->id;
    }

    public function completeActivity(
        User $user,
        Order $order,
        ProductionPlanActivity $activity
    ): bool {
        return $order->status === Order::STATUS_IN_PRODUCTION
            && $order->currentAssignment?->user_id === $user->id
            && $user->hasPermission('production.manage')
            && $activity->production_plan_id === $order->productionPlan?->id;
    }

    public function unmarkActivity(
        User $user,
        Order $order,
        ProductionPlanActivity $activity
    ): bool {
        return $order->status === Order::STATUS_IN_PRODUCTION
            && $user->hasPermission('production.manage')
            && $user->roles()
                ->whereIn('slug', ['admin', 'super-admin'])
                ->exists()
            && $activity->production_plan_id === $order->productionPlan?->id;
    }

    public function manageProductionPlan(User $user, Order $order): bool
    {
        return $order->status === Order::STATUS_IN_PRODUCTION
            && $order->currentAssignment?->user_id === $user->id
            && $user->hasPermission('production.manage');
    }
}
