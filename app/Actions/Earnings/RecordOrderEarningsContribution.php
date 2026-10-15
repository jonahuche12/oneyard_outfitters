<?php

namespace App\Actions\Earnings;

use App\Models\EarningsActivityWeight;
use App\Models\OrderEarningsContribution;
use App\Models\OrderEarningsPool;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecordOrderEarningsContribution
{
    /**
     * Record one staff contribution on a pending earnings pool.
     *
     * Expected attributes:
     * user_id, activity_key, source_type (workflow|manual), activity_at,
     * source_reference (required for workflow), reason (required for manual).
     *
     * Manual contributions need an active actor with
     * earnings.contributions.assign. Workflow contributions are system-recorded
     * and idempotent: repeating one returns the existing row.
     */
    public function execute(
        OrderEarningsPool $pool,
        array $attributes,
        ?User $actor = null
    ): OrderEarningsContribution {
        $isManual = ($attributes['source_type'] ?? null)
            === OrderEarningsContribution::SOURCE_MANUAL;

        if ($isManual && (
            ! $actor
            || ! $actor->is_active
            || ! $actor->hasPermission('earnings.contributions.assign')
        )) {
            throw new AuthorizationException(
                'You are not authorized to assign earnings contributions.'
            );
        }

        $data = Validator::make($attributes, [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'activity_key' => ['required', 'string', 'max:100'],
            'source_type' => [
                'required',
                Rule::in([
                    OrderEarningsContribution::SOURCE_WORKFLOW,
                    OrderEarningsContribution::SOURCE_MANUAL,
                ]),
            ],
            'activity_at' => ['required', 'date'],
            'source_reference' => [
                'required_if:source_type,workflow',
                'nullable',
                'string',
                'max:255',
            ],
            'reason' => [
                'required_if:source_type,manual',
                'nullable',
                'string',
                'max:2000',
            ],
        ])->validate();

        if ($isManual) {
            $data['reason'] = trim((string) $data['reason']);

            if (mb_strlen($data['reason']) < 10) {
                throw ValidationException::withMessages([
                    'reason' => 'A manual assignment needs a reason of at least 10 characters.',
                ]);
            }
        }

        return DB::transaction(function () use ($pool, $data, $isManual, $actor) {
            $lockedPool = OrderEarningsPool::query()
                ->lockForUpdate()
                ->findOrFail($pool->id);

            if ($lockedPool->status === OrderEarningsPool::STATUS_FINALIZED) {
                throw ValidationException::withMessages([
                    'pool' => 'Contributions cannot be recorded on a finalized earnings pool.',
                ]);
            }

            $staff = User::query()->findOrFail($data['user_id']);

            if (! $staff->is_active) {
                throw ValidationException::withMessages([
                    'user_id' => 'Only active staff can earn contributions.',
                ]);
            }

            $weight = EarningsActivityWeight::query()
                ->where('activity_key', $data['activity_key'])
                ->where('is_enabled', true)
                ->first();

            if (! $weight) {
                throw ValidationException::withMessages([
                    'activity_key' => 'This activity has no enabled earnings weight.',
                ]);
            }

            $activityAt = Carbon::parse($data['activity_at']);

            if ($activityAt->isFuture()) {
                throw ValidationException::withMessages([
                    'activity_at' => 'The activity time cannot be in the future.',
                ]);
            }

            if ($lockedPool->delivered_at
                && $activityAt->gt($lockedPool->delivered_at)) {
                throw ValidationException::withMessages([
                    'activity_at' => 'The activity time cannot be after the order was delivered.',
                ]);
            }

            $existing = OrderEarningsContribution::query()
                ->where('order_earnings_pool_id', $lockedPool->id)
                ->where('user_id', $staff->id)
                ->where('activity_key', $data['activity_key'])
                ->active()
                ->first();

            if ($existing) {
                if (! $isManual
                    && $existing->source_type === OrderEarningsContribution::SOURCE_WORKFLOW) {
                    return $existing;
                }

                throw ValidationException::withMessages([
                    'activity_key' => 'This staff member already has a contribution for this activity on this pool.',
                ]);
            }

            return OrderEarningsContribution::query()->create([
                'order_earnings_pool_id' => $lockedPool->id,
                'user_id' => $staff->id,
                'activity_key' => $weight->activity_key,
                'points' => $weight->points,
                'source_type' => $data['source_type'],
                'source_reference' => $data['source_reference'] ?? null,
                'activity_at' => $activityAt,
                'user_active_at_activity' => true,
                'assigned_by' => $isManual ? $actor->id : null,
                'reason' => $isManual ? $data['reason'] : null,
            ]);
        });
    }
}
