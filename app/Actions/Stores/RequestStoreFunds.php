<?php

namespace App\Actions\Stores;

use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreFundAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RequestStoreFunds
{
    /**
     * Create a store-originated request for the current business date.
     *
     * The store may not yet have opened. In that case the daily cash
     * record relationship remains null until opening integration.
     */
    public function execute(Store $store, array $data): StoreFundAssignment
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($store, $validated) {
            $lockedStore = Store::query()
                ->lockForUpdate()
                ->findOrFail($store->id);

            Gate::authorize('request', [StoreFundAssignment::class, $lockedStore]);

            $businessDate = today()->toDateString();

            $dailyRecord = StoreDailyCashRecord::query()
                ->where('store_id', $lockedStore->id)
                ->whereDate('business_date', $businessDate)
                ->lockForUpdate()
                ->first();

            $assignment = $lockedStore->fundAssignments()->create([
                'store_daily_cash_record_id' => $dailyRecord?->id,
                'business_date' => $businessDate,
                'source' => StoreFundAssignment::SOURCE_STORE_REQUEST,
                'purpose' => $validated['purpose'],
                'status' => StoreFundAssignment::STATUS_REQUESTED,
                'requested_cash' => $validated['requested_cash'],
                'requested_bank' => $validated['requested_bank'],
                'requested_by' => auth()->id(),
                'note' => $validated['note'] ?? null,
                'reference' => $validated['reference'] ?? null,
            ]);

            $assignment->events()->create([
                'actor_id' => auth()->id(),
                'event_type' => 'requested',
                'details' => [
                    'source' => StoreFundAssignment::SOURCE_STORE_REQUEST,
                    'business_date' => $businessDate,
                    'purpose' => $validated['purpose'],
                    'requested_cash' => $validated['requested_cash'],
                    'requested_bank' => $validated['requested_bank'],
                    'daily_cash_record_id' => $dailyRecord?->id,
                ],
            ]);

            return $assignment->refresh();
        });
    }

    private function validate(array $data): array
    {
        $validated = Validator::make($data, [
            'purpose' => [
                'required',
                'string',
                Rule::in(StoreFundAssignment::PURPOSES),
            ],
            'requested_cash' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'requested_bank' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'note' => ['nullable', 'string', 'max:5000'],
            'reference' => ['nullable', 'string', 'max:100'],
        ])->validate();

        if (
            (float) $validated['requested_cash'] <= 0
            && (float) $validated['requested_bank'] <= 0
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'requested_cash' => 'At least one of cash or bank must be greater than zero.',
                'requested_bank' => 'At least one of cash or bank must be greater than zero.',
            ]);
        }

        return $validated;
    }
}
