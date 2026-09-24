<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('organizations.view');
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->hasPermission('organizations.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('organizations.create');
    }

    public function export(User $user, Organization $organization): bool
    {
        return $user->hasPermission('organizations.export');
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->hasPermission('organizations.update');
    }

    public function activate(User $user, Organization $organization): bool
    {
        return $this->canManageLifecycle($user);
    }

    public function deactivate(User $user, Organization $organization): bool
    {
        return $this->canManageLifecycle($user);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->hasPermission('organizations.delete');
    }

    private function canManageLifecycle(User $user): bool
    {
        return $user->hasPermission('organizations.update')
            && $user->roles()
                ->whereIn('slug', ['admin', 'super-admin'])
                ->exists();
    }
}
