<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('contacts.view');
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->hasPermission('contacts.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('contacts.create');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->hasPermission('contacts.update');
    }

    public function updateNotes(User $user, Contact $contact): bool
    {
        return $user->hasPermission('contacts.update-notes');
    }

    public function activate(User $user, Contact $contact): bool
    {
        return $this->canManageLifecycle($user, 'contacts.activate');
    }

    public function deactivate(User $user, Contact $contact): bool
    {
        return $this->canManageLifecycle($user, 'contacts.deactivate');
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->hasPermission('contacts.delete')
            && $user->roles()
                ->whereIn('slug', ['admin', 'super-admin'])
                ->exists();
    }

    private function canManageLifecycle(
        User $user,
        string $permission
    ): bool {
        return $user->hasPermission($permission)
            && $user->roles()
                ->whereIn('slug', ['admin', 'super-admin'])
                ->exists();
    }
}
