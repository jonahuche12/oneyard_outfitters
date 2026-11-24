<?php

namespace App\Actions\Stores;

use App\Models\StoreFundAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DispatchStoreFundAssignment
{
    public function execute(
        StoreFundAssignment $assignment,
        array $data
    ): StoreFundAssignment {
        $validated = Validator::make($data, [
            'sent_cash' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'sent_bank' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
        ])->validate();

        if (
            (float) $validated['sent_cash'] <= 0
            && (float) $validated['sent_bank'] <= 0
        ) {
            throw ValidationException::withMessages([
                'sent_cash' => 'At least one of cash or bank sent must be greater than zero.',
                'sent_bank' => 'At least one of cash or bank sent must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($assignment, $validated) {
            $locked = StoreFundAssignment::query()
                ->lockForUpdate()
                ->findOrFail($assignment->id);

            Gate::authorize('dispatch', $locked);

            if ($locked->status !== StoreFundAssignment::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'status' => 'Only approved assignments can be dispatched.',
                ]);
            }

            $actorId = auth()->id();
            $now = now();

            $locked->forceFill([
                'status' => StoreFundAssignment::STATUS_SENT,
                'sent_cash' => $validated['sent_cash'],
                'sent_bank' => $validated['sent_bank'],
                'dispatched_by' => $actorId,
                'dispatched_at' => $now,
            ])->save();

            $locked->events()->create([
                'actor_id' => $actorId,
                'event_type' => 'dispatched',
                'details' => [
                    'previous_status' => StoreFundAssignment::STATUS_APPROVED,
                    'status' => StoreFundAssignment::STATUS_SENT,
                    'sent_cash' => $validated['sent_cash'],
                    'sent_bank' => $validated['sent_bank'],
                    'dispatched_at' => $now->toISOString(),
                ],
            ]);

            return $locked->refresh();
        });
    }
}
