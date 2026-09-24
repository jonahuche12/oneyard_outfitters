<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ContactNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_note_can_be_created_for_a_contact(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $note = ContactNote::factory()->create([
            'contact_id' => $contact->id,
            'recorded_by' => $user->id,
            'note' => 'Spoke with the contact about the next uniform order.',
        ]);

        $this->assertDatabaseHas('contact_notes', [
            'id' => $note->id,
            'contact_id' => $contact->id,
            'recorded_by' => $user->id,
            'note' => 'Spoke with the contact about the next uniform order.',
        ]);
    }

    public function test_contact_note_belongs_to_contact(): void
    {
        $contact = Contact::factory()->create();

        $note = ContactNote::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue(
            $note->contact->is($contact)
        );
    }

    public function test_contact_note_belongs_to_recording_user(): void
    {
        $user = User::factory()->create();

        $note = ContactNote::factory()->create([
            'recorded_by' => $user->id,
        ]);

        $this->assertTrue(
            $note->recorder->is($user)
        );
    }

    public function test_contact_has_historical_interaction_notes(): void
    {
        $contact = Contact::factory()->create();

        $first = ContactNote::factory()->create([
            'contact_id' => $contact->id,
            'note' => 'First interaction.',
        ]);

        $second = ContactNote::factory()->create([
            'contact_id' => $contact->id,
            'note' => 'Second interaction.',
        ]);

        $this->assertCount(2, $contact->interactionNotes);
        $this->assertTrue(
            $contact->interactionNotes->contains($first)
        );
        $this->assertTrue(
            $contact->interactionNotes->contains($second)
        );
    }

    public function test_user_can_retrieve_contact_notes_they_recorded(): void
    {
        $user = User::factory()->create();

        $note = ContactNote::factory()->create([
            'recorded_by' => $user->id,
        ]);

        $this->assertTrue(
            $user->contactNotesRecorded->contains($note)
        );
    }

    public function test_recording_user_can_be_deleted_without_deleting_contact_note(): void
    {
        $user = User::factory()->create();

        $note = ContactNote::factory()->create([
            'recorded_by' => $user->id,
        ]);

        $user->delete();

        $this->assertDatabaseHas('contact_notes', [
            'id' => $note->id,
            'recorded_by' => null,
        ]);
    }

    public function test_deleting_contact_cascades_to_contact_notes(): void
    {
        $contact = Contact::factory()->create();

        $note = ContactNote::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $contact->forceDelete();

        $this->assertDatabaseMissing('contact_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_contact_note_uses_soft_deletes(): void
    {
        $note = ContactNote::factory()->create();

        $note->delete();

        $this->assertSoftDeleted('contact_notes', [
            'id' => $note->id,
        ]);

        $this->assertDatabaseHas('contact_notes', [
            'id' => $note->id,
        ]);
    }
}
