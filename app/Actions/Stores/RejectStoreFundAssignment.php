<?php

namespace App\Actions\Stores;

use App\Models\StoreFundAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RejectStoreFundAssignment
{
    public function execute(
        StoreFundAssignment $assignment,
        string $reason
    ): StoreFundAssignment {
        $validated = Validator::make(
            ['reason' => $reason],
            [
                'reason' => [
                    'required',
                    'string',
                    'max:5000',
                    function ($attribute, $value, $fail) {
                        if (mb_strlen(trim($value)) < 10) {
                            $fail('The rejection reason must contain at least 10 non-whitespace characters.');
                        }
                    },
                ],
            ]
        )->validate();

        return DB::transaction(function () use ($assignment, $validated) {
            $locked = StoreFundAssignment::query()
                ->lockForUpdate()
                ->findOrFail($assignment->id);

            Gate::authorize('reject', $locked);

            if ($locked->status !== StoreFundAssignment::STATUS_REQUESTED) {
                throw ValidationException::withMessages([
                    'status' => 'Only requested assignments can be rejected.',
                ]);
            }

            $actorId = auth()->id();
            $reason = trim($validated['reason']);

            $locked->forceFill([
                'status' => StoreFundAssignment::STATUS_REJECTED,
                'handled_by' => $actorId,
                'rejection_reason' => $reason,
            ])->save();

            $locked->events()->create([
                'actor_id' => $actorId,
                'event_type' => 'rejected',
                'details' => [
                    'previous_status' => StoreFundAssignment::STATUS_REQUESTED,
                    'status' => StoreFundAssignment::STATUS_REJECTED,
                    'rejection_reason' => $reason,
                ],
            ]);

            return $locked->refresh();
        });
    }
}
