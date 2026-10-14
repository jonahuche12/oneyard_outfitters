<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;

class ActivePasswordResetUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve only active users for password-reset operations.
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        $user = parent::retrieveByCredentials($credentials);

        if (! $user || ! $user->is_active) {
            return null;
        }

        return $user;
    }
}
