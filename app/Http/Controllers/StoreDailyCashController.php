<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreFundAssignment;
use App\Models\StoreStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreDailyCashController extends Controller
{
    public function open(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeStoreAccess($request, $store);

        $data = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'opening_bank' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        DB::transaction(function () use ($store, $request, $data): void {
            // Serialize opening attempts for this store before checking today's record.
            $lockedStore = Store::query()->lockForUpdate()->findOrFail($store->id);
            $date = today()->toDateString();

            $existing = StoreDailyCashRecord::query()
                ->where('store_id', $lockedStore->id)
                ->whereDate('business_date', $date)
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'opening_cash' => 'This store already has an opening record for today.',
                ]);
            }

            $assignments = StoreFundAssignment::query()
                ->where('store_id', $lockedStore->id)
                ->whereDate('business_date', $date)
                ->where('status', StoreFundAssignment::STATUS_CONFIRMED)
                ->whereNull('store_daily_cash_record_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $assignedCashMinor = 0;
            $assignedBankMinor = 0;

            foreach ($assignments as $assignment) {
                $assignedCashMinor += $this->toMinorUnits(
                    $assignment->sent_cash ?? '0.00'
                );

                $assignedBankMinor += $this->toMinorUnits(
                    $assignment->sent_bank ?? '0.00'
                );
            }

            $enteredCashMinor = $this->toMinorUnits($data['opening_cash']);
            $enteredBankMinor = $this->toMinorUnits($data['opening_bank']);

            $record = $lockedStore->dailyCashRecords()->create([
                'business_date' => $date,

                'entered_opening_cash' => $this->fromMinorUnits($enteredCashMinor),
                'entered_opening_bank' => $this->fromMinorUnits($enteredBankMinor),

                'assigned_opening_cash' => $this->fromMinorUnits($assignedCashMinor),
                'assigned_opening_bank' => $this->fromMinorUnits($assignedBankMinor),

                'opening_cash' => $this->fromMinorUnits(
                    $enteredCashMinor + $assignedCashMinor
                ),

                'opening_bank' => $this->fromMinorUnits(
                    $enteredBankMinor + $assignedBankMinor
                ),

                'opened_by' => $request->user()->id,
            ]);

            foreach ($assignments as $assignment) {
                $assignment->forceFill([
                    'store_daily_cash_record_id' => $record->id,
                ])->save();
            }
        });

        return redirect()
            ->route('stores.show', $store)
            ->with('success', 'Today’s opening balances have been recorded.');
    }

    public function close(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeStoreAccess($request, $store);

        $data = $request->validate([
            'closing_cash' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'closing_bank' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        DB::transaction(function () use ($store, $request, $data): void {
            $lockedStore = Store::query()->lockForUpdate()->findOrFail($store->id);

            $record = $lockedStore->dailyCashRecords()
                ->whereDate('business_date', today()->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $record) {
                throw ValidationException::withMessages([
                    'closing_cash' => 'Record the opening balances before closing the day.',
                ]);
            }

            if ($record->closed_by !== null) {
                throw ValidationException::withMessages([
                    'closing_cash' => 'This business day has already been closed.',
                ]);
            }

            $record->update([
                'closing_cash' => $data['closing_cash'],
                'closing_bank' => $data['closing_bank'],
                'closed_by' => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('stores.show', $store)
            ->with('success', 'Today’s closing balances have been recorded.');
    }

    /**
     * Convert a decimal amount to integer minor units without floating-point
     * arithmetic.
     */
    private function toMinorUnits(mixed $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^\+?(\d+)(?:\.(\d{1,2}))?$/D', $value, $matches)) {
            throw new \InvalidArgumentException(
                'Invalid monetary amount encountered during opening balance calculation.'
            );
        }

        $whole = (int) $matches[1];
        $fraction = (int) str_pad($matches[2] ?? '', 2, '0');

        return ($whole * 100) + $fraction;
    }

    private function fromMinorUnits(int $amount): string
    {
        return intdiv($amount, 100)
            . '.'
            . str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }

    private function authorizeStoreAccess(Request $request, Store $store): void
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && $store->is_active, 403);

        abort_unless(
            StoreStaff::query()
                ->active()
                ->where('user_id', $user->id)
                ->where('store_id', $store->id)
                ->whereIn('role', [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ])
                ->exists(),
            403
        );
    }
}
