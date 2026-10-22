<?php

namespace App\Actions\Measurements;

use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewMeasurementSubmission
{
    public function accept(User $actor, MeasurementSubmission $submission): MeasurementSubmission
    {
        return $this->review($actor, $submission, 'accepted', null);
    }

    public function requestCorrection(User $actor, MeasurementSubmission $submission, string $reason): MeasurementSubmission
    {
        $reason = trim($reason);
        $length = mb_strlen($reason);

        if ($length < 10 || $length > 1000) {
            throw ValidationException::withMessages([
                'reason' => 'Explain what needs correcting (10 to 1000 characters).',
            ]);
        }

        return $this->review($actor, $submission, 'correction_requested', $reason);
    }

    private function review(User $actor, MeasurementSubmission $submission, string $status, ?string $reason): MeasurementSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $status, $reason): MeasurementSubmission {
            $locked = MeasurementSubmission::query()->lockForUpdate()->findOrFail($submission->getKey());
            $item = $locked->orderItem()->firstOrFail();
            $order = Order::query()->lockForUpdate()->findOrFail($item->order_id);

            if (
                ! $actor->is_active
                || ! $actor->hasPermission('measurements.review')
                || ! app(CanViewOrderMeasurements::class)->handle($actor, $order)
            ) {
                throw new AuthorizationException('You are not authorized to review measurements for this order.');
            }

            $open = $order->status === Order::STATUS_APPROVED
                || ($order->status === Order::STATUS_IN_PRODUCTION && ! $order->productionPlan()->exists());
            if (! $open) {
                throw ValidationException::withMessages([
                    'measurements' => 'Measurements can no longer be reviewed for this order.',
                ]);
            }

            if ($locked->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'measurements' => 'Only a submitted revision can be reviewed.',
                ]);
            }

            $latest = MeasurementSubmission::query()
                ->where('order_item_id', $locked->order_item_id)
                ->max('revision');
            if ((int) $latest !== (int) $locked->revision) {
                throw ValidationException::withMessages([
                    'measurements' => 'This is not the latest revision.',
                ]);
            }

            $locked->forceFill([
                'status' => $status,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'correction_reason' => $reason,
            ])->save();

            return $locked->fresh();
        });
    }
}
