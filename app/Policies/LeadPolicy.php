<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leads.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        if (! $user->hasPermission('leads.update')) {
            return false;
        }

        return $user->isAdministrator()
            || $lead->created_by === $user->id;
    }

    public function assign(User $user, Lead $lead): bool
    {
        if (! $user->hasPermission('leads.assign')) {
            return false;
        }

        return $user->isAdministrator()
            || $lead->created_by === $user->id;
    }

    public function convert(User $user, Lead $lead): bool
    {
        if (! $user->hasPermission('leads.convert')) {
            return false;
        }

        return $user->isAdministrator()
            || $lead->created_by === $user->id;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->isAdministrator()
            && $user->hasPermission('leads.update');
    }
}
