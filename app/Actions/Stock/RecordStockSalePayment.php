<?php

namespace App\Actions\Stock;

use App\Models\StockSale;
use App\Models\StockSalePayment;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RecordStockSalePayment
{
    public function execute(
        User $actor,
        Store $store,
        StockSale $sale,
        array $data
    ): StockSalePayment {
        return DB::transaction(function () use ($actor, $store, $sale, $data) {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
            $store = Store::query()->lockForUpdate()->findOrFail($store->id);

            if (! $actor->is_active || ! $store->is_active) {
                throw new AuthorizationException();
            }

            $assignment = StoreStaff::query()
                ->where('user_id', $actor->id)
                ->where('store_id', $store->id)
                ->whereNull('ends_at')
                ->whereIn('role', [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ])
                ->lockForUpdate()
                ->first();

            if (! $assignment) {
                throw new AuthorizationException();
            }

            $sale = StockSale::query()
                ->whereKey($sale->id)
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->first();

            if (! $sale) {
                throw new AuthorizationException();
            }

            $validated = Validator::make($data, [
                'amount' => [
                    'required',
                    'regex:/^\d{1,13}(?:\.\d{1,2})?$/',
                ],
                'payment_method' => ['required', 'string', 'max:30'],
                'reference' => ['nullable', 'string', 'max:120'],
                'paid_at' => ['nullable', 'date'],
                'notes' => ['nullable', 'string'],
            ])->validate();

            $grossAmount = (string) $validated['amount'];

            if (bccomp($grossAmount, '0', 2) <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount received must be greater than zero.',
                ]);
            }

            $payments = StockSalePayment::query()
                ->where('stock_sale_id', $sale->id)
                ->lockForUpdate()
                ->get();

            $paid = '0.00';
            foreach ($payments as $existingPayment) {
                $paid = bcadd($paid, (string) $existingPayment->amount, 2);
            }

            $balance = bcsub((string) $sale->total_amount, $paid, 2);

            if (bccomp($balance, '0', 2) < 0) {
                $balance = '0.00';
            }

            $appliedAmount = bccomp($grossAmount, $balance, 2) > 0
                ? $balance
                : $grossAmount;

            $tipAmount = bcsub($grossAmount, $appliedAmount, 2);

            return StockSalePayment::query()->create([
                'stock_sale_id' => $sale->id,
                'amount' => $appliedAmount,
                'tip_amount' => $tipAmount,
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'paid_at' => $validated['paid_at'] ?? now(),
                'notes' => $validated['notes'] ?? null,
                'received_by' => $actor->id,
            ]);
        });
    }
}
