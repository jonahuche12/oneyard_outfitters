<?php

namespace App\Actions\Measurements;

use App\Models\OrderItem;
use App\Models\OrderItemMeasurementEligibilityAudit;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ResolveOrderItemMeasurementEligibility
{
    public function execute(User $actor, OrderItem $orderItem, array $input): OrderItem
    {
        if (! $actor->is_active || ! $actor->hasPermission('measurements.eligibility.manage')) {
            throw new AuthorizationException(
                'You are not authorized to manage measurement eligibility.'
            );
        }

        $input['reason'] = is_string($input['reason'] ?? null)
            ? trim($input['reason'])
            : $input['reason'] ?? null;

        $validated = Validator::make($input, [
            'eligibility' => ['required', 'string', 'in:required,not_required'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($actor, $orderItem, $validated): OrderItem {
            $item = OrderItem::query()
                ->lockForUpdate()
                ->findOrFail($orderItem->getKey());

            $oldEligibility = $item->measurement_eligibility ?? 'pending';
            $newEligibility = $validated['eligibility'];
            $reason = trim($validated['reason']);

            if ($oldEligibility === $newEligibility) {
                throw ValidationException::withMessages([
                    'eligibility' => 'This item is already marked as ' . $newEligibility . '.',
                ]);
            }

            if (
                $newEligibility === 'not_required'
                && \App\Models\MeasurementSubmission::query()
                    ->where('order_item_id', $item->id)
                    ->whereIn('status', ['submitted', 'accepted'])
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'eligibility' => 'Measurements cannot be marked not required while a submitted or accepted revision exists.',
                ]);
            }

            $item->forceFill([
                'measurement_eligibility' => $newEligibility,
                'measurement_eligibility_reason' => $reason,
                'measurement_eligibility_decided_by' => $actor->id,
                'measurement_eligibility_decided_at' => now(),
            ])->save();

            OrderItemMeasurementEligibilityAudit::query()->create([
                'order_item_id' => $item->id,
                'old_eligibility' => $oldEligibility,
                'new_eligibility' => $newEligibility,
                'changed_by' => $actor->id,
                'reason' => $reason,
            ]);

            return $item->refresh();
        });
    }
}
