<?php

namespace App\Actions\Earnings;

use App\Models\Order;
use App\Models\OrderEarningsAllocation;
use App\Models\OrderEarningsPool;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class FinalizeOrderEarningsPool
{
    public function __construct(
        private CalculateOrderEarningsAllocation $calculator
    ) {
    }

    /**
     * Finalize a pool into immutable per-staff allocations, or persist a hold.
     *
     * Requires an active actor with earnings.pools.finalize. The order must be
     * delivered and its completed quotation payments must cover the order
     * total (within one cent, the same rule the delivery policy applies).
     * A held pool may be finalized again once the cause is resolved.
     */
    public function execute(OrderEarningsPool $pool, User $actor): OrderEarningsPool
    {
        if (! $actor->is_active
            || ! $actor->hasPermission('earnings.pools.finalize')) {
            throw new AuthorizationException(
                'You are not authorized to finalize earnings pools.'
            );
        }

        return DB::transaction(function () use ($pool, $actor) {
            $locked = OrderEarningsPool::query()
                ->lockForUpdate()
                ->findOrFail($pool->id);

            if ($locked->status === OrderEarningsPool::STATUS_FINALIZED) {
                throw ValidationException::withMessages([
                    'pool' => 'This earnings pool has already been finalized.',
                ]);
            }

            $previousStatus = $locked->status;

            $order = Order::query()
                ->with('quotation')
                ->findOrFail($locked->order_id);

            if ($order->status !== Order::STATUS_DELIVERED) {
                throw ValidationException::withMessages([
                    'order' => 'The order must be delivered before earnings are finalized.',
                ]);
            }

            if (! $order->quotation) {
                throw ValidationException::withMessages([
                    'order' => 'The order has no source quotation.',
                ]);
            }

            $payments = $order->quotation->payments()
                ->where('status', 'completed')
                ->get(['id', 'amount']);

            $paidCents = (int) $payments->sum(
                fn ($payment) => $this->toCents((string) $payment->amount)
            );
            $orderCents = $this->toCents((string) $order->total);

            if ($orderCents - $paidCents > 1) {
                throw ValidationException::withMessages([
                    'payment' => 'The order is not fully paid; earnings cannot be finalized.',
                ]);
            }

            $result = $this->calculator->execute($locked);

            if ($result['verdict'] === CalculateOrderEarningsAllocation::VERDICT_HELD) {
                $locked->update([
                    'status' => OrderEarningsPool::STATUS_HELD,
                    'held_at' => now(),
                    'hold_reasons' => [
                        'reasons' => $result['hold_reasons'],
                        'inactive_user_ids' => $result['inactive_user_ids'],
                        'checked_by' => $actor->id,
                    ],
                ]);

                return $locked->fresh();
            }

            $poolCents = $this->toCents((string) $locked->pool_amount);
            $allocatedCents = (int) collect($result['allocations'])->sum(
                fn ($row) => $this->toCents($row['amount'])
            );

            if ($allocatedCents !== $poolCents) {
                throw new LogicException(
                    'Allocated amounts do not equal the earnings pool.'
                );
            }

            foreach ($result['allocations'] as $row) {
                OrderEarningsAllocation::query()->create([
                    'order_earnings_pool_id' => $locked->id,
                    'user_id' => $row['user_id'],
                    'amount' => $row['amount'],
                    'weight' => $row['weight'],
                    'weight_scale' => $row['weight_scale'],
                    'effective_points' => $row['effective_points'],
                ]);
            }

            $beneficiaryIds = array_column($result['allocations'], 'user_id');

            $locked->update([
                'status' => OrderEarningsPool::STATUS_FINALIZED,
                'finalized_by' => $actor->id,
                'finalized_at' => now(),
                'held_at' => null,
                'hold_reasons' => null,
                'finalization_snapshot' => [
                    'previous_status' => $previousStatus,
                    'calculation' => $result,
                    'payment_check' => [
                        'rule' => 'Completed quotation payments cover the order total within one cent.',
                        'order_total' => $this->fromCents($orderCents),
                        'paid_total' => $this->fromCents($paidCents),
                        'payment_ids' => $payments->pluck('id')->all(),
                    ],
                    'finalized_by' => $actor->id,
                    'finalizer_is_beneficiary' => in_array(
                        $actor->id,
                        $beneficiaryIds,
                        true
                    ),
                ],
            ]);

            return $locked->fresh(['allocations']);
        });
    }

    private function toCents(string $amount): int
    {
        return (int) str_replace(
            '.',
            '',
            number_format((float) $amount, 2, '.', '')
        );
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
