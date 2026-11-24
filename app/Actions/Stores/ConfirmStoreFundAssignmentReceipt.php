<?php

namespace App\Actions\Stores;

use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreFundAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ConfirmStoreFundAssignmentReceipt
{
    public function execute(
        StoreFundAssignment $assignment,
        array $data
    ): StoreFundAssignment {
        $validated = Validator::make($data, [
            'received_cash' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'received_bank' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
        ])->validate();

        return DB::transaction(function () use ($assignment, $validated) {
            $actorId = Auth::id();

            /*
             * Keep the same broad lock order used by store cash operations:
             * user -> store -> daily record.
             *
             * The assignment is locked after those records to prevent a
             * confirmation racing with opening-balance creation.
             */
            if ($actorId) {
                User::query()
                    ->lockForUpdate()
                    ->findOrFail($actorId);
            }

            $store = Store::query()
                ->lockForUpdate()
                ->findOrFail($assignment->store_id);

            $today = today()->toDateString();

            $dailyCash = StoreDailyCashRecord::query()
                ->where('store_id', $store->id)
                ->whereDate('business_date', $today)
                ->lockForUpdate()
                ->first();

            $locked = StoreFundAssignment::query()
                ->lockForUpdate()
                ->findOrFail($assignment->id);

            Gate::authorize('confirm', $locked);

            if ($locked->status !== StoreFundAssignment::STATUS_SENT) {
                throw ValidationException::withMessages([
                    'status' => 'Only dispatched fund assignments can be confirmed.',
                ]);
            }

            $receivedCash = $this->toMinorUnits($validated['received_cash']);
            $receivedBank = $this->toMinorUnits($validated['received_bank']);

            if ($receivedCash === 0 && $receivedBank === 0) {
                throw ValidationException::withMessages([
                    'received_cash' => 'At least one received amount must be greater than zero.',
                ]);
            }

            if (
                $receivedCash !== $this->toMinorUnits($locked->sent_cash)
                || $receivedBank !== $this->toMinorUnits($locked->sent_bank)
            ) {
                throw ValidationException::withMessages([
                    'received_cash' => 'Received cash and bank amounts must exactly match the dispatched amounts.',
                ]);
            }

            $confirmedAt = now();

            /*
             * An assignment created before opening has no daily-record link.
             * If it is confirmed after opening, attach it to the open record
             * so the dashboard includes it as an intraday receipt.
             *
             * Never change opening totals here. The dashboard subtracts the
             * amounts already included by Step 6 from its confirmed total.
             *
             * Assignments already linked to a daily record retain that link.
             * A closed day's closing figures are never changed here.
             */
            $dailyRecordId = $locked->store_daily_cash_record_id;

            if (
                $dailyRecordId === null
                && $dailyCash !== null
                && $dailyCash->closed_by === null
                && $locked->business_date?->toDateString() === $today
            ) {
                $dailyRecordId = $dailyCash->id;
            }

            $locked->forceFill([
                'store_daily_cash_record_id' => $dailyRecordId,
                'status' => StoreFundAssignment::STATUS_CONFIRMED,
                'confirmed_by' => $actorId,
                'confirmed_at' => $confirmedAt,
            ])->save();

            $locked->events()->create([
                'actor_id' => $actorId,
                'event_type' => 'receipt_confirmed',
                'details' => [
                    'previous_status' => StoreFundAssignment::STATUS_SENT,
                    'status' => StoreFundAssignment::STATUS_CONFIRMED,
                    'received_cash' => $locked->sent_cash,
                    'received_bank' => $locked->sent_bank,
                    'store_daily_cash_record_id' => $dailyRecordId,
                    'confirmed_at' => $confirmedAt->toISOString(),
                ],
            ]);

            return $locked->refresh();
        });
    }

    /**
     * Convert a validated decimal amount into integer minor units
     * without floating-point comparison.
     */
    private function toMinorUnits(mixed $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^\+?(\d+)(?:\.(\d{1,2}))?$/D', $value, $matches)) {
            throw ValidationException::withMessages([
                'amount' => 'The amount must have no more than two decimal places.',
            ]);
        }

        $whole = (int) $matches[1];
        $fraction = (int) str_pad($matches[2] ?? '', 2, '0');

        return ($whole * 100) + $fraction;
    }
}
