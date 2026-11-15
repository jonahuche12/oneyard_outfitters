<?php

namespace App\Actions\Stores;

use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EndStoreStaffAssignment
{
    use StoreActionSupport;

    public function execute(User $actor, StoreStaff $assignment, string $reason): StoreStaff
    {
        $this->authorizeStoreManager($actor);

        $validated = Validator::make(['reason' => trim($reason)], [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($actor, $assignment, $validated): StoreStaff {
            $locked = StoreStaff::query()->lockForUpdate()->findOrFail($assignment->getKey());

            if (! $locked->isActive()) {
                throw ValidationException::withMessages([
                    'assignment' => 'This assignment has already ended.',
                ]);
            }

            $locked->forceFill([
                'ends_at' => now(),
                'ended_reason' => $validated['reason'],
                'ended_by' => $actor->id,
            ])->save();

            return $locked;
        });
    }
}
