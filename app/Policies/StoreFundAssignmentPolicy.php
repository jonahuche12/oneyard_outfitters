<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\StoreFundAssignment;
use App\Models\StoreStaff;
use App\Models\User;

class StoreFundAssignmentPolicy
{
    public const VIEW_PERMISSION = 'stores.funds.view';
    public const MANAGE_PERMISSION = 'stores.funds.manage';
    public const REVIEW_PERMISSION = 'stores.funds.review';

    public function viewAny(User $user): bool
    {
        return $this->canViewAllManagementRecords($user)
            || $this->isFinanceReviewer($user);
    }

    /**
     * Used by the controller to identify the restricted finance queue.
     */
    public function reviewRequests(User $user): bool
    {
        return $this->isFinanceReviewer($user);
    }

    public function viewManagementHistory(User $user): bool
    {
        return $this->canViewAllManagementRecords($user);
    }

    public function view(User $user, StoreFundAssignment $assignment): bool
    {
        if ($this->canViewAllManagementRecords($user)) {
            return true;
        }

        if ($this->isFinanceReviewer($user)) {
            return $assignment->source === StoreFundAssignment::SOURCE_STORE_REQUEST;
        }

        return $this->isActiveStoreMember($user, (int) $assignment->store_id);
    }

    public function viewStore(User $user, Store $store): bool
    {
        if (! $user->is_active || ! $store->is_active) {
            return false;
        }

        return $this->canViewAllManagementRecords($user)
            || $this->isActiveStoreMember($user, (int) $store->id);
    }

    public function request(User $user, Store $store): bool
    {
        return $user->is_active
            && $store->is_active
            && $this->isActiveStoreMember($user, (int) $store->id);
    }

    public function createDirect(User $user, Store $store): bool
    {
        if (! $user->is_active || ! $store->is_active || ! $this->canManage($user)) {
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return ! $this->isActiveStoreMember($user, (int) $store->id);
    }

    public function approve(User $user, StoreFundAssignment $assignment): bool
    {
        return $this->mayReviewOrHandle($user, $assignment);
    }

    public function reject(User $user, StoreFundAssignment $assignment): bool
    {
        return $this->mayReviewOrHandle($user, $assignment);
    }

    public function dispatch(User $user, StoreFundAssignment $assignment): bool
    {
        return $this->mayHandle($user, $assignment);
    }

    public function correctDispatch(User $user, StoreFundAssignment $assignment): bool
    {
        if (! $user->is_active || ! $this->canManage($user)) {
            return false;
        }

        return $this->isSuperAdmin($user)
            || (int) $assignment->dispatched_by === (int) $user->id;
    }

    public function confirm(User $user, StoreFundAssignment $assignment): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return StoreStaff::query()->active()
            ->where('user_id', $user->id)
            ->where('store_id', $assignment->store_id)
            ->where('role', StoreStaff::ROLE_COORDINATOR)
            ->exists();
    }

    private function canViewAllManagementRecords(User $user): bool
    {
        return $this->canManage($user)
            && $user->hasPermission(self::VIEW_PERMISSION);
    }

    private function canManage(User $user): bool
    {
        return $user->is_active
            && $user->hasPermission(self::MANAGE_PERMISSION)
            && $this->isAdministrator($user);
    }

    private function isFinanceReviewer(User $user): bool
    {
        return $user->is_active
            && $user->hasPermission(self::VIEW_PERMISSION)
            && $user->hasPermission(self::REVIEW_PERMISSION)
            && $user->roles->contains('slug', 'finance');
    }

    private function mayReviewOrHandle(
        User $user,
        StoreFundAssignment $assignment
    ): bool {
        if ($this->canManage($user)) {
            return $this->mayHandle($user, $assignment);
        }

        return $this->isFinanceReviewer($user)
            && $assignment->source === StoreFundAssignment::SOURCE_STORE_REQUEST;
    }

    private function mayHandle(User $user, StoreFundAssignment $assignment): bool
    {
        if (! $this->canManage($user)) {
            return false;
        }

        if ($assignment->source !== StoreFundAssignment::SOURCE_DIRECT_ASSIGNMENT) {
            return true;
        }

        return $this->isSuperAdmin($user)
            || (int) $assignment->requested_by === (int) $user->id;
    }

    private function isAdministrator(User $user): bool
    {
        return $user->roles->contains(
            fn ($role) => in_array($role->slug, ['admin', 'super-admin'], true)
        );
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->roles->contains('slug', 'super-admin');
    }

    private function isActiveStoreMember(User $user, int $storeId): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return StoreStaff::query()->active()
            ->where('user_id', $user->id)
            ->where('store_id', $storeId)
            ->whereIn('role', [
                StoreStaff::ROLE_COORDINATOR,
                StoreStaff::ROLE_ASSISTANT,
            ])
            ->exists();
    }
}
