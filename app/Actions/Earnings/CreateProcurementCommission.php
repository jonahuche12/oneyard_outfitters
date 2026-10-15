<?php

namespace App\Actions\Earnings;

use App\Models\Procurement;
use App\Models\ProcurementOffer;
use App\Models\StaffWalletEntry;
use Illuminate\Support\Facades\DB;

class CreateProcurementCommission
{
    /**
     * Credit delivered quantity x commission_per_unit to the offering staff
     * member's wallet. Idempotent per offer. Returns null when nothing is due.
     * Call inside the delivery-confirmation transaction.
     */
    public function execute(ProcurementOffer $offer): ?StaffWalletEntry
    {
        return DB::transaction(function () use ($offer) {
            $locked = ProcurementOffer::query()->lockForUpdate()->findOrFail($offer->id);

            if ($locked->delivered_at === null
                || (float) $locked->delivered_quantity <= 0
                || ! in_array($locked->status, [ProcurementOffer::STATUS_ACCEPTED, ProcurementOffer::STATUS_DELIVERED], true)) {
                return null;
            }

            $existing = StaffWalletEntry::query()
                ->where('procurement_offer_id', $locked->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $procurement = Procurement::withTrashed()->find($locked->procurement_id);

            if (! $procurement) {
                return null;
            }

            $quantityHundredths = (int) round(((float) $locked->delivered_quantity) * 100);
            $rateCents = (int) round(((float) $procurement->commission_per_unit) * 100);

            // hundredths x cents = ten-thousandths of a currency unit; round half up to cents.
            $amountCents = intdiv($quantityHundredths * $rateCents + 50, 100);

            if ($amountCents <= 0) {
                return null;
            }

            return StaffWalletEntry::query()->create([
                'user_id' => $locked->user_id,
                'type' => StaffWalletEntry::TYPE_COMMISSION,
                'amount' => number_format($amountCents / 100, 2, '.', ''),
                'procurement_offer_id' => $locked->id,
                'reason' => sprintf(
                    'Commission: %s x NGN %s for %s (offer #%d)',
                    rtrim(rtrim(number_format((float) $locked->delivered_quantity, 2, '.', ''), '0'), '.'),
                    number_format($rateCents / 100, 2, '.', ''),
                    $procurement->item_name,
                    $locked->id
                ),
                'created_by' => $locked->delivered_by,
            ]);
        });
    }
}
