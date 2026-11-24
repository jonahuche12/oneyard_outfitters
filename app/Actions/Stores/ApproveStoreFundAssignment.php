<?php

namespace App\Actions\Stores;

use App\Models\StoreFundAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveStoreFundAssignment
{
    public function execute(StoreFundAssignment $assignment): StoreFundAssignment
    {
        return DB::transaction(function () use ($assignment) {
            $locked = StoreFundAssignment::query()
                ->lockForUpdate()
                ->findOrFail($assignment->id);

            Gate::authorize('approve', $locked);

            if ($locked->status !== StoreFundAssignment::STATUS_REQUESTED) {
                throw ValidationException::withMessages([
                    'status' => 'Only requested assignments can be approved.',
                ]);
            }

            $actorId = auth()->id();
            $now = now();

            $locked->forceFill([
                'status' => StoreFundAssignment::STATUS_APPROVED,
                'handled_by' => $actorId,
                'approved_by' => $actorId,
                'approved_at' => $now,
                'rejection_reason' => null,
            ])->save();

            $locked->events()->create([
                'actor_id' => $actorId,
                'event_type' => 'approved',
                'details' => [
                    'previous_status' => StoreFundAssignment::STATUS_REQUESTED,
                    'status' => StoreFundAssignment::STATUS_APPROVED,
                    'approved_at' => $now->toISOString(),
                ],
            ]);

            return $locked->refresh();
        });
    }
}
