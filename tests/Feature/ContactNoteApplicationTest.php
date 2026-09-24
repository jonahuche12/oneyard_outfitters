<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ContactNote;
use Database\Seeders\RbacSeeder;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactNoteApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    private function activeUserWithRole(string $roleSlug): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $role = Role::where('slug', $roleSlug)->firstOrFail();

        $user->roles()->attach($role);

        return $user;
    }

    public function test_authorized_user_can_record_a_contact_interaction(): void
    {
        $user = $this->activeUserWithRole('sales');

        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('contact-notes.store', $contact), [
                'note' => 'Discussed uniform requirements and requested a sample.',
            ]);

        $response
            ->assertRedirect(route('contacts.show', $contact))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contact_notes', [
            'contact_id' => $contact->id,
            'recorded_by' => $user->id,
            'note' => 'Discussed uniform requirements and requested a sample.',
        ]);
    }

    public function test_user_without_create_permission_cannot_record_interaction(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('contact-notes.store', $contact), [
                'note' => 'Unauthorized interaction.',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('contact_notes', [
            'contact_id' => $contact->id,
            'note' => 'Unauthorized interaction.',
        ]);
    }

    public function test_authorized_user_can_update_an_interaction(): void
    {
        $user = $this->activeUserWithRole('sales');

        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $contactNote = ContactNote::create([
            'contact_id' => $contact->id,
            'recorded_by' => $user->id,
            'note' => 'Original interaction.',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('contact-notes.update', $contactNote), [
                'note' => 'Updated interaction record.',
            ]);

        $response->assertRedirect(route('contacts.show', $contact));

        $this->assertDatabaseHas('contact_notes', [
            'id' => $contactNote->id,
            'note' => 'Updated interaction record.',
        ]);
    }

    public function test_authorized_user_can_delete_an_interaction(): void
    {
        $user = $this->activeUserWithRole('admin');

        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $contactNote = ContactNote::create([
            'contact_id' => $contact->id,
            'recorded_by' => $user->id,
            'note' => 'Interaction to remove.',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('contact-notes.destroy', $contactNote));

        $response->assertRedirect(route('contacts.show', $contact));

        $this->assertSoftDeleted('contact_notes', [
            'id' => $contactNote->id,
        ]);
    }
}
