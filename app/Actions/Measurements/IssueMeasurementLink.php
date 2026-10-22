<?php

namespace App\Actions\Measurements;

use App\Models\Contact;
use App\Models\MeasurementLink;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IssueMeasurementLink
{
    public const EXPIRY_DAYS = 3;
    public const MINIMUM_EXPIRY_DAYS = 3;

    public static function hash(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    /**
     * @return array{link: MeasurementLink, token: string} The raw token is returned once and never stored.
     */
    public function execute(User $actor, Order $order, Contact $contact): array
    {
        app(AuthorizeMeasurementLinkManager::class)->handle($actor, $order);

        return DB::transaction(function () use ($actor, $order, $contact): array {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            $inProductionWithoutPlan = $lockedOrder->status === Order::STATUS_IN_PRODUCTION
                && ! $lockedOrder->productionPlan()->exists();

            if ($lockedOrder->status !== Order::STATUS_APPROVED && ! $inProductionWithoutPlan) {
                throw ValidationException::withMessages([
                    'order' => 'Measurement links can only be issued before the production plan is created.',
                ]);
            }

            if (! $lockedOrder->items()->where('measurement_eligibility', 'required')->exists()) {
                throw ValidationException::withMessages([
                    'order' => 'No item on this order requires measurements.',
                ]);
            }

            if ((int) $contact->organization_id !== (int) $lockedOrder->organization_id) {
                throw ValidationException::withMessages([
                    'contact_id' => 'The contact does not belong to this order\'s organization.',
                ]);
            }

            if (! $contact->is_active || blank($contact->email)) {
                throw ValidationException::withMessages([
                    'contact_id' => 'The contact must be active and have an email address.',
                ]);
            }

            MeasurementLink::query()
                ->where('order_id', $lockedOrder->id)
                ->where('contact_id', $contact->id)
                ->where('is_active_slot', 1)
                ->lockForUpdate()
                ->get()
                ->each(fn (MeasurementLink $old) => $old->forceFill([
                    'revoked_at' => now(),
                    'revoked_by' => $actor->id,
                    'revoke_reason' => 'Replaced by a newly issued link.',
                    'is_active_slot' => null,
                ])->save());

            $rawToken = Str::random(48);

            $link = MeasurementLink::query()->create([
                'order_id' => $lockedOrder->id,
                'contact_id' => $contact->id,
                'token_hash' => self::hash($rawToken),
                'expires_at' => now()->addDays(max(self::MINIMUM_EXPIRY_DAYS, self::EXPIRY_DAYS)),
                'created_by' => $actor->id,
                'is_active_slot' => 1,
            ]);

            return ['link' => $link, 'token' => $rawToken];
        });
    }
}
