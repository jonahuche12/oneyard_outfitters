<?php

namespace App\Actions\Earnings;

use App\Models\OrderEarningsContribution;
use App\Models\OrderEarningsPool;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidOrderEarningsContribution
{
    /**
     * Void a contribution on a pending or held pool. The row is kept for history.
     */
    public function execute(OrderEarningsContribution $contribution, string $reason, User $actor): OrderEarningsContribution
    {
        if (! $actor->is_active || ! $actor->hasPermission('earnings.contributions.assign')) {
            throw new AuthorizationException('You are not authorized to void earnings contributions.');
        }

        $reason = trim($reason);

        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages([
                'void_reason' => 'A reason of at least 10 characters is required.',
            ]);
        }

        return DB::transaction(function () use ($contribution, $reason, $actor) {
            $pool = OrderEarningsPool::query()
                ->lockForUpdate()
                ->findOrFail($contribution->order_earnings_pool_id);

            if ($pool->status === OrderEarningsPool::STATUS_FINALIZED) {
                throw ValidationException::withMessages([
                    'contribution' => 'Contributions on a finalized pool cannot be voided. Use an adjustment or reversal.',
                ]);
            }

            $locked = OrderEarningsContribution::query()
                ->lockForUpdate()
                ->findOrFail($contribution->id);

            if ($locked->voided_at !== null) {
                throw ValidationException::withMessages([
                    'contribution' => 'This contribution has already been voided.',
                ]);
            }

            $locked->update([
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'void_reason' => $reason,
                'is_active_slot' => null,
            ]);

            return $locked->fresh();
        });
    }
}
