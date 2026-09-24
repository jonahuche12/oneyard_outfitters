<?php

namespace App\Policies;

use App\Models\ContactNote;
use App\Models\User;

class ContactNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('contact-notes.view');
    }

    public function view(User $user, ContactNote $contactNote): bool
    {
        return $user->hasPermission('contact-notes.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('contact-notes.create');
    }

    public function update(User $user, ContactNote $contactNote): bool
    {
        return $user->hasPermission('contact-notes.update');
    }

    public function delete(User $user, ContactNote $contactNote): bool
    {
        return $user->hasPermission('contact-notes.delete');
    }
}
