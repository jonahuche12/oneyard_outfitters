<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\StoreRemittance;
use App\Models\StoreStaff;
use App\Models\User;

class StoreRemittancePolicy
{
    public const REVIEW_PERMISSION = 'stores.remittances.review';

    /**
     * The finance review queue across all stores.
     */
    public function viewAny(User $user): bool
    {
        return $this->canReview($user);
    }

    /**
     * Reviewers see every remission. Store staff see their own store's.
     */
    public function view(User $user, StoreRemittance $remittance): bool
    {
        return $this->canReview($user)
            || $this->isActiveMemberOf($user, $remittance->store_id);
    }

    /**
     * Called as: Gate::authorize('submit', [StoreRemittance::class, $store]).
     * Same rule as SubmitStoreRemittance.
     */
    public function viewStore(User $user, Store $store): bool
    {
        return $this->canReview($user)
            || ($store->is_active && $this->isActiveMemberOf($user, $store->id));
    }

    public function submit(User $user, Store $store): bool
    {
        return $user->is_active
            && $store->is_active
            && $this->isActiveMemberOf($user, $store->id);
    }

    /**
     * Reviewers cannot review a remission they submitted themselves.
     */
    public function review(User $user, StoreRemittance $remittance): bool
    {
        return $this->canReview($user)
            && $remittance->submitted_by !== $user->id;
    }

    private function canReview(User $user): bool
    {
        return $user->is_active && $user->hasPermission(self::REVIEW_PERMISSION);
    }

    private function isActiveMemberOf(User $user, int $storeId): bool
    {
        return $user->is_active
            && StoreStaff::query()
                ->active()
                ->where('user_id', $user->id)
                ->where('store_id', $storeId)
                ->whereIn('role', [StoreStaff::ROLE_COORDINATOR, StoreStaff::ROLE_ASSISTANT])
                ->exists();
    }
}
