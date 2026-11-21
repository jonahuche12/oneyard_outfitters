<?php

namespace App\Actions\Stock;

use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockProcessing;
use App\Models\StockProcessingOutput;
use App\Models\StockProcessingStatusEvent;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RecordStockProcessing
{
    /**
     * Record one bale-processing session and its stock ledger entries.
     *
     * Fabric output lines:
     * [
     *   ['stock_item_id' => 10, 'quantity_produced' => '20.000', 'line_cost' => '1500.00'],
     * ]
     *
     * Recoverable waste, when present:
     * [
     *   'stock_item_id' => 20,
     *   'line_cost' => '100.00',
     * ]
     *
     * The recoverable-waste quantity is taken from recoverable_waste_kg.
     */
    public function execute(User $actor, Store $store, array $data): StockProcessing
    {
        return DB::transaction(function () use ($actor, $store, $data): StockProcessing {
            // Consistent lock order: actor -> store -> stock items -> stock levels.
            $lockedActor = User::query()
                ->lockForUpdate()
                ->findOrFail($actor->id);

            if (! $lockedActor->is_active) {
                throw new AuthorizationException(
                    'Inactive users cannot record stock processing.'
                );
            }

            $lockedStore = Store::query()
                ->lockForUpdate()
                ->findOrFail($store->id);

            if (! $lockedStore->is_active) {
                throw new AuthorizationException(
                    'Stock processing cannot be recorded against an inactive store.'
                );
            }

            $isCoordinator = StoreStaff::query()
                ->where('user_id', $lockedActor->id)
                ->where('store_id', $lockedStore->id)
                ->whereIn('role', [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ])
                ->whereNull('ends_at')
                ->exists();

            if (! $isCoordinator) {
                throw new AuthorizationException(
                    'Only the assigned store coordinator or assistant can record stock processing.'
                );
            }

            $validated = $this->validatePayload($data);

            $outputLines = $validated['outputs'];
            $recoverableWaste = $validated['recoverable_waste'] ?? null;

            $itemIds = [(int) $validated['raw_stock_item_id']];

            foreach ($outputLines as $line) {
                $itemIds[] = (int) $line['stock_item_id'];
            }

            if ($recoverableWaste !== null) {
                $itemIds[] = (int) $recoverableWaste['stock_item_id'];
            }

            $itemIds = array_values(array_unique($itemIds));
            sort($itemIds, SORT_NUMERIC);

            $items = StockItem::query()
                ->whereIn('id', $itemIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($items->count() !== count($itemIds)) {
                throw ValidationException::withMessages([
                    'stock_item_id' => 'One or more selected stock items do not exist.',
                ]);
            }

            $rawItem = $items->get((int) $validated['raw_stock_item_id']);

            if ((int) $rawItem->store_id !== (int) $lockedStore->id) {
                throw ValidationException::withMessages([
                    'raw_stock_item_id' => 'The raw stock item belongs to another store.',
                ]);
            }


            if (! $rawItem->is_raw || $rawItem->unit !== StockItem::UNIT_KG) {
                throw ValidationException::withMessages([
                    'raw_stock_item_id' => 'Processing requires a raw stock item measured in kilograms.',
                ]);
            }

            if (
                $rawItem->processing_status === StockItem::PROCESSING_PROCESSED
                || ! in_array(
                    $rawItem->processing_status,
                    [
                        StockItem::PROCESSING_PENDING,
                        StockItem::PROCESSING_PROCESSING,
                    ],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'raw_stock_item_id' => 'This raw item is not open for processing. Reopen it through the audited status workflow first.',
                ]);
            }

            foreach ($outputLines as $line) {
                $item = $items->get((int) $line['stock_item_id']);

                if ((int) $item->store_id !== (int) $lockedStore->id) {
                    throw ValidationException::withMessages([
                        'outputs' => sprintf(
                            'Output stock item #%d belongs to another store.',
                            $item->id
                        ),
                    ]);
                }


                if (
                    $item->is_raw
                    || $item->unit !== StockItem::UNIT_YARD
                ) {
                    throw ValidationException::withMessages([
                        'outputs' => sprintf(
                            'Output item #%d must be a non-raw fabric item measured in yards.',
                            $item->id
                        ),
                    ]);
                }
            }

            if ($recoverableWaste !== null) {
                $wasteItem = $items->get((int) $recoverableWaste['stock_item_id']);

                if ((int) $wasteItem->store_id !== (int) $lockedStore->id) {
                    throw ValidationException::withMessages([
                        'recoverable_waste' => 'The reusable-waste stock item belongs to another store.',
                    ]);
                }


                if (
                    $wasteItem->is_raw
                    || $wasteItem->unit !== StockItem::UNIT_KG
                    || $wasteItem->category !== 'reusable-waste'
                ) {
                    throw ValidationException::withMessages([
                        'recoverable_waste' => 'Recoverable waste must use a non-raw reusable-waste stock item measured in kilograms.',
                    ]);
                }
            }

            $quantityConsumed = (string) $validated['quantity_consumed'];
            $recoverableKg = (string) $validated['recoverable_waste_kg'];
            $discardedKg = (string) $validated['discarded_waste_kg'];

            $totalWaste = bcadd($recoverableKg, $discardedKg, 3);

            if (bccomp($totalWaste, $quantityConsumed, 3) > 0) {
                throw ValidationException::withMessages([
                    'recoverable_waste_kg' => 'Recoverable and discarded waste cannot exceed the weight consumed.',
                ]);
            }

            if (bccomp($recoverableKg, '0', 3) > 0 && $recoverableWaste === null) {
                throw ValidationException::withMessages([
                    'recoverable_waste' => 'Select an approved reusable-waste stock item when recoverable waste is recorded.',
                ]);
            }

            if (bccomp($recoverableKg, '0', 3) === 0 && $recoverableWaste !== null) {
                throw ValidationException::withMessages([
                    'recoverable_waste' => 'A reusable-waste output is only allowed when recoverable waste is greater than zero.',
                ]);
            }

            $allOutputs = $this->buildOutputLines(
                $outputLines,
                $recoverableWaste,
                $recoverableKg
            );

            $allocatedCost = '0.00';

            foreach ($allOutputs as $line) {
                $allocatedCost = bcadd(
                    $allocatedCost,
                    $line['line_cost'],
                    2
                );
            }

            $levelIds = $itemIds;

            $levels = StockLevel::query()
                ->where('store_id', $lockedStore->id)
                ->whereIn('stock_item_id', $levelIds)
                ->orderBy('stock_item_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('stock_item_id');

            $rawLevel = $levels->get($rawItem->id);

            if (! $rawLevel) {
                throw ValidationException::withMessages([
                    'raw_stock_item_id' => 'The raw item has no stock balance in this store.',
                ]);
            }

            if (bccomp((string) $rawLevel->quantity, $quantityConsumed, 3) < 0) {
                throw ValidationException::withMessages([
                    'quantity_consumed' => 'The store does not have enough available raw material for this processing session.',
                ]);
            }

            $currentAverage = (string) $rawLevel->average_unit_cost;

            if (bccomp($currentAverage, '0', 6) < 0) {
                throw ValidationException::withMessages([
                    'raw_stock_item_id' => 'The raw stock balance has an invalid average cost.',
                ]);
            }

            $remainingQuantity = bcsub(
                (string) $rawLevel->quantity,
                $quantityConsumed,
                3
            );

            // When consuming the entire available balance, use its exact
            // recorded value to avoid leaving a rounding residue.
            $consumedCost = bccomp($remainingQuantity, '0', 3) === 0
                ? (string) $rawLevel->inventory_value
                : bcround(
                    bcmul($quantityConsumed, $currentAverage, 12),
                    2
                );

            if (bccomp((string) $rawLevel->inventory_value, $consumedCost, 2) < 0) {
                throw ValidationException::withMessages([
                    'quantity_consumed' => 'The raw stock valuation is inconsistent with its available inventory value.',
                ]);
            }

            $remainingValue = bcsub(
                (string) $rawLevel->inventory_value,
                $consumedCost,
                2
            );

            if (bccomp($remainingValue, '0', 2) < 0) {
                throw ValidationException::withMessages([
                    'quantity_consumed' => 'Processing would create a negative raw inventory value.',
                ]);
            }

            $newAverage = bccomp($remainingQuantity, '0', 3) === 0
                ? '0.000000'
                : bcround(
                    bcdiv($remainingValue, $remainingQuantity, 12),
                    6
                );

            $processing = StockProcessing::query()->create([
                'store_id' => $lockedStore->id,
                'raw_stock_item_id' => $rawItem->id,
                'quantity_consumed' => $quantityConsumed,
                'consumed_cost' => $consumedCost,
                'allocated_cost' => $allocatedCost,
                'cost_difference' => bcsub($consumedCost, $allocatedCost, 2),
                'recoverable_waste_kg' => $recoverableKg,
                'discarded_waste_kg' => $discardedKg,
                'processed_on' => $validated['processed_on'],
                'notes' => $validated['notes'] ?? null,
                'processed_by' => $lockedActor->id,
            ]);

            // Deduct the consumed raw weight exactly once.
            $rawLevel->quantity = $remainingQuantity;
            $rawLevel->inventory_value = $remainingValue;
            $rawLevel->average_unit_cost = $newAverage;
            $rawLevel->save();

            StockMovement::query()->create([
                'stock_item_id' => $rawItem->id,
                'store_id' => $lockedStore->id,
                'type' => StockMovement::TYPE_PROCESSING_OUT,
                'quantity' => $quantityConsumed,
                'unit_cost' => $currentAverage,
                'movement_value' => $consumedCost,
                'source_type' => StockProcessing::class,
                'source_id' => $processing->id,
                'reason' => 'Raw material consumed in '.$processing->number,
                'created_by' => $lockedActor->id,
            ]);

            foreach ($allOutputs as $line) {
                $item = $items->get((int) $line['stock_item_id']);

                StockProcessingOutput::query()->create([
                    'stock_processing_id' => $processing->id,
                    'stock_item_id' => $item->id,
                    'quantity_produced' => $line['quantity_produced'],
                    'line_cost' => $line['line_cost'],
                ]);

                $this->postOutput(
                    $lockedStore,
                    $lockedActor,
                    $processing,
                    $item,
                    $line['quantity_produced'],
                    $line['line_cost']
                );
            }

            if ($rawItem->processing_status === StockItem::PROCESSING_PENDING) {
                $previousStatus = $rawItem->processing_status;

                $rawItem->processing_status = StockItem::PROCESSING_PROCESSING;
                $rawItem->processing_completed_at = null;
                $rawItem->save();

                StockProcessingStatusEvent::query()->create([
                    'stock_item_id' => $rawItem->id,
                    'event_type' => 'session_started',
                    'previous_status' => $previousStatus,
                    'new_status' => StockItem::PROCESSING_PROCESSING,
                    'reason' => 'Processing session '.$processing->number.' recorded.',
                    'recorded_by' => $lockedActor->id,
                    'recorded_at' => now(),
                ]);
            }

            return $processing->fresh([
                'store',
                'rawItem',
                'processor',
                'outputs.item',
            ]);
        }, 3);
    }

    private function validatePayload(array $data): array
    {
        $validated = Validator::make($data, [
            'raw_stock_item_id' => ['required', 'integer', 'min:1'],
            'quantity_consumed' => [
                'required',
                'regex:/^\d{1,9}(?:\.\d{1,3})?$/',
            ],
            'recoverable_waste_kg' => [
                'nullable',
                'regex:/^\d{1,9}(?:\.\d{1,3})?$/',
            ],
            'discarded_waste_kg' => [
                'nullable',
                'regex:/^\d{1,9}(?:\.\d{1,3})?$/',
            ],
            'processed_on' => ['required', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string'],
            'outputs' => ['required', 'array', 'min:1'],
            'outputs.*.stock_item_id' => ['required', 'integer', 'min:1'],
            'outputs.*.quantity_produced' => [
                'required',
                'regex:/^\d{1,9}(?:\.\d{1,3})?$/',
            ],
            'outputs.*.line_cost' => [
                'required',
                'regex:/^\d{1,13}(?:\.\d{1,2})?$/',
            ],
            'recoverable_waste' => ['nullable', 'array'],
            'recoverable_waste.stock_item_id' => [
                'required_with:recoverable_waste',
                'integer',
                'min:1',
            ],
            'recoverable_waste.line_cost' => [
                'required_with:recoverable_waste',
                'regex:/^\d{1,13}(?:\.\d{1,2})?$/',
            ],
        ])->validate();

        // Laravel omits absent nullable fields from validated data.
        // Normalize this optional object so later checks are deterministic.
        $validated['recoverable_waste'] =
            $validated['recoverable_waste'] ?? null;

        $validated['recoverable_waste_kg'] =
            (string) ($validated['recoverable_waste_kg'] ?? '0.000');

        $validated['discarded_waste_kg'] =
            (string) ($validated['discarded_waste_kg'] ?? '0.000');

        if (bccomp((string) $validated['quantity_consumed'], '0', 3) <= 0) {
            throw ValidationException::withMessages([
                'quantity_consumed' => 'The quantity consumed must be greater than zero.',
            ]);
        }

        foreach (['recoverable_waste_kg', 'discarded_waste_kg'] as $field) {
            if (bccomp($validated[$field], '0', 3) < 0) {
                throw ValidationException::withMessages([
                    $field => 'Waste quantities cannot be negative.',
                ]);
            }
        }

        $combined = [];

        foreach ($validated['outputs'] as $line) {
            $quantity = (string) $line['quantity_produced'];
            $lineCost = bcround((string) $line['line_cost'], 2);

            if (bccomp($quantity, '0', 3) <= 0) {
                throw ValidationException::withMessages([
                    'outputs' => 'Every finished fabric output quantity must be greater than zero.',
                ]);
            }

            $itemId = (int) $line['stock_item_id'];

            if (! isset($combined[$itemId])) {
                $combined[$itemId] = [
                    'stock_item_id' => $itemId,
                    'quantity_produced' => '0.000',
                    'line_cost' => '0.00',
                ];
            }

            $combined[$itemId]['quantity_produced'] = bcadd(
                $combined[$itemId]['quantity_produced'],
                $quantity,
                3
            );

            $combined[$itemId]['line_cost'] = bcadd(
                $combined[$itemId]['line_cost'],
                $lineCost,
                2
            );
        }

        ksort($combined);

        $validated['outputs'] = array_values($combined);

        if (
            $validated['recoverable_waste'] !== null
            && bccomp((string) $validated['recoverable_waste']['line_cost'], '0', 2) < 0
        ) {
            throw ValidationException::withMessages([
                'recoverable_waste' => 'Recoverable waste allocation cannot be negative.',
            ]);
        }

        foreach ($validated['outputs'] as $line) {
            if (bccomp($line['line_cost'], '0', 2) < 0) {
                throw ValidationException::withMessages([
                    'outputs' => 'Output allocation costs cannot be negative.',
                ]);
            }
        }

        return $validated;
    }

    private function buildOutputLines(
        array $fabricLines,
        ?array $recoverableWaste,
        string $recoverableKg
    ): array {
        $lines = $fabricLines;

        if (
            $recoverableWaste !== null
            && bccomp($recoverableKg, '0', 3) > 0
        ) {
            $lines[] = [
                'stock_item_id' => (int) $recoverableWaste['stock_item_id'],
                'quantity_produced' => $recoverableKg,
                'line_cost' => bcround(
                    (string) $recoverableWaste['line_cost'],
                    2
                ),
            ];
        }

        return $lines;
    }

    private function postOutput(
        Store $store,
        User $actor,
        StockProcessing $processing,
        StockItem $item,
        string $quantity,
        string $lineCost
    ): void {
        $level = StockLevel::query()
            ->where('store_id', $store->id)
            ->where('stock_item_id', $item->id)
            ->lockForUpdate()
            ->first();

        if (! $level) {
            $level = StockLevel::query()->create([
                'store_id' => $store->id,
                'stock_item_id' => $item->id,
                'quantity' => '0.000',
                'quantity_quarantined' => '0.000',
                'average_unit_cost' => '0.000000',
                'inventory_value' => '0.00',
                'quarantine_value' => '0.00',
            ]);
        }

        $newQuantity = bcadd(
            (string) $level->quantity,
            $quantity,
            3
        );

        $newValue = bcadd(
            (string) $level->inventory_value,
            $lineCost,
            2
        );

        $newAverage = bcround(
            bcdiv($newValue, $newQuantity, 12),
            6
        );

        $unitCost = bcround(
            bcdiv($lineCost, $quantity, 12),
            6
        );

        $level->quantity = $newQuantity;
        $level->inventory_value = $newValue;
        $level->average_unit_cost = $newAverage;
        $level->save();

        StockMovement::query()->create([
            'stock_item_id' => $item->id,
            'store_id' => $store->id,
            'type' => StockMovement::TYPE_PROCESSING_IN,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'movement_value' => $lineCost,
            'source_type' => StockProcessing::class,
            'source_id' => $processing->id,
            'reason' => 'Output from '.$processing->number,
            'created_by' => $actor->id,
        ]);
    }
}
