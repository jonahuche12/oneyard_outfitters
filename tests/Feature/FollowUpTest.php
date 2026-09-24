<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_up_can_be_created_for_an_organization(): void
    {
        $organization = Organization::factory()->create();

        $followUp = FollowUp::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->assertDatabaseHas('follow_ups', [
            'id' => $followUp->id,
            'organization_id' => $organization->id,
        ]);
    }

    public function test_follow_up_belongs_to_organization(): void
    {
        $organization = Organization::factory()->create();

        $followUp = FollowUp::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->assertTrue(
            $followUp->organization->is($organization)
        );
    }

    public function test_organization_retrieves_follow_ups(): void
    {
        $organization = Organization::factory()->create();

        FollowUp::factory()
            ->count(3)
            ->create([
                'organization_id' => $organization->id,
            ]);

        $this->assertCount(
            3,
            $organization->followUps
        );
    }

    public function test_follow_up_can_be_associated_with_a_contact(): void
    {
        $contact = Contact::factory()->create();

        $followUp = FollowUp::factory()
            ->forContact($contact)
            ->create();

        $this->assertTrue(
            $followUp->contact->is($contact)
        );

        $this->assertSame(
            $contact->organization_id,
            $followUp->organization_id
        );
    }

    public function test_contact_retrieves_follow_ups(): void
    {
        $contact = Contact::factory()->create();

        FollowUp::factory()
            ->count(2)
            ->forContact($contact)
            ->create();

        $this->assertCount(
            2,
            $contact->followUps
        );
    }

    public function test_follow_up_belongs_to_recording_user(): void
    {
        $user = User::factory()->create();

        $followUp = FollowUp::factory()->create([
            'recorded_by' => $user->id,
        ]);

        $this->assertTrue(
            $followUp->recordedBy->is($user)
        );
    }

    public function test_user_retrieves_follow_ups_they_recorded(): void
    {
        $user = User::factory()->create();

        FollowUp::factory()
            ->count(2)
            ->create([
                'recorded_by' => $user->id,
            ]);

        $this->assertCount(
            2,
            $user->followUpsRecorded
        );
    }

    public function test_follow_up_dates_are_cast_to_datetime(): void
    {
        $followUp = FollowUp::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Support\Carbon::class,
            $followUp->follow_up_date
        );

        if ($followUp->next_follow_up_date !== null) {
            $this->assertInstanceOf(
                \Illuminate\Support\Carbon::class,
                $followUp->next_follow_up_date
            );
        }
    }

    public function test_follow_up_is_open_by_default(): void
    {
        $followUp = FollowUp::factory()->create();

        $this->assertSame(
            'open',
            $followUp->status
        );
    }

    public function test_follow_up_can_be_soft_deleted(): void
    {
        $followUp = FollowUp::factory()->create();

        $followUp->delete();

        $this->assertSoftDeleted(
            'follow_ups',
            ['id' => $followUp->id]
        );

        $this->assertNull(
            FollowUp::find($followUp->id)
        );

        $this->assertNotNull(
            FollowUp::withTrashed()->find($followUp->id)
        );
    }

    public function test_hard_deleting_organization_cascades_to_follow_ups(): void
    {
        $organization = Organization::factory()->create();

        $followUp = FollowUp::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $organization->forceDelete();

        $this->assertDatabaseMissing(
            'follow_ups',
            ['id' => $followUp->id]
        );
    }

    public function test_deleting_contact_preserves_follow_up(): void
    {
        $contact = Contact::factory()->create();

        $followUp = FollowUp::factory()
            ->forContact($contact)
            ->create();

        $contact->forceDelete();

        $followUp->refresh();

        $this->assertNull(
            $followUp->contact_id
        );

        $this->assertDatabaseHas('follow_ups', [
            'id' => $followUp->id,
            'contact_id' => null,
        ]);
    }

    public function test_deleting_recording_user_preserves_follow_up(): void
    {
        $user = User::factory()->create();

        $followUp = FollowUp::factory()->create([
            'recorded_by' => $user->id,
        ]);

        $user->delete();

        $followUp->refresh();

        $this->assertNull(
            $followUp->recorded_by
        );

        $this->assertDatabaseHas('follow_ups', [
            'id' => $followUp->id,
            'recorded_by' => null,
        ]);
    }
}