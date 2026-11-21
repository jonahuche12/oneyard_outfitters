<?php

namespace App\Actions\Stores;

use App\Models\Store;
use App\Models\StoreExpense;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecordStoreExpense
{
    /**
     * @return array{
     *     expense: StoreExpense,
     *     qualifying_total: string,
     *     threshold_exceeded: bool
     * }
     */
    public function execute(User $actor, Store $store, array $data): array
    {
        return DB::transaction(function () use ($actor, $store, $data): array {
            // Keep the same lock order as stock sales and daily cash closing.
            $lockedActor = User::query()
                ->lockForUpdate()
                ->findOrFail($actor->id);

            if (! $lockedActor->is_active) {
                throw new AuthorizationException(
                    'Inactive users cannot record store expenses.'
                );
            }

            $lockedStore = Store::query()
                ->lockForUpdate()
                ->findOrFail($store->id);

            if (! $lockedStore->is_active) {
                throw new AuthorizationException(
                    'Expenses cannot be recorded against an inactive store.'
                );
            }

            $businessDay = $lockedStore->dailyCashRecords()
                ->whereDate('business_date', today()->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $businessDay || $businessDay->closed_by !== null) {
                throw ValidationException::withMessages([
                    'business_day' => 'Record opening balances before recording expenses. Expenses cannot be recorded after the business day is closed.',
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
                    'Only the assigned store coordinator or assistant can record expenses.'
                );
            }

            $validated = Validator::make($data, [
                'amount' => [
                    'required',
                    'regex:/^\d{1,13}(?:\.\d{1,2})?$/',
                ],
                'category' => [
                    'required',
                    'string',
                    Rule::in(StoreExpense::CATEGORIES),
                ],
                'payment_method' => [
                    'required',
                    'string',
                    Rule::in(StoreExpense::PAYMENT_METHODS),
                ],
                'description' => [
                    'required',
                    'string',
                    'max:2000',
                ],
            ])->validate();

            $amount = bcadd((string) $validated['amount'], '0', 2);

            if (bccomp($amount, '0.00', 2) <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Expense amount must be greater than zero.',
                ]);
            }

            // The Store lock serializes expense writes against other store
            // operations, including closing the business day.
            $priorQualifyingTotal = $businessDay->expenses()
                ->whereNotIn(
                    'category',
                    StoreExpense::THRESHOLD_EXCLUDED_CATEGORIES
                )
                ->pluck('amount')
                ->reduce(
                    fn (string $total, $value): string =>
                        bcadd($total, (string) $value, 2),
                    '0.00'
                );

            $expense = StoreExpense::query()->create([
                'store_id' => $lockedStore->id,
                'store_daily_cash_record_id' => $businessDay->id,
                'amount' => $amount,
                'category' => $validated['category'],
                'payment_method' => $validated['payment_method'],
                'description' => trim($validated['description']),
                'recorded_by' => $lockedActor->id,
            ]);

            $countsTowardThreshold = $expense->countsTowardDailyThreshold();

            $qualifyingTotal = $countsTowardThreshold
                ? bcadd($priorQualifyingTotal, $amount, 2)
                : $priorQualifyingTotal;

            return [
                'expense' => $expense,
                'qualifying_total' => $qualifyingTotal,
                'threshold_exceeded' => $countsTowardThreshold
                    && bccomp($qualifyingTotal, '5000.00', 2) > 0,
            ];
        });
    }
}
