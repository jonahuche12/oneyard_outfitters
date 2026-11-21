<?php

namespace App\Actions\Stock;

use App\Models\StockConsignment;
use App\Models\StockConsignmentReturn;
use App\Models\StockConsignmentReturnItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DispatchStockConsignmentReturn
{
    public function execute(User $actor, StockConsignment $consignment, array $data): StockConsignmentReturn
    {
        return DB::transaction(function () use ($actor, $consignment, $data) {
            $lockedActor = User::query()->lockForUpdate()->findOrFail($actor->id);
            $lockedConsignment = StockConsignment::query()
                ->lockForUpdate()
                ->findOrFail($consignment->id);

            if (! $lockedActor->is_active) {
                throw new AuthorizationException('Inactive users cannot dispatch stock returns.');
            }

            $assignment = StoreStaff::query()
                ->where('user_id', $lockedActor->id)
                ->where('store_id', $lockedConsignment->destination_store_id)
                ->whereIn('role', [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ])
                ->whereNull('ends_at')
                ->exists();

            if (! $assignment) {
                throw new AuthorizationException(
                    'Only assigned destination-store staff can dispatch a return.'
                );
            }

            if (! $lockedConsignment->dispatched_at ||
                $lockedConsignment->status === StockConsignment::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'items' => 'This consignment cannot receive a stock return.',
                ]);
            }

            $validated = Validator::make($data, [
                'items' => ['required', 'array', 'min:1'],
                'items.*.stock_consignment_item_id' => ['required', 'integer', 'min:1'],
                'items.*.quantity' => ['required', 'regex:/^\d{1,9}(?:\.\d{1,3})?$/'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ])->validate();

            $requested = [];

            foreach ($validated['items'] as $row) {
                $quantity = (string) $row['quantity'];

                if (bccomp($quantity, '0', 3) <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Every return quantity must be greater than zero.',
                    ]);
                }

                $lineId = (int) $row['stock_consignment_item_id'];
                $requested[$lineId] = bcadd($requested[$lineId] ?? '0.000', $quantity, 3);
            }

            ksort($requested);

            $lines = $lockedConsignment->items()
                ->whereIn('id', array_keys($requested))
                ->with('item')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lines->count() !== count($requested)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected lines do not belong to this consignment.',
                ]);
            }

            $returnable = DB::table('stock_consignment_acknowledgement_items as ai')
                ->join('stock_consignment_acknowledgements as a', 'a.id', '=', 'ai.acknowledgement_id')
                ->where('a.stock_consignment_id', $lockedConsignment->id)
                ->whereIn('ai.stock_consignment_item_id', array_keys($requested))
                ->groupBy('ai.stock_consignment_item_id')
                ->selectRaw('ai.stock_consignment_item_id, SUM(ai.quantity_good) as quantity_good')
                ->pluck('quantity_good', 'stock_consignment_item_id');

            $alreadyReturned = DB::table('stock_consignment_return_items as ri')
                ->join('stock_consignment_returns as r', 'r.id', '=', 'ri.stock_consignment_return_id')
                ->where('r.stock_consignment_id', $lockedConsignment->id)
                ->whereIn('ri.stock_consignment_item_id', array_keys($requested))
                ->groupBy('ri.stock_consignment_item_id')
                ->selectRaw('ri.stock_consignment_item_id, SUM(ri.quantity_dispatched) as quantity_returned')
                ->pluck('quantity_returned', 'stock_consignment_item_id');

            $levels = StockLevel::query()
                ->where('store_id', $lockedConsignment->destination_store_id)
                ->whereIn('stock_item_id', $lines->pluck('stock_item_id'))
                ->orderBy('stock_item_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('stock_item_id');

            foreach ($requested as $lineId => $quantity) {
                $line = $lines->get($lineId);

                $good = (string) ($returnable[$lineId] ?? '0.000');
                $returned = (string) ($alreadyReturned[$lineId] ?? '0.000');
                $remainingGood = bcsub($good, $returned, 3);

                if (bccomp($quantity, $remainingGood, 3) > 0) {
                    throw ValidationException::withMessages([
                        'items' => "Return quantity exceeds acknowledged-good stock for {$line->item->description}.",
                    ]);
                }

                $level = $levels->get($line->stock_item_id);

                if (! $level || bccomp((string) $level->quantity, $quantity, 3) < 0) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient available stock to return {$line->item->description}.",
                    ]);
                }
            }

            $stockReturn = StockConsignmentReturn::query()->create([
                'stock_consignment_id' => $lockedConsignment->id,
                'reference' => 'SR-'.Str::upper(Str::random(12)),
                'dispatched_by' => $lockedActor->id,
                'dispatched_at' => now(),
                'dispatch_notes' => $validated['notes'] ?? null,
            ]);

            foreach ($requested as $lineId => $quantity) {
                $line = $lines->get($lineId);
                $level = $levels->get($line->stock_item_id);

                $unitCost = (string) $level->average_unit_cost;
                $movementValue = bcround(bcmul($quantity, $unitCost, 12), 2);
                $remainingQuantity = bcsub((string) $level->quantity, $quantity, 3);
                $remainingValue = bcsub((string) $level->inventory_value, $movementValue, 2);

                if (bccomp($remainingValue, '0', 2) < 0) {
                    throw ValidationException::withMessages([
                        'items' => "Return would create a negative inventory value for {$line->item->description}.",
                    ]);
                }

                $level->quantity = $remainingQuantity;
                $level->inventory_value = $remainingValue;
                $level->average_unit_cost = bccomp($remainingQuantity, '0', 3) === 0
                    ? '0.000000'
                    : bcround(bcdiv($remainingValue, $remainingQuantity, 12), 6);
                $level->save();

                StockConsignmentReturnItem::query()->create([
                    'stock_consignment_return_id' => $stockReturn->id,
                    'stock_consignment_item_id' => $line->id,
                    'quantity_dispatched' => $quantity,
                ]);

                StockMovement::query()->create([
                    'stock_item_id' => $line->stock_item_id,
                    'store_id' => $lockedConsignment->destination_store_id,
                    'type' => StockMovement::TYPE_CONSIGNMENT_RETURN_OUT,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'movement_value' => $movementValue,
                    'source_type' => StockConsignmentReturn::class,
                    'source_id' => $stockReturn->id,
                    'reason' => 'Return dispatched on '.$stockReturn->reference,
                    'created_by' => $lockedActor->id,
                ]);
            }

            return $stockReturn->fresh(['consignment', 'dispatcher', 'items.consignmentItem.item']);
        }, 3);
    }
}
