<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function view(User $user, User $staff): bool
    {
        return $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $staff): bool
    {
        return $user->hasPermission('users.update');
    }

    public function activate(User $user, User $staff): bool
    {
        return $user->hasPermission('users.activate');
    }

    public function deactivate(User $user, User $staff): bool
    {
        return $user->hasPermission('users.deactivate');
    }

    public function assignRoles(User $user, User $staff): bool
    {
        return $user->hasPermission('users.assign-roles');
    }
}