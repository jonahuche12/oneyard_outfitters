<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\QualityControlInspection;
use App\Models\ProductionPlanActivity;
use App\Models\User;

class OrderPolicy
{
    public function proceedToDelivery(
        User $user,
        Order $order
    ): bool {
        return $order->status === Order::STATUS_READY
            && $order->delivery === null
            && $user->hasPermission('deliveries.create');
    }


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

    public function confirmProductionComplete(User $user, Order $order): bool
    {
        return $order->status === Order::STATUS_IN_PRODUCTION
            && $order->currentAssignment?->user_id === $user->id
            && $user->hasPermission('production.manage')
            && $order->productionPlan !== null
            && $order->productionPlan->activities()
                ->whereNot('status', ProductionPlanActivity::STATUS_COMPLETED)
                ->doesntExist();
    }

    public function manageProductionPlan(User $user, Order $order): bool
    {
        return $order->status === Order::STATUS_IN_PRODUCTION
            && $order->currentAssignment?->user_id === $user->id
            && $user->hasPermission('production.manage');
    }

    public function resubmitCorrectionToQualityControl(
        User $user,
        Order $order
    ): bool {
        $isAdmin = $user->roles()
            ->whereIn('slug', ['admin', 'super-admin'])
            ->exists();

        if ($order->status !== Order::STATUS_IN_PRODUCTION) {
            return false;
        }

        $hasFailedInspection = $order->qualityControlInspections()
            ->where(
                'status',
                QualityControlInspection::STATUS_COMPLETED
            )
            ->where(
                'result',
                QualityControlInspection::RESULT_FAIL
            )
            ->exists();

        if (!$hasFailedInspection) {
            return false;
        }

        if (!$order->currentAssignment) {
            return $isAdmin;
        }

        return (
            $user->hasPermission('production.manage')
            || $user->hasPermission('orders.assign')
            || $isAdmin
        ) && (
            $order->currentAssignment->user_id === $user->id
            || $isAdmin
        );
    }

    public function returnToProductionForCorrection(User $user, Order $order): bool
    {
        $isAdmin = $user->roles()
            ->whereIn('slug', ['admin', 'super-admin'])
            ->exists();

        return $order->status === Order::STATUS_CORRECTION_REQUIRED
            && (
                $user->hasPermission('production.manage')
                || $isAdmin
            );
    }

    public function viewQualityControl(User $user, ?Order $order = null): bool
    {
        return $user->hasPermission('quality-control.view')
            || $user->roles()
                ->whereIn('slug', ['admin', 'super-admin'])
                ->exists();
    }

    public function inspectQualityControl(User $user, Order $order): bool
    {
        $isAdmin = $user->roles()
            ->whereIn('slug', ['admin', 'super-admin'])
            ->exists();

        return $order->status === Order::STATUS_READY_FOR_QUALITY_CONTROL
            && (
                $user->hasPermission('quality-control.inspect')
                || $isAdmin
            );
    }

    public function approveQualityControl(User $user, Order $order): bool
    {
        $isAdmin = $user->roles()
            ->whereIn('slug', ['admin', 'super-admin'])
            ->exists();

        return $order->status === Order::STATUS_READY_FOR_QUALITY_CONTROL
            && (
                $user->hasPermission('quality-control.approve')
                || $isAdmin
            );
    }

}
