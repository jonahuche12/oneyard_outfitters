<?php

namespace App\Actions\Measurements;

use App\Models\MeasurementLink;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RevokeMeasurementLink
{
    public function execute(User $actor, MeasurementLink $link, array $input): MeasurementLink
    {
        app(AuthorizeMeasurementLinkManager::class)
            ->handle($actor, Order::query()->findOrFail($link->order_id));

        $input['reason'] = is_string($input['reason'] ?? null) ? trim($input['reason']) : ($input['reason'] ?? null);

        $validated = Validator::make($input, [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($actor, $link, $validated): MeasurementLink {
            $locked = MeasurementLink::query()->lockForUpdate()->findOrFail($link->getKey());

            if ($locked->revoked_at !== null) {
                throw ValidationException::withMessages([
                    'link' => 'This link has already been revoked.',
                ]);
            }

            $locked->forceFill([
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
                'revoke_reason' => $validated['reason'],
                'is_active_slot' => null,
            ])->save();

            return $locked->refresh();
        });
    }
}
