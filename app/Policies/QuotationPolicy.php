<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('quotations.view');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('quotations.create');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.update')
            && $quotation->status === Quotation::STATUS_DRAFT;
    }

    public function send(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.send')
            && $quotation->status === Quotation::STATUS_DRAFT;
    }

    public function cancel(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.cancel')
            && in_array(
                $quotation->status,
                [
                    Quotation::STATUS_DRAFT,
                    Quotation::STATUS_SENT,
                ],
                true
            );
    }
}
