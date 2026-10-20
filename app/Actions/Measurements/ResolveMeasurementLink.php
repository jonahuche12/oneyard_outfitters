<?php

namespace App\Actions\Measurements;

use App\Models\MeasurementLink;
use App\Models\Order;

class ResolveMeasurementLink
{
    /**
     * Returns the link only when the token is valid. Every failure returns null
     * so a caller cannot tell why a token was rejected.
     */
    public function execute(?string $rawToken): ?MeasurementLink
    {
        if (! is_string($rawToken) || strlen($rawToken) !== 48) {
            return null;
        }

        $link = MeasurementLink::query()
            ->where('token_hash', IssueMeasurementLink::hash($rawToken))
            ->first();

        if (! $link || $link->revoked_at !== null || $link->expires_at->isPast()) {
            return null;
        }

        $order = Order::query()->find($link->order_id);

        $qualifies = $order
            && ($order->status === Order::STATUS_APPROVED
                || ($order->status === Order::STATUS_IN_PRODUCTION && ! $order->productionPlan()->exists()))
            && $order->items()->where('measurement_eligibility', 'required')->exists();

        if (! $qualifies) {
            return null;
        }

        $link->forceFill([
            'first_accessed_at' => $link->first_accessed_at ?? now(),
            'last_accessed_at' => now(),
        ])->save();

        return $link;
    }
}
