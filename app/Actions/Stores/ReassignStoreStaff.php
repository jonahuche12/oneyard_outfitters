<?php

namespace App\Actions\Stores;

use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReassignStoreStaff
{
    use StoreActionSupport;

    /**
     * Move a user with an active assignment to another store, or change their
     * role inside the same store. The old assignment is closed, never edited
     * away, so history is kept.
     */
    public function execute(User $actor, User $user, Store $toStore, string $role): StoreStaff
    {
        $this->authorizeStoreManager($actor);

        Validator::make(['role' => $role], [
            'role' => ['required', Rule::in(StoreStaff::ROLES)],
        ])->validate();

        try {
            return DB::transaction(function () use ($actor, $user, $toStore, $role): StoreStaff {
                // Lock order everywhere: user, then store, then assignment rows.
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
                $lockedStore = Store::query()->lockForUpdate()->findOrFail($toStore->getKey());

                $this->assertStoreAcceptsStaff($lockedStore);
                $this->assertUserAssignable($lockedUser);

                $current = StoreStaff::query()
                    ->active()
                    ->where('user_id', $lockedUser->id)
                    ->lockForUpdate()
                    ->first();

                if (! $current) {
                    throw ValidationException::withMessages([
                        'user_id' => $lockedUser->name.' has no active store assignment. Use assign instead.',
                    ]);
                }

                $sameStore = (int) $current->store_id === (int) $lockedStore->id;

                if ($sameStore && $current->role === $role) {
                    throw ValidationException::withMessages([
                        'role' => 'The user already holds this role in this store.',
                    ]);
                }

                $current->forceFill([
                    'ends_at' => now(),
                    'ended_reason' => $sameStore
                        ? 'Role changed to '.$role.'.'
                        : 'Reassigned to '.$lockedStore->name.'.',
                    'ended_by' => $actor->id,
                ])->save();

                if ($role === StoreStaff::ROLE_COORDINATOR) {
                    $this->endCurrentCoordinator($lockedStore, $actor, 'Replaced by a new coordinator.');
                }

                return StoreStaff::query()->create([
                    'store_id' => $lockedStore->id,
                    'user_id' => $lockedUser->id,
                    'role' => $role,
                    'starts_at' => now(),
                    'assigned_by' => $actor->id,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'user_id' => 'This conflicts with another change made at the same time. Refresh and try again.',
            ]);
        }
    }
}
