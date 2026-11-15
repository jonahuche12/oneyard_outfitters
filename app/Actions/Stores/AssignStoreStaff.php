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

class AssignStoreStaff
{
    use StoreActionSupport;

    /**
     * Assign a user who has no active store assignment to a store.
     * Assigning a coordinator ends the store's previous coordinator assignment.
     */
    public function execute(User $actor, Store $store, User $user, string $role): StoreStaff
    {
        $this->authorizeStoreManager($actor);

        Validator::make(['role' => $role], [
            'role' => ['required', Rule::in(StoreStaff::ROLES)],
        ])->validate();

        try {
            return DB::transaction(function () use ($actor, $store, $user, $role): StoreStaff {
                // Lock order everywhere: user, then store, then assignment rows.
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
                $lockedStore = Store::query()->lockForUpdate()->findOrFail($store->getKey());

                $this->assertStoreAcceptsStaff($lockedStore);
                $this->assertUserAssignable($lockedUser);

                $existing = StoreStaff::query()
                    ->active()
                    ->where('user_id', $lockedUser->id)
                    ->with('store')
                    ->first();

                if ($existing) {
                    throw ValidationException::withMessages([
                        'user_id' => $lockedUser->name.' is already assigned to '.$existing->store->name.'. Use reassign instead.',
                    ]);
                }

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
