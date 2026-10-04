<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;

class DeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('deliveries.view');
    }

    public function view(User $user, Delivery $delivery): bool
    {
        return $user->hasPermission('deliveries.view');
    }

    public function create(User $user, Order $order): bool
    {
        return $order->status === Order::STATUS_READY
            && $order->delivery === null
            && $user->hasPermission('deliveries.create');
    }

    public function confirm(User $user, Delivery $delivery): bool
    {
        return $delivery->status === Delivery::STATUS_PENDING
            && $delivery->activated_at !== null
            && $delivery->payment_arrangement !== null
            && $delivery->payment_arrangement !== Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
            && $user->hasPermission('deliveries.confirm');
    }

    public function claimOfflinePayment(User $user, Delivery $delivery): bool
    {
        return $delivery->status === Delivery::STATUS_PENDING
            && $delivery->activated_at !== null
            && $delivery->payment_arrangement === Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
            && in_array(
                $delivery->offline_payment_status,
                [null, Delivery::OFFLINE_PAYMENT_STATUS_REJECTED],
                true
            )
            && $user->hasPermission('deliveries.confirm');
    }

    public function confirmOfflinePayment(User $user, Delivery $delivery): bool
    {
        return $delivery->status === Delivery::STATUS_PENDING
            && $delivery->payment_arrangement === Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
            && $delivery->offline_payment_status === Delivery::OFFLINE_PAYMENT_STATUS_PENDING
            && $user->hasPermission('deliveries.review_offline_payment');
    }

    public function rejectOfflinePayment(User $user, Delivery $delivery): bool
    {
        return $delivery->status === Delivery::STATUS_PENDING
            && $delivery->payment_arrangement === Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
            && $delivery->offline_payment_status === Delivery::OFFLINE_PAYMENT_STATUS_PENDING
            && $user->hasPermission('deliveries.review_offline_payment');
    }
}
