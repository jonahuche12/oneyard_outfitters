<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_contact_lifecycle(): void
    {
        $this->seed(RbacSeeder::class);

        $user = User::factory()->create();
        $role = Role::where('slug', 'admin')->firstOrFail();
        $user->roles()->attach($role);

        $contact = Contact::factory()->create();

        $this->assertTrue($user->can('view', $contact));
        $this->assertTrue($user->can('update', $contact));
        $this->assertTrue($user->can('updateNotes', $contact));
        $this->assertTrue($user->can('activate', $contact));
        $this->assertTrue($user->can('deactivate', $contact));
        $this->assertTrue($user->can('delete', $contact));
    }

    public function test_sales_can_manage_contact_information_but_not_lifecycle_or_delete(): void
    {
        $this->seed(RbacSeeder::class);

        $user = User::factory()->create();
        $role = Role::where('slug', 'sales')->firstOrFail();
        $user->roles()->attach($role);

        $contact = Contact::factory()->create();

        $this->assertTrue($user->can('view', $contact));
        $this->assertTrue($user->can('update', $contact));
        $this->assertTrue($user->can('updateNotes', $contact));

        $this->assertFalse($user->can('activate', $contact));
        $this->assertFalse($user->can('deactivate', $contact));
        $this->assertFalse($user->can('delete', $contact));
    }

    public function test_operational_roles_can_view_contacts_without_contact_management_access(): void
    {
        $this->seed(RbacSeeder::class);

        $contact = Contact::factory()->create();

        foreach ([
            'procurement',
            'production',
            'quality-control',
            'finance',
            'delivery',
        ] as $roleSlug) {
            $user = User::factory()->create();
            $role = Role::where('slug', $roleSlug)->firstOrFail();
            $user->roles()->attach($role);

            $this->assertTrue(
                $user->can('view', $contact),
                "{$roleSlug} should be able to view contacts."
            );

            $this->assertFalse(
                $user->can('update', $contact),
                "{$roleSlug} should not update contacts."
            );

            $this->assertFalse(
                $user->can('activate', $contact),
                "{$roleSlug} should not activate contacts."
            );

            $this->assertFalse(
                $user->can('deactivate', $contact),
                "{$roleSlug} should not deactivate contacts."
            );

            $this->assertFalse(
                $user->can('delete', $contact),
                "{$roleSlug} should not delete contacts."
            );
        }
    }

    public function test_contact_policy_is_registered_for_contact_model(): void
    {
        $this->seed(RbacSeeder::class);

        $user = User::factory()->create();
        $role = Role::where('slug', 'admin')->firstOrFail();
        $user->roles()->attach($role);

        $contact = Contact::factory()->create();

        $this->assertTrue($user->can('view', $contact));
    }

    public function test_sales_cannot_manage_contact_lifecycle_even_with_lifecycle_permissions_absent(): void
    {
        $this->seed(RbacSeeder::class);

        $user = User::factory()->create();
        $role = Role::where('slug', 'sales')->firstOrFail();
        $user->roles()->attach($role);

        $contact = Contact::factory()->create();

        $this->assertFalse($user->can('activate', $contact));
        $this->assertFalse($user->can('deactivate', $contact));
    }
}
