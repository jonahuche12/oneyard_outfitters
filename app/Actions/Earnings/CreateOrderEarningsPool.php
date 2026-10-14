<?php

namespace App\Actions\Earnings;

use App\Models\Order;
use App\Models\OrderEarningsPool;
use Illuminate\Support\Facades\DB;
use LogicException;

class CreateOrderEarningsPool
{
    public function execute(Order $order): OrderEarningsPool
    {
        if ($order->status !== Order::STATUS_DELIVERED) {
            throw new LogicException(
                'An earnings pool can only be created for a delivered order.'
            );
        }

        $order->loadMissing('quotation');

        $quotation = $order->quotation;

        if (! $quotation) {
            throw new LogicException(
                'A delivered order must have a source quotation for earnings.'
            );
        }

        return DB::transaction(function () use ($order, $quotation) {
            // Use the order's persisted total as the order-specific amount basis.
            // Integer cents avoid floating-point calculation drift.
            $sourceCents = (int) round(
                ((float) $order->total) * 100,
                0,
                PHP_ROUND_HALF_UP
            );

            if ($sourceCents < 0) {
                throw new LogicException(
                    'The order amount cannot be negative.'
                );
            }

            $poolCents = (int) round(
                $sourceCents * 0.05,
                0,
                PHP_ROUND_HALF_UP
            );

            $sourceAmount = number_format($sourceCents / 100, 2, '.', '');
            $poolAmount = number_format($poolCents / 100, 2, '.', '');

            // firstOrCreate plus the unique order_id constraint protects against
            // duplicate pool creation if the delivery event is retried.
            return OrderEarningsPool::query()->firstOrCreate(
                ['order_id' => $order->id],
                [
                    'quotation_id' => $quotation->id,
                    'quotation_number' => $quotation->quotation_number,
                    'source_amount' => $sourceAmount,
                    'rate_percent' => '5.00',
                    'pool_amount' => $poolAmount,
                    'source_snapshot' => [
                        'basis' => 'order.total',
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_total' => $sourceAmount,
                        'quotation_id' => $quotation->id,
                        'quotation_number' => $quotation->quotation_number,
                        'quotation_total_at_pool_creation' =>
                            (string) $quotation->total,
                        'rate_percent' => '5.00',
                        'pool_amount' => $poolAmount,
                        'calculation' =>
                            '5% of the persisted order total, rounded to two decimal places.',
                    ],
                    'delivered_at' => now(),
                    'status' => OrderEarningsPool::STATUS_PENDING,
                ]
            );
        });
    }
}
