<?php

namespace App\Actions\Stock;

use App\Models\StockConsignment;
use App\Models\StockConsignmentAcknowledgement;
use App\Models\StockConsignmentAcknowledgementItem;
use App\Models\StockConsignmentDiscrepancy;
use App\Models\StockConsignmentItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AcknowledgeStockConsignment
{
    public function execute(User $actor, StockConsignment $consignment, array $data): StockConsignmentAcknowledgement
    {
        return DB::transaction(function () use ($actor, $consignment, $data) {
            $lockedActor = User::query()
                ->whereKey($actor->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedActor->is_active) {
                throw ValidationException::withMessages([
                    'consignment' => 'Your account is not active.',
                ]);
            }

            $lockedConsignment = StockConsignment::query()
                ->whereKey($consignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedConsignment->status, [
                StockConsignment::STATUS_DISPATCHED,
                StockConsignment::STATUS_PARTIALLY_ACKNOWLEDGED,
                StockConsignment::STATUS_UNDER_REVIEW,
            ], true)) {
                throw ValidationException::withMessages([
                    'consignment' => 'This consignment is not available for acknowledgement.',
                ]);
            }

            $destination = Store::query()
                ->whereKey($lockedConsignment->destination_store_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $destination->is_active) {
                throw ValidationException::withMessages([
                    'consignment' => 'The destination store is inactive.',
                ]);
            }

            $assignment = StoreStaff::query()
                ->where('user_id', $lockedActor->id)
                ->where('store_id', $destination->id)
                ->whereIn('role', [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ])
                ->whereNull('ends_at')
                ->lockForUpdate()
                ->first();

            if (! $assignment) {
                throw ValidationException::withMessages([
                    'consignment' => 'Only the destination store coordinator or assistant can acknowledge this consignment.',
                ]);
            }

            $validated = Validator::make($data, [
                'items' => ['required', 'array', 'min:1'],
                'items.*.stock_consignment_item_id' => ['required', 'integer', 'distinct'],
                'items.*.quantity_good' => ['required', 'numeric', 'min:0', 'regex:/^\d{1,9}(?:\.\d{1,3})?$/'],
                'items.*.quantity_damaged' => ['required', 'numeric', 'min:0', 'regex:/^\d{1,9}(?:\.\d{1,3})?$/'],
                'items.*.quantity_shortage' => ['required', 'numeric', 'min:0', 'regex:/^\d{1,9}(?:\.\d{1,3})?$/'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ])->validate();

            $submitted = collect($validated['items'])
                ->keyBy(fn (array $line) => (int) $line['stock_consignment_item_id']);

            $lineIds = $submitted->keys()->map(fn ($id) => (int) $id)->sort()->values();

            $consignmentItems = StockConsignmentItem::query()
                ->where('stock_consignment_id', $lockedConsignment->id)
                ->whereIn('id', $lineIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($consignmentItems->count() !== $lineIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected lines do not belong to this consignment.',
                ]);
            }

            $prepared = [];
            $levelIds = [];

            foreach ($submitted as $lineId => $quantities) {
                $line = $consignmentItems->get($lineId);

                $previouslyAccounted = (string) StockConsignmentAcknowledgementItem::query()
                    ->where('stock_consignment_item_id', $line->id)
                    ->selectRaw('COALESCE(SUM(quantity_good + quantity_damaged + quantity_shortage), 0) as total')
                    ->value('total');

                $remaining = bcsub((string) $line->quantity_dispatched, $previouslyAccounted, 3);

                $good = (string) $quantities['quantity_good'];
                $damaged = (string) $quantities['quantity_damaged'];
                $shortage = (string) $quantities['quantity_shortage'];
                $accounted = bcadd(bcadd($good, $damaged, 3), $shortage, 3);

                if (bccomp($accounted, '0', 3) <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Each submitted line must acknowledge at least one unit.',
                    ]);
                }

                if (bccomp($accounted, $remaining, 3) > 0) {
                    throw ValidationException::withMessages([
                        'items' => "Acknowledged quantity exceeds the outstanding quantity for {$line->item->description}.",
                    ]);
                }

                $prepared[$line->id] = [
                    'line' => $line,
                    'good' => $good,
                    'damaged' => $damaged,
                    'shortage' => $shortage,
                ];

                $levelIds[] = $line->stock_item_id;
            }

            sort($levelIds);

            $levels = StockLevel::query()
                ->where('store_id', $destination->id)
                ->whereIn('stock_item_id', array_unique($levelIds))
                ->orderBy('stock_item_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('stock_item_id');

            foreach ($prepared as $entry) {
                $line = $entry['line'];
                $level = $levels->get($line->stock_item_id);

                if (! $level) {
                    throw ValidationException::withMessages([
                        'items' => "The quarantined stock level for {$line->item->description} is missing.",
                    ]);
                }

                $releasedAndMissing = bcadd($entry['good'], $entry['shortage'], 3);

                if (bccomp((string) $level->quantity_quarantined, $releasedAndMissing, 3) < 0) {
                    throw ValidationException::withMessages([
                        'items' => "The quarantine balance for {$line->item->description} is insufficient.",
                    ]);
                }
            }

            $acknowledgement = StockConsignmentAcknowledgement::query()->create([
                'stock_consignment_id' => $lockedConsignment->id,
                'acknowledged_by' => $lockedActor->id,
                'acknowledged_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($prepared as $entry) {
                $line = $entry['line'];
                $good = $entry['good'];
                $damaged = $entry['damaged'];
                $shortage = $entry['shortage'];
                $level = $levels->get($line->stock_item_id);

                $acknowledgementItem = StockConsignmentAcknowledgementItem::query()->create([
                    'acknowledgement_id' => $acknowledgement->id,
                    'stock_consignment_item_id' => $line->id,
                    'quantity_good' => $good,
                    'quantity_damaged' => $damaged,
                    'quantity_shortage' => $shortage,
                ]);

                // Good stock is released into available inventory.
                // Damaged stock remains quarantined pending investigation.
                // Shortage was not physically received, so it leaves quarantine.
                $releasedAndMissing = bcadd($good, $shortage, 3);
                $unitCost = (string) $line->unit_cost;
                $releasedValue = bcmul($good, $unitCost, 2);
                $shortageValue = bcmul($shortage, $unitCost, 2);

                $level->quantity_quarantined = bcsub(
                    (string) $level->quantity_quarantined,
                    $releasedAndMissing,
                    3
                );
                $level->quarantine_value = bcsub(
                    (string) $level->quarantine_value,
                    bcadd($releasedValue, $shortageValue, 2),
                    2
                );
                $level->quantity = bcadd((string) $level->quantity, $good, 3);
                $level->inventory_value = bcadd((string) $level->inventory_value, $releasedValue, 2);

                $totalAvailable = (string) $level->quantity;
                $level->average_unit_cost = bccomp($totalAvailable, '0', 3) === 0
                    ? '0.000000'
                    : bcround(bcdiv((string) $level->inventory_value, $totalAvailable, 12), 6);

                $level->save();

                if (bccomp($good, '0', 3) > 0) {
                    StockMovement::query()->create([
                        'stock_item_id' => $line->stock_item_id,
                        'store_id' => $destination->id,
                        'type' => StockMovement::TYPE_CONSIGNMENT_RELEASE,
                        'quantity' => $good,
                        'unit_cost' => $unitCost,
                        'movement_value' => $releasedValue,
                        'source_type' => StockConsignmentAcknowledgement::class,
                        'source_id' => $acknowledgement->id,
                        'reason' => 'Good stock released from consignment quarantine '.$lockedConsignment->reference,
                        'created_by' => $lockedActor->id,
                    ]);
                }

                if (bccomp($shortage, '0', 3) > 0) {
                    StockMovement::query()->create([
                        'stock_item_id' => $line->stock_item_id,
                        'store_id' => $destination->id,
                        'type' => StockMovement::TYPE_CONSIGNMENT_SHORTAGE,
                        'quantity' => $shortage,
                        'unit_cost' => $unitCost,
                        'movement_value' => $shortageValue,
                        'source_type' => StockConsignmentAcknowledgement::class,
                        'source_id' => $acknowledgement->id,
                        'reason' => 'Shortage reported on consignment '.$lockedConsignment->reference,
                        'created_by' => $lockedActor->id,
                    ]);
                }

                foreach ([
                    'damaged' => $damaged,
                    'shortage' => $shortage,
                ] as $type => $quantity) {
                    if (bccomp($quantity, '0', 3) <= 0) {
                        continue;
                    }

                    StockConsignmentDiscrepancy::query()->create([
                        'stock_consignment_item_id' => $line->id,
                        'acknowledgement_item_id' => $acknowledgementItem->id,
                        'type' => $type,
                        'quantity' => $quantity,
                        'status' => 'open',
                        'reported_by' => $lockedActor->id,
                        'reported_at' => now(),
                    ]);
                }
            }

            $allLines = StockConsignmentItem::query()
                ->where('stock_consignment_id', $lockedConsignment->id)
                ->get();

            $fullyAccounted = $allLines->every(function (StockConsignmentItem $line): bool {
                $accounted = (string) StockConsignmentAcknowledgementItem::query()
                    ->where('stock_consignment_item_id', $line->id)
                    ->selectRaw('COALESCE(SUM(quantity_good + quantity_damaged + quantity_shortage), 0) as total')
                    ->value('total');

                return bccomp($accounted, (string) $line->quantity_dispatched, 3) === 0;
            });

            $hasOpenDiscrepancies = StockConsignmentDiscrepancy::query()
                ->whereIn('stock_consignment_item_id', $allLines->pluck('id'))
                ->where('status', 'open')
                ->exists();

            if ($hasOpenDiscrepancies) {
                $lockedConsignment->status = StockConsignment::STATUS_UNDER_REVIEW;
            } elseif ($fullyAccounted) {
                $lockedConsignment->status = StockConsignment::STATUS_RECONCILED;
            } else {
                $lockedConsignment->status = StockConsignment::STATUS_PARTIALLY_ACKNOWLEDGED;
            }

            $lockedConsignment->save();

            return $acknowledgement->fresh([
                'consignment',
                'acknowledger',
                'items.consignmentItem.item',
                'items.discrepancies',
            ]);
        }, 3);
    }
}
