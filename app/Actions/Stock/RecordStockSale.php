<?php

namespace App\Actions\Stock;

use App\Models\Customer;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockSale;
use App\Models\StockSaleItem;
use App\Models\StockSalePayment;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecordStockSale
{
    public function execute(User $actor, Store $store, array $data): StockSale
    {
        return DB::transaction(function () use ($actor, $store, $data): StockSale {
            $lockedActor = User::query()
                ->lockForUpdate()
                ->findOrFail($actor->id);

            if (! $lockedActor->is_active) {
                throw new AuthorizationException('Inactive users cannot record sales.');
            }

            $lockedStore = Store::query()
                ->lockForUpdate()
                ->findOrFail($store->id);

            if (! $lockedStore->is_active) {
                throw new AuthorizationException('Sales cannot be recorded against an inactive store.');
            }

            $businessDay = $lockedStore->dailyCashRecords()
                ->whereDate('business_date', today()->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $businessDay || $businessDay->closed_by !== null) {
                throw ValidationException::withMessages([
                    'business_day' => 'Record opening balances before recording sales. Sales are unavailable after the business day is closed.',
                ]);
            }

            $assigned = StoreStaff::query()
                ->where('user_id', $lockedActor->id)
                ->where('store_id', $lockedStore->id)
                ->whereIn('role', [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ])
                ->whereNull('ends_at')
                ->exists();

            if (! $assigned) {
                throw new AuthorizationException(
                    'Only the assigned store coordinator or assistant can record sales.'
                );
            }

            $validated = Validator::make($data, [
                'customer_id' => ['nullable', 'integer', 'min:1'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.stock_item_id' => ['required', 'integer', 'min:1'],
                'items.*.quantity' => ['required', 'regex:/^\d{1,9}(?:\.\d{1,3})?$/'],
                'items.*.unit_selling_price' => [
                    'nullable',
                    'regex:/^\d{1,13}(?:\.\d{1,2})?$/',
                    'required_without:items.*.line_total',
                ],
                'items.*.line_total' => [
                    'nullable',
                    'regex:/^\d{1,13}(?:\.\d{1,2})?$/',
                    'required_without:items.*.unit_selling_price',
                ],
                'initial_payment_amount' => [
                    'nullable',
                    'regex:/^\d{1,13}(?:\.\d{1,2})?$/',
                ],
                'initial_payment_full' => ['nullable', 'boolean'],
                'initial_payment_method' => ['nullable', 'string', 'max:30'],
                'initial_payment_reference' => ['nullable', 'string', 'max:120'],
                'initial_payment_notes' => ['nullable', 'string'],
            ])->validate();

            $initialPaymentFull = (bool) ($validated['initial_payment_full'] ?? false);
            $grossInitialPayment = (string) ($validated['initial_payment_amount'] ?? '0.00');

            $combined = [];
            foreach ($validated['items'] as $index => $line) {
                $itemId = (int) $line['stock_item_id'];

                if (isset($combined[$itemId])) {
                    throw ValidationException::withMessages([
                        "items.$index.stock_item_id" => 'Select each stock item only once per sale.',
                    ]);
                }

                $quantity = (string) $line['quantity'];

                if (bccomp($quantity, '0', 3) <= 0) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => 'Quantity sold must be greater than zero.',
                    ]);
                }

                $combined[$itemId] = [
                    'quantity' => $quantity,
                    'unit_selling_price' => $line['unit_selling_price'] ?? null,
                    'line_total' => $line['line_total'] ?? null,
                    'index' => $index,
                ];
            }

            $itemIds = array_keys($combined);
            sort($itemIds, SORT_NUMERIC);

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
                ->where('store_id', $lockedStore->id)
                ->whereIn('stock_item_id', $itemIds)
                ->orderBy('stock_item_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('stock_item_id');

            $customer = null;
            if (! empty($validated['customer_id'])) {
                $customer = Customer::query()
                    ->where('store_id', $lockedStore->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->find((int) $validated['customer_id']);

                if (! $customer) {
                    throw ValidationException::withMessages([
                        'customer_id' => 'Select an active customer belonging to this store.',
                    ]);
                }
            }

            $lines = [];
            $totalAmount = '0.00';

            foreach ($combined as $itemId => $line) {
                $item = $items->get($itemId);
                $level = $levels->get($itemId);
                $quantity = $line['quantity'];

                if (! $level || bccomp((string) $level->quantity, $quantity, 3) < 0) {
                    throw ValidationException::withMessages([
                        "items.{$line['index']}.quantity" =>
                            "Insufficient available stock for {$item->description}.",
                    ]);
                }

                $unitPrice = $line['unit_selling_price'];
                $lineTotal = $line['line_total'];

                if ($unitPrice !== null) {
                    $calculatedTotal = bcround(
                        bcmul($quantity, (string) $unitPrice, 8),
                        2
                    );

                    if (
                        $lineTotal !== null
                        && bccomp($calculatedTotal, (string) $lineTotal, 2) !== 0
                    ) {
                        throw ValidationException::withMessages([
                            "items.{$line['index']}.line_total" =>
                                'Line total must agree with quantity multiplied by unit selling price.',
                        ]);
                    }

                    $lineTotal = $calculatedTotal;
                    $unitPrice = bcround((string) $unitPrice, 2);
                } else {
                    $lineTotal = bcround((string) $lineTotal, 2);
                    $unitPrice = bcround(
                        bcdiv($lineTotal, $quantity, 8),
                        2
                    );
                }

                if (bccomp($lineTotal, '0', 2) <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$line['index']}.line_total" => 'Line total must be greater than zero.',
                    ]);
                }

                $unitCost = (string) $level->average_unit_cost;
                $lineCost = bcround(bcmul($quantity, $unitCost, 8), 2);

                $lines[] = [
                    'item' => $item,
                    'level' => $level,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'unit_cost' => $unitCost,
                    'line_cost' => $lineCost,
                ];

                $totalAmount = bcadd($totalAmount, $lineTotal, 2);
            }

            if ($initialPaymentFull) {
                $initialPayment = $totalAmount;
                $initialTip = '0.00';
            } else {
                if (bccomp($grossInitialPayment, '0', 2) < 0) {
                    throw ValidationException::withMessages([
                        'initial_payment_amount' => 'The initial payment cannot be negative.',
                    ]);
                }

                $initialPayment = bccomp($grossInitialPayment, $totalAmount, 2) > 0
                    ? $totalAmount
                    : $grossInitialPayment;

                $initialTip = bccomp($grossInitialPayment, $totalAmount, 2) > 0
                    ? bcsub($grossInitialPayment, $totalAmount, 2)
                    : '0.00';
            }

            if (
                ($initialPaymentFull || bccomp($grossInitialPayment, '0', 2) > 0)
                && empty($validated['initial_payment_method'])
            ) {
                throw ValidationException::withMessages([
                    'initial_payment_method' => 'Select a payment method when recording an initial payment.',
                ]);
            }

            $balance = bcsub($totalAmount, $initialPayment, 2);

            if (bccomp($balance, '0', 2) > 0 && ! $customer) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Select a store customer for an unpaid or part-paid sale.',
                ]);
            }

            $sale = StockSale::query()->create([
                'store_id' => $lockedStore->id,
                'sale_number' => 'SALE-'.Str::upper(Str::random(12)),
                'customer_id' => $customer?->id,
                'customer_name' => $customer?->name,
                'customer_phone' => $customer?->phone,
                'customer_email' => $customer?->email,
                'total_amount' => $totalAmount,
                'sold_at' => now(),
                'created_by' => $lockedActor->id,
            ]);

            foreach ($lines as $line) {
                $item = $line['item'];
                $level = $line['level'];
                $quantity = $line['quantity'];
                $lineCost = $line['line_cost'];

                $remainingQuantity = bcsub((string) $level->quantity, $quantity, 3);
                $remainingValue = bcsub((string) $level->inventory_value, $lineCost, 2);

                if (bccomp($remainingValue, '0', 2) < 0) {
                    throw ValidationException::withMessages([
                        'items' => "Sale would create a negative inventory value for {$item->description}.",
                    ]);
                }

                $level->quantity = $remainingQuantity;
                $level->inventory_value = $remainingValue;
                $level->average_unit_cost = bccomp($remainingQuantity, '0', 3) === 0
                    ? '0.000000'
                    : bcround(bcdiv($remainingValue, $remainingQuantity, 12), 6);
                $level->save();

                $saleItem = StockSaleItem::query()->create([
                    'stock_sale_id' => $sale->id,
                    'stock_item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_selling_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                    'unit_cost_snapshot' => $line['unit_cost'],
                    'line_cost_snapshot' => $lineCost,
                ]);

                StockMovement::query()->create([
                    'stock_item_id' => $item->id,
                    'store_id' => $lockedStore->id,
                    'type' => StockMovement::TYPE_SALE_OUT,
                    'quantity' => $quantity,
                    'unit_cost' => $line['unit_cost'],
                    'movement_value' => $lineCost,
                    'source_type' => StockSaleItem::class,
                    'source_id' => $saleItem->id,
                    'reason' => 'Stock sold under '.$sale->sale_number,
                    'created_by' => $lockedActor->id,
                ]);
            }

            if (
                bccomp($initialPayment, '0', 2) > 0
                || bccomp($initialTip, '0', 2) > 0
            ) {
                StockSalePayment::query()->create([
                    'stock_sale_id' => $sale->id,
                    'amount' => $initialPayment,
                    'tip_amount' => $initialTip,
                    'payment_method' => $validated['initial_payment_method'],
                    'reference' => $validated['initial_payment_reference'] ?? null,
                    'paid_at' => now(),
                    'notes' => $validated['initial_payment_notes'] ?? null,
                    'received_by' => $lockedActor->id,
                ]);
            }

            return $sale->load(['items', 'payments', 'customer']);
        });
    }
}
