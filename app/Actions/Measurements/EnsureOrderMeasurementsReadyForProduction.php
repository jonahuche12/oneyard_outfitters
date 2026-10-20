<?php

namespace App\Actions\Measurements;

use App\Models\Order;
use Illuminate\Validation\ValidationException;

class EnsureOrderMeasurementsReadyForProduction
{
    /**
     * Prevent an order from entering production until each order item has
     * a resolved measurement decision, and each required item has an
     * accepted latest measurement revision.
     *
     * @throws ValidationException
     */
    public function handle(Order $order): void
    {
        $items = $order->items()
            ->with([
                'measurementSubmissions' => fn ($query) => $query
                    ->orderByDesc('revision'),
            ])
            ->get();

        foreach ($items as $item) {
            $eligibility = $item->measurement_eligibility ?? 'pending';

            if ($eligibility === 'not_required') {
                continue;
            }

            if ($eligibility !== 'required') {
                throw ValidationException::withMessages([
                    'measurements' => "Production is blocked: measurement eligibility for \"{$item->item_name}\" has not been resolved.",
                ]);
            }

            $latestSubmission = $item->measurementSubmissions->first();

            if (! $latestSubmission || $latestSubmission->status !== 'accepted') {
                throw ValidationException::withMessages([
                    'measurements' => "Production is blocked: \"{$item->item_name}\" requires an accepted latest measurement revision.",
                ]);
            }
        }
    }
}
