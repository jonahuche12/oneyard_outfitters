<?php

namespace App\Actions\Stores;

use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreFundAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateDirectStoreFundAssignment
{
    /**
     * Create an administrator-originated assignment.
     *
     * Direct assignments do not need a separate approval step.
     * The creating administrator is recorded as requester, handler
     * and approver. Dispatch remains a separate workflow step.
     */
    public function execute(Store $store, array $data): StoreFundAssignment
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($store, $validated) {
            $lockedStore = Store::query()
                ->lockForUpdate()
                ->findOrFail($store->id);

            Gate::authorize('createDirect', [StoreFundAssignment::class, $lockedStore]);

            $businessDate = today()->toDateString();

            $dailyRecord = StoreDailyCashRecord::query()
                ->where('store_id', $lockedStore->id)
                ->whereDate('business_date', $businessDate)
                ->lockForUpdate()
                ->first();

            $actorId = auth()->id();
            $now = now();

            $assignment = $lockedStore->fundAssignments()->create([
                'store_daily_cash_record_id' => $dailyRecord?->id,
                'business_date' => $businessDate,
                'source' => StoreFundAssignment::SOURCE_DIRECT_ASSIGNMENT,
                'purpose' => $validated['purpose'],
                'status' => StoreFundAssignment::STATUS_APPROVED,
                'requested_cash' => $validated['requested_cash'],
                'requested_bank' => $validated['requested_bank'],
                'requested_by' => $actorId,
                'handled_by' => $actorId,
                'approved_by' => $actorId,
                'approved_at' => $now,
                'note' => $validated['note'] ?? null,
                'reference' => $validated['reference'] ?? null,
            ]);

            $assignment->events()->create([
                'actor_id' => $actorId,
                'event_type' => 'direct_assigned',
                'details' => [
                    'source' => StoreFundAssignment::SOURCE_DIRECT_ASSIGNMENT,
                    'business_date' => $businessDate,
                    'purpose' => $validated['purpose'],
                    'requested_cash' => $validated['requested_cash'],
                    'requested_bank' => $validated['requested_bank'],
                    'daily_cash_record_id' => $dailyRecord?->id,
                    'approved_by' => $actorId,
                    'approved_at' => $now->toISOString(),
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
