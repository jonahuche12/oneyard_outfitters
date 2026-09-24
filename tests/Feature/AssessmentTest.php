<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_can_be_created_for_an_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $assessment = Assessment::factory()->create([
            'organization_id' => $organization->id,
            'assessed_by' => $user->id,
        ]);

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'organization_id' => $organization->id,
            'assessed_by' => $user->id,
        ]);
    }

    public function test_assessment_belongs_to_organization(): void
    {
        $organization = Organization::factory()->create();

        $assessment = Assessment::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->assertTrue(
            $assessment->organization->is($organization)
        );
    }

    public function test_organization_retrieves_assessments(): void
    {
        $organization = Organization::factory()->create();

        Assessment::factory()
            ->count(3)
            ->create([
                'organization_id' => $organization->id,
            ]);

        $this->assertCount(
            3,
            $organization->assessments
        );
    }

    public function test_assessment_belongs_to_assessing_user(): void
    {
        $user = User::factory()->create();

        $assessment = Assessment::factory()->create([
            'assessed_by' => $user->id,
        ]);

        $this->assertTrue(
            $assessment->assessedBy->is($user)
        );
    }

    public function test_user_retrieves_assessments_they_conducted(): void
    {
        $user = User::factory()->create();

        Assessment::factory()
            ->count(2)
            ->create([
                'assessed_by' => $user->id,
            ]);

        $this->assertCount(
            2,
            $user->assessments
        );
    }

    public function test_assessment_date_is_cast_to_date(): void
    {
        $assessment = Assessment::factory()->create([
            'assessment_date' => '2026-09-21',
        ]);

        $this->assertInstanceOf(
            \Illuminate\Support\Carbon::class,
            $assessment->assessment_date
        );
    }

    public function test_estimated_budget_is_cast_to_decimal(): void
    {
        $assessment = Assessment::factory()->create([
            'estimated_budget' => 1500000.50,
        ]);

        $this->assertSame(
            '1500000.50',
            $assessment->estimated_budget
        );
    }

    public function test_assessment_is_draft_by_default(): void
    {
        $assessment = Assessment::factory()->create();

        $this->assertSame(
            'draft',
            $assessment->status
        );
    }

    public function test_assessment_can_be_soft_deleted(): void
    {
        $assessment = Assessment::factory()->create();

        $assessment->delete();

        $this->assertSoftDeleted(
            'assessments',
            ['id' => $assessment->id]
        );

        $this->assertNull(
            Assessment::find($assessment->id)
        );

        $this->assertNotNull(
            Assessment::withTrashed()->find($assessment->id)
        );
    }

    public function test_hard_deleting_organization_cascades_to_assessments(): void
    {
        $organization = Organization::factory()->create();

        $assessment = Assessment::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $organization->forceDelete();

        $this->assertDatabaseMissing(
            'assessments',
            ['id' => $assessment->id]
        );
    }

    public function test_deleting_assessed_user_preserves_assessment(): void
    {
        $user = User::factory()->create();

        $assessment = Assessment::factory()->create([
            'assessed_by' => $user->id,
        ]);

        $user->delete();

        $assessment->refresh();

        $this->assertNull($assessment->assessed_by);

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'assessed_by' => null,
        ]);
    }
}