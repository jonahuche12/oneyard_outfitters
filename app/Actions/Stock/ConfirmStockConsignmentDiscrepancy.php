<?php

namespace App\Actions\Stock;

use App\Models\StockConsignment;
use App\Models\StockConsignmentDiscrepancy;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmStockConsignmentDiscrepancy
{
    public function execute(
        User $actor,
        StockConsignmentDiscrepancy $discrepancy,
        array $data
    ): StockConsignmentDiscrepancy {
        $validated = validator($data, [
            'resolution_reason' => ['required', 'string', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use (
            $actor,
            $discrepancy,
            $validated
        ): StockConsignmentDiscrepancy {
            $lockedActor = User::query()
                ->whereKey($actor->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedActor->is_active) {
                abort(403, 'Your account is inactive.');
            }

            $lockedDiscrepancy = StockConsignmentDiscrepancy::query()
                ->whereKey($discrepancy->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDiscrepancy->status !== 'open') {
                throw ValidationException::withMessages([
                    'discrepancy' => 'This discrepancy has already been resolved.',
                ]);
            }

            $line = $lockedDiscrepancy->consignmentItem()
                ->lockForUpdate()
                ->firstOrFail();

            $consignment = StockConsignment::query()
                ->whereKey($line->stock_consignment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($consignment->status, [
                StockConsignment::STATUS_CANCELLED,
                StockConsignment::STATUS_RECONCILED,
            ], true)) {
                throw ValidationException::withMessages([
                    'discrepancy' => 'This consignment cannot accept further discrepancy confirmations.',
                ]);
            }

            $source = Store::query()
                ->whereKey($consignment->source_store_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $source->is_active) {
                abort(403, 'The sending store is inactive.');
            }

            $assignment = StoreStaff::query()
                ->where('user_id', $lockedActor->id)
                ->where('store_id', $source->id)
                ->where('role', StoreStaff::ROLE_COORDINATOR)
                ->whereNull('ends_at')
                ->lockForUpdate()
                ->first();

            abort_unless($assignment, 403);

            $resolution = match ($lockedDiscrepancy->type) {
                'damaged' => 'written_off',
                'shortage' => 'confirmed',
                default => throw ValidationException::withMessages([
                    'discrepancy' => 'Unsupported discrepancy type.',
                ]),
            };

            if ($lockedDiscrepancy->type === 'damaged') {
                $destination = Store::query()
                    ->whereKey($consignment->destination_store_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $level = StockLevel::query()
                    ->where('store_id', $destination->id)
                    ->where('stock_item_id', $line->stock_item_id)
                    ->lockForUpdate()
                    ->first();

                if (! $level) {
                    throw ValidationException::withMessages([
                        'discrepancy' => 'The quarantined stock balance could not be found.',
                    ]);
                }

                $quantity = (string) $lockedDiscrepancy->quantity;

                if (bccomp((string) $level->quantity_quarantined, $quantity, 3) < 0) {
                    throw ValidationException::withMessages([
                        'discrepancy' => 'The quarantined balance is insufficient for this write-off.',
                    ]);
                }

                $writeOffValue = bcdiv(
                    bcmul($quantity, (string) $line->unit_cost, 6),
                    '1',
                    2
                );

                if (bccomp((string) $level->quarantine_value, $writeOffValue, 2) < 0) {
                    throw ValidationException::withMessages([
                        'discrepancy' => 'The quarantined stock value is insufficient for this write-off.',
                    ]);
                }

                $level->quantity_quarantined = bcsub(
                    (string) $level->quantity_quarantined,
                    $quantity,
                    3
                );

                $level->quarantine_value = bcsub(
                    (string) $level->quarantine_value,
                    $writeOffValue,
                    2
                );

                $level->save();

                StockMovement::query()->create([
                    'stock_item_id' => $line->stock_item_id,
                    'store_id' => $destination->id,
                    'type' => StockMovement::TYPE_CONSIGNMENT_WRITE_OFF,
                    'quantity' => $quantity,
                    'unit_cost' => $line->unit_cost,
                    'movement_value' => $writeOffValue,
                    'source_type' => StockConsignmentDiscrepancy::class,
                    'source_id' => $lockedDiscrepancy->id,
                    'reason' => 'Damaged consignment stock written off after sending-store confirmation: '.$validated['resolution_reason'],
                    'created_by' => $lockedActor->id,
                ]);
            }

            $lockedDiscrepancy->status = 'resolved';
            $lockedDiscrepancy->resolution = $resolution;
            $lockedDiscrepancy->resolution_reason = trim($validated['resolution_reason']);
            $lockedDiscrepancy->resolved_by = $lockedActor->id;
            $lockedDiscrepancy->resolved_at = now();
            $lockedDiscrepancy->save();

            $lineIds = $consignment->items()->pluck('id');

            $hasOpenDiscrepancies = StockConsignmentDiscrepancy::query()
                ->whereIn('stock_consignment_item_id', $lineIds)
                ->where('status', 'open')
                ->exists();

            if (! $hasOpenDiscrepancies) {
                $allLines = $consignment->items()->get();

                $fullyAccounted = $allLines->every(
                    function ($consignmentLine): bool {
                        $accounted = (string) $consignmentLine->acknowledgementItems()
                            ->selectRaw(
                                'COALESCE(SUM(quantity_good + quantity_damaged + quantity_shortage), 0) as total'
                            )
                            ->value('total');

                        return bccomp(
                            $accounted,
                            (string) $consignmentLine->quantity_dispatched,
                            3
                        ) === 0;
                    }
                );

                $consignment->status = $fullyAccounted
                    ? StockConsignment::STATUS_RECONCILED
                    : StockConsignment::STATUS_PARTIALLY_ACKNOWLEDGED;

                $consignment->save();
            } else {
                $consignment->status = StockConsignment::STATUS_UNDER_REVIEW;
                $consignment->save();
            }

            return $lockedDiscrepancy->fresh([
                'consignmentItem.item',
                'reporter',
                'resolver',
            ]);
        }, 3);
    }
}
