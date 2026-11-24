<?php

namespace App\Actions\Stock;

use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AddStockQuantity
{
    public function execute(User $actor, StockItem $stockItem, array $input): StockMovement
    {
        $validated = Validator::make($input, [
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:999999999'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'decimal:0,6', 'max:999999999'],
            'reason' => ['required', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($actor, $stockItem, $validated) {
            $assignment = $actor->activeStoreAssignment;

            if (
                ! $assignment
                || $assignment->role !== StoreStaff::ROLE_COORDINATOR
                || ! $assignment->store?->is_active
            ) {
                abort(403);
            }

            $item = StockItem::query()
                ->whereKey($stockItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $item->store_id !== (int) $assignment->store_id) {
                abort(403);
            }

            $level = StockLevel::query()
                ->where('stock_item_id', $item->id)
                ->where('store_id', $assignment->store_id)
                ->lockForUpdate()
                ->first();

            if (! $level) {
                throw ValidationException::withMessages([
                    'quantity' => 'This item has no existing stock balance at this store.',
                ]);
            }

            $quantity = bcadd((string) $validated['quantity'], '0', 3);
            $unitCost = bcadd((string) $validated['unit_cost'], '0', 6);

            $movementValue = bcround(
                bcmul($quantity, $unitCost, 9),
                2
            );

            $newQuantity = bcadd(
                (string) $level->quantity,
                $quantity,
                3
            );

            $newValue = bcadd(
                (string) $level->inventory_value,
                $movementValue,
                2
            );

            if (bccomp($newQuantity, '0', 3) <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'The resulting stock quantity must be greater than zero.',
                ]);
            }

            $newAverage = bcround(
                bcdiv($newValue, $newQuantity, 12),
                6
            );

            $level->quantity = $newQuantity;
            $level->inventory_value = $newValue;
            $level->average_unit_cost = $newAverage;
            $level->save();

            return StockMovement::query()->create([
                'stock_item_id' => $item->id,
                'store_id' => $assignment->store_id,
                'type' => StockMovement::TYPE_MANUAL_ADDITION,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'movement_value' => $movementValue,
                'source_type' => null,
                'source_id' => null,
                'reason' => $validated['reason'],
                'created_by' => $actor->id,
            ]);
        });
    }
}
