<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_can_be_created_for_an_organization(): void
    {
        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position' => 'Principal',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'organization_id' => $organization->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position' => 'Principal',
            'is_primary' => true,
        ]);
    }

    public function test_contact_belongs_to_organization(): void
    {
        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->assertTrue(
            $contact->organization->is($organization)
        );
    }

    public function test_organization_can_retrieve_its_contacts(): void
    {
        $organization = Organization::factory()->create();

        Contact::factory()
            ->count(3)
            ->create([
                'organization_id' => $organization->id,
            ]);

        $this->assertCount(
            3,
            $organization->fresh()->contacts
        );
    }

    public function test_contact_is_active_by_default(): void
    {
        $contact = Contact::factory()->create();

        $this->assertTrue($contact->is_active);
    }

    public function test_contact_primary_and_active_flags_are_cast_to_boolean(): void
    {
        $contact = Contact::factory()->create([
            'is_primary' => true,
            'is_active' => false,
        ]);

        $contact->refresh();

        $this->assertIsBool($contact->is_primary);
        $this->assertIsBool($contact->is_active);

        $this->assertTrue($contact->is_primary);
        $this->assertFalse($contact->is_active);
    }

    public function test_contact_can_be_soft_deleted(): void
    {
        $contact = Contact::factory()->create();

        $contact->delete();

        $this->assertSoftDeleted('contacts', [
            'id' => $contact->id,
        ]);

        $this->assertNull(
            Contact::find($contact->id)
        );

        $this->assertNotNull(
            Contact::withTrashed()->find($contact->id)
        );
    }

    public function test_hard_deleted_organization_cascades_to_contacts(): void
    {
        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $organization->forceDelete();

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }
}