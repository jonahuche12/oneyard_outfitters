<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;

class StorePolicy
{
    /**
     * The store management list: stores.manage holders only.
     */
    public function viewAny(User $user): bool
    {
        return $this->manages($user);
    }

    /**
     * Managers see every store. Staff see only the store they are
     * currently assigned to.
     */
    public function view(User $user, Store $store): bool
    {
        return $this->manages($user) || $this->isActiveMemberOf($user, $store);
    }

    public function create(User $user): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->manages($user);
    }

    public function toggleActive(User $user, Store $store): bool
    {
        return $this->manages($user);
    }

    /**
     * Assign, reassign and end store staff assignments.
     */
    public function manageStaff(User $user, Store $store): bool
    {
        return $this->manages($user);
    }

    public function viewDashboard(User $user, Store $store): bool
    {
        return $this->manages($user)
            || (
                $user->is_active
                && StoreStaff::query()
                    ->active()
                    ->where('user_id', $user->id)
                    ->where('store_id', $store->id)
                    ->where('role', StoreStaff::ROLE_COORDINATOR)
                    ->exists()
            );
    }

    private function manages(User $user): bool
    {
        return $user->is_active && $user->hasPermission('stores.manage');
    }

    private function isActiveMemberOf(User $user, Store $store): bool
    {
        return $user->is_active
            && StoreStaff::query()
                ->active()
                ->where('user_id', $user->id)
                ->where('store_id', $store->id)
                ->exists();
    }
}
