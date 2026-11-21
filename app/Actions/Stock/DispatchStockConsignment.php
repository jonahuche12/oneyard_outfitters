<?php

namespace App\Actions\Stock;

use App\Models\StockConsignment;
use App\Models\StockConsignmentItem;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DispatchStockConsignment
{
    public function execute(User $actor, Store $source, Store $destination, array $data): StockConsignment
    {
        return DB::transaction(function () use ($actor, $source, $destination, $data): StockConsignment {
            $lockedActor = User::query()->lockForUpdate()->findOrFail($actor->id);

            if (! $lockedActor->is_active) {
                throw new AuthorizationException('Inactive users cannot dispatch stock.');
            }

            $stores = Store::query()
                ->whereIn('id', [$source->id, $destination->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lockedSource = $stores->get($source->id);
            $lockedDestination = $stores->get($destination->id);

            if (! $lockedSource || ! $lockedDestination) {
                throw ValidationException::withMessages([
                    'destination_store_id' => 'The selected source or destination store does not exist.',
                ]);
            }

            if (! $lockedSource->is_active || ! $lockedDestination->is_active) {
                throw ValidationException::withMessages([
                    'destination_store_id' => 'Both stores must be active to dispatch a consignment.',
                ]);
            }

            if ($lockedSource->is($lockedDestination)) {
                throw ValidationException::withMessages([
                    'destination_store_id' => 'The destination must be different from the source store.',
                ]);
            }

            $isCoordinator = StoreStaff::query()
                ->where('user_id', $lockedActor->id)
                ->where('store_id', $lockedSource->id)
                ->whereIn('role', [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ])
                ->whereNull('ends_at')
                ->exists();

            if (! $isCoordinator) {
                throw new AuthorizationException(
                    'Only the source store coordinator or assistant can dispatch stock.'
                );
            }

            $validated = Validator::make($data, [
                'items' => ['required', 'array', 'min:1'],
                'items.*.stock_item_id' => ['required', 'integer', 'min:1'],
                'items.*.quantity' => ['required', 'regex:/^\d{1,9}(?:\.\d{1,3})?$/'],
                'notes' => ['nullable', 'string'],
            ])->validate();

            $combined = [];

            foreach ($validated['items'] as $line) {
                $quantity = (string) $line['quantity'];

                if (bccomp($quantity, '0', 3) <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Every dispatched quantity must be greater than zero.',
                    ]);
                }

                $id = (int) $line['stock_item_id'];
                $combined[$id] = bcadd($combined[$id] ?? '0.000', $quantity, 3);
            }

            ksort($combined);
            $itemIds = array_keys($combined);

            $items = StockItem::query()
                ->whereIn('id', $itemIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($items->count() !== count($itemIds)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected stock items do not exist.',
                ]);
            }

            $levels = StockLevel::query()
                ->where('store_id', $lockedSource->id)
                ->whereIn('stock_item_id', $itemIds)
                ->orderBy('stock_item_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('stock_item_id');

            $lines = [];
            $totalCost = '0.00';
            $expectedSales = '0.00';

            foreach ($combined as $itemId => $quantity) {
                $item = $items->get($itemId);
                $level = $levels->get($itemId);

                if (! $level || bccomp((string) $level->quantity, $quantity, 3) < 0) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient available stock for {$item->description}.",
                    ]);
                }

                $unitCost = (string) $level->average_unit_cost;
                $lineCost = bcround(bcmul($quantity, $unitCost, 12), 2);
                $marketPrice = $item->current_market_price ?? $item->suggested_price;
                $salesValue = $marketPrice === null
                    ? '0.00'
                    : bcround(bcmul($quantity, (string) $marketPrice, 8), 2);

                $lines[] = [
                    'item' => $item,
                    'level' => $level,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_cost' => $lineCost,
                    'market_price' => $marketPrice,
                    'sales_value' => $salesValue,
                    'gross_return' => bcsub($salesValue, $lineCost, 2),
                ];

                $totalCost = bcadd($totalCost, $lineCost, 2);
                $expectedSales = bcadd($expectedSales, $salesValue, 2);
            }

            $consignment = StockConsignment::query()->create([
                'reference' => 'SC-'.Str::upper(Str::random(12)),
                'source_store_id' => $lockedSource->id,
                'destination_store_id' => $lockedDestination->id,
                'status' => StockConsignment::STATUS_DISPATCHED,
                'dispatched_at' => now(),
                'dispatched_by' => $lockedActor->id,
                'total_cost' => $totalCost,
                'expected_sales_value' => $expectedSales,
                'expected_gross_return' => bcsub($expectedSales, $totalCost, 2),
                'notes' => $validated['notes'] ?? null,
                'created_by' => $lockedActor->id,
            ]);

            foreach ($lines as $line) {
                $item = $line['item'];
                $sourceLevel = $line['level'];
                $quantity = $line['quantity'];
                $lineCost = $line['line_cost'];

                $remainingQuantity = bcsub((string) $sourceLevel->quantity, $quantity, 3);
                $remainingValue = bcsub((string) $sourceLevel->inventory_value, $lineCost, 2);

                if (bccomp($remainingValue, '0', 2) < 0) {
                    throw ValidationException::withMessages([
                        'items' => "Dispatch would create a negative inventory value for {$item->description}.",
                    ]);
                }

                $sourceLevel->quantity = $remainingQuantity;
                $sourceLevel->inventory_value = $remainingValue;
                $sourceLevel->average_unit_cost = bccomp($remainingQuantity, '0', 3) === 0
                    ? '0.000000'
                    : bcround(bcdiv($remainingValue, $remainingQuantity, 12), 6);
                $sourceLevel->save();

                $destinationLevel = StockLevel::query()
                    ->where('store_id', $lockedDestination->id)
                    ->where('stock_item_id', $item->id)
                    ->lockForUpdate()
                    ->first();

                if (! $destinationLevel) {
                    $destinationLevel = StockLevel::query()->create([
                        'stock_item_id' => $item->id,
                        'store_id' => $lockedDestination->id,
                        'quantity' => '0.000',
                        'quantity_quarantined' => '0.000',
                        'average_unit_cost' => '0.000000',
                        'inventory_value' => '0.00',
                        'quarantine_value' => '0.00',
                    ]);
                }

                $destinationLevel->quantity_quarantined = bcadd(
                    (string) $destinationLevel->quantity_quarantined,
                    $quantity,
                    3
                );
                $destinationLevel->quarantine_value = bcadd(
                    (string) $destinationLevel->quarantine_value,
                    $lineCost,
                    2
                );
                $destinationLevel->save();

                StockConsignmentItem::query()->create([
                    'stock_consignment_id' => $consignment->id,
                    'stock_item_id' => $item->id,
                    'quantity_dispatched' => $quantity,
                    'unit_cost' => $line['unit_cost'],
                    'line_cost' => $lineCost,
                    'market_price' => $line['market_price'],
                    'expected_sales_value' => $line['sales_value'],
                    'expected_gross_return' => $line['gross_return'],
                ]);

                StockMovement::query()->create([
                    'stock_item_id' => $item->id,
                    'store_id' => $lockedSource->id,
                    'type' => StockMovement::TYPE_CONSIGNMENT_OUT,
                    'quantity' => $quantity,
                    'unit_cost' => $line['unit_cost'],
                    'movement_value' => $lineCost,
                    'source_type' => StockConsignment::class,
                    'source_id' => $consignment->id,
                    'reason' => 'Dispatched on '.$consignment->reference,
                    'created_by' => $lockedActor->id,
                ]);

                StockMovement::query()->create([
                    'stock_item_id' => $item->id,
                    'store_id' => $lockedDestination->id,
                    'type' => StockMovement::TYPE_CONSIGNMENT_IN,
                    'quantity' => $quantity,
                    'unit_cost' => $line['unit_cost'],
                    'movement_value' => $lineCost,
                    'source_type' => StockConsignment::class,
                    'source_id' => $consignment->id,
                    'reason' => 'Received into quarantine from '.$consignment->reference,
                    'created_by' => $lockedActor->id,
                ]);
            }

            return $consignment->fresh([
                'sourceStore',
                'destinationStore',
                'creator',
                'dispatcher',
                'items.item',
            ]);
        }, 3);
    }
}
