<?php

namespace App\Actions\Stock;

use App\Models\StockConsignment;
use App\Models\StockConsignmentReturn;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReceiveStockConsignmentReturn
{
    public function execute(User $actor, StockConsignmentReturn $stockReturn, array $data): StockConsignmentReturn
    {
        return DB::transaction(function () use ($actor, $stockReturn, $data) {
            $lockedActor = User::query()->lockForUpdate()->findOrFail($actor->id);
            $lockedReturn = StockConsignmentReturn::query()
                ->lockForUpdate()
                ->findOrFail($stockReturn->id);

            $consignment = StockConsignment::query()
                ->lockForUpdate()
                ->findOrFail($lockedReturn->stock_consignment_id);

            if (! $lockedActor->is_active) {
                throw new AuthorizationException('Inactive users cannot receive stock returns.');
            }

            $isCoordinator = StoreStaff::query()
                ->where('user_id', $lockedActor->id)
                ->where('store_id', $consignment->source_store_id)
                ->where('role', StoreStaff::ROLE_COORDINATOR)
                ->whereNull('ends_at')
                ->exists();

            if (! $isCoordinator) {
                throw new AuthorizationException(
                    'Only the source-store coordinator can confirm return receipt.'
                );
            }

            if ($lockedReturn->received_at !== null) {
                throw ValidationException::withMessages([
                    'items' => 'This return has already been received.',
                ]);
            }

            $validated = Validator::make($data, [
                'items' => ['required', 'array', 'min:1'],
                'items.*.stock_consignment_return_item_id' => ['required', 'integer', 'min:1'],
                'items.*.quantity_received' => ['required', 'regex:/^\d{1,9}(?:\.\d{1,3})?$/'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ])->validate();

            $requested = [];

            foreach ($validated['items'] as $row) {
                $id = (int) $row['stock_consignment_return_item_id'];

                if (array_key_exists($id, $requested)) {
                    throw ValidationException::withMessages([
                        'items' => 'A return line may only be submitted once.',
                    ]);
                }

                $requested[$id] = (string) $row['quantity_received'];
            }

            $returnLines = $lockedReturn->items()
                ->with('consignmentItem.item')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($returnLines->count() !== count($requested) ||
                array_diff(array_keys($requested), $returnLines->keys()->all())) {
                throw ValidationException::withMessages([
                    'items' => 'Submit a receipt quantity for every return line.',
                ]);
            }

            $stockItemIds = $returnLines->pluck('consignmentItem.stock_item_id')->unique()->sort()->values();

            $levels = StockLevel::query()
                ->where('store_id', $consignment->source_store_id)
                ->whereIn('stock_item_id', $stockItemIds)
                ->orderBy('stock_item_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('stock_item_id');

            foreach ($requested as $returnLineId => $quantityReceived) {
                $line = $returnLines->get($returnLineId);

                if (bccomp($quantityReceived, (string) $line->quantity_dispatched, 3) > 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Received quantity cannot exceed the quantity dispatched.',
                    ]);
                }
            }

            foreach ($requested as $returnLineId => $quantityReceived) {
                $returnLine = $returnLines->get($returnLineId);
                $consignmentLine = $returnLine->consignmentItem;
                $item = $consignmentLine->item;
                $quantityReceived = bcadd($quantityReceived, '0', 3);

                $returnLine->quantity_received = $quantityReceived;
                $returnLine->save();

                if (bccomp($quantityReceived, '0', 3) === 0) {
                    continue;
                }

                $unitCost = (string) $consignmentLine->unit_cost;
                $movementValue = bcround(bcmul($quantityReceived, $unitCost, 12), 2);
                $level = $levels->get($consignmentLine->stock_item_id);

                if (! $level) {
                    $level = StockLevel::query()->create([
                        'stock_item_id' => $consignmentLine->stock_item_id,
                        'store_id' => $consignment->source_store_id,
                        'quantity' => '0.000',
                        'quantity_quarantined' => '0.000',
                        'average_unit_cost' => '0.000000',
                        'inventory_value' => '0.00',
                        'quarantine_value' => '0.00',
                    ]);
                    $levels->put($consignmentLine->stock_item_id, $level);
                }

                $newQuantity = bcadd((string) $level->quantity, $quantityReceived, 3);
                $newValue = bcadd((string) $level->inventory_value, $movementValue, 2);

                $level->quantity = $newQuantity;
                $level->inventory_value = $newValue;
                $level->average_unit_cost = bccomp($newQuantity, '0', 3) === 0
                    ? '0.000000'
                    : bcround(bcdiv($newValue, $newQuantity, 12), 6);
                $level->save();

                StockMovement::query()->create([
                    'stock_item_id' => $consignmentLine->stock_item_id,
                    'store_id' => $consignment->source_store_id,
                    'type' => StockMovement::TYPE_CONSIGNMENT_RETURN_IN,
                    'quantity' => $quantityReceived,
                    'unit_cost' => $unitCost,
                    'movement_value' => $movementValue,
                    'source_type' => StockConsignmentReturn::class,
                    'source_id' => $lockedReturn->id,
                    'reason' => 'Return received on '.$lockedReturn->reference,
                    'created_by' => $lockedActor->id,
                ]);
            }

            $lockedReturn->received_by = $lockedActor->id;
            $lockedReturn->received_at = now();
            $lockedReturn->receipt_notes = $validated['notes'] ?? null;
            $lockedReturn->save();

            return $lockedReturn->fresh([
                'consignment',
                'dispatcher',
                'receiver',
                'items.consignmentItem.item',
            ]);
        }, 3);
    }
}
