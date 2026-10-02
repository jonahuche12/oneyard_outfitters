<?php

namespace App\Policies;

use App\Models\ProcurementOffer;
use App\Models\User;

class ProcurementOfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('procurement.view')
            || $this->isAdmin($user);
    }

    public function view(User $user, ProcurementOffer $offer): bool
    {
        return $this->isAdmin($user)
            || (
                $user->hasPermission('procurement.view')
                && $offer->user_id === $user->id
            );
    }

    public function update(User $user, ProcurementOffer $offer): bool
    {
        if ($this->isAdmin($user)) {
            return $offer->status === ProcurementOffer::STATUS_SUBMITTED;
        }

        return $user->hasPermission('procurement.view')
            && $offer->user_id === $user->id
            && $offer->status === ProcurementOffer::STATUS_SUBMITTED;
    }

    public function withdraw(User $user, ProcurementOffer $offer): bool
    {
        return $user->hasPermission('procurement.view')
            && $offer->user_id === $user->id
            && $offer->status === ProcurementOffer::STATUS_SUBMITTED;
    }

    public function accept(User $user, ProcurementOffer $offer): bool
    {
        if (! $user->hasPermission('procurement.manage')) {
            return false;
        }

        if ($offer->status !== ProcurementOffer::STATUS_SUBMITTED) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $offer->procurement?->order?->currentAssignment?->user_id === $user->id;
    }

    public function reject(User $user, ProcurementOffer $offer): bool
    {
        if (! $user->hasPermission('procurement.manage')) {
            return false;
        }

        if ($offer->status !== ProcurementOffer::STATUS_SUBMITTED) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $offer->procurement?->order?->currentAssignment?->user_id === $user->id;
    }

    private function isAdmin(User $user): bool
    {
        return $user->roles()
            ->whereIn('slug', ['admin', 'super-admin'])
            ->exists();
    }
}
