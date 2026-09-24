<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the application's initial Super Admin account.
     *
     * This seeder is intentionally idempotent:
     * - It creates the account if it does not exist.
     * - It never overwrites an existing password.
     * - It ensures the account remains active.
     * - It ensures the super-admin role is assigned.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL');
        $name = env('SUPER_ADMIN_NAME', 'Super Administrator');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (! $email) {
            throw new RuntimeException(
                'SUPER_ADMIN_EMAIL is not configured in the environment.'
            );
        }

        if (! $password) {
            throw new RuntimeException(
                'SUPER_ADMIN_PASSWORD is not configured in the environment.'
            );
        }

        $role = Role::query()
            ->where('slug', 'super-admin')
            ->first();

        if (! $role) {
            throw new RuntimeException(
                'The super-admin role does not exist. Run RbacSeeder first.'
            );
        }

        $user = User::query()
            ->where('email', $email)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'is_active' => true,
            ]);
        } else {
            $user->update([
                'is_active' => true,
            ]);
        }

        $user->roles()->syncWithoutDetaching([
            $role->id,
        ]);
    }
}