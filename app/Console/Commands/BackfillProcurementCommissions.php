<?php

namespace App\Console\Commands;

use App\Actions\Earnings\CreateProcurementCommission;
use App\Models\ProcurementOffer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillProcurementCommissions extends Command
{
    protected $signature = 'earnings:backfill-commissions {--dry-run : List what would be credited without writing}';

    protected $description = 'Credit commission for delivery-confirmed procurement offers that have no commission entry yet.';

    public function handle(CreateProcurementCommission $action): int
    {
        $ids = DB::table('procurement_offers as o')
            ->join('procurements as p', 'p.id', '=', 'o.procurement_id')
            ->whereNotNull('o.delivered_at')
            ->where('o.delivered_quantity', '>', 0)
            ->whereIn('o.status', [ProcurementOffer::STATUS_ACCEPTED, ProcurementOffer::STATUS_DELIVERED])
            ->where('p.commission_per_unit', '>', 0)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('staff_wallet_entries as w')
                ->whereColumn('w.procurement_offer_id', 'o.id'))
            ->orderBy('o.id')
            ->pluck('o.id');

        if ($ids->isEmpty()) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        foreach ($ids as $id) {
            $offer = ProcurementOffer::query()->findOrFail($id);

            if ($this->option('dry-run')) {
                $this->line("Would credit offer #{$offer->id} (user {$offer->user_id}, delivered {$offer->delivered_quantity}).");

                continue;
            }

            $entry = $action->execute($offer);
            $this->info($entry
                ? "Credited offer #{$offer->id}: {$entry->amount} to user {$entry->user_id}."
                : "Offer #{$offer->id}: nothing due.");
        }

        return self::SUCCESS;
    }
}
