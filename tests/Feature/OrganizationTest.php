<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_can_be_created(): void
    {
        $organization = Organization::factory()->create([
            'organization_code' => 'ORG-ABA-000001',
            'name' => 'Example Academy',
            'type' => 'school',
            'ownership' => 'private',
        ]);

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'organization_code' => 'ORG-ABA-000001',
            'name' => 'Example Academy',
            'type' => 'school',
            'ownership' => 'private',
        ]);
    }

    public function test_organization_code_must_be_unique(): void
    {
        Organization::factory()->create([
            'organization_code' => 'ORG-ABA-000001',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Organization::factory()->create([
            'organization_code' => 'ORG-ABA-000001',
        ]);
    }

    public function test_organization_can_be_soft_deleted(): void
    {
        $organization = Organization::factory()->create();

        $organization->delete();

        $this->assertSoftDeleted('organizations', [
            'id' => $organization->id,
        ]);

        $this->assertNull(
            Organization::find($organization->id)
        );

        $this->assertNotNull(
            Organization::withTrashed()->find($organization->id)
        );
    }

    public function test_organization_is_active_by_default(): void
    {
        $organization = Organization::factory()->create();

        $this->assertTrue($organization->is_active);
    }

    public function test_organization_casts_is_active_to_boolean(): void
    {
        $organization = Organization::factory()->create([
            'is_active' => false,
        ]);

        $organization->refresh();

        $this->assertIsBool($organization->is_active);
        $this->assertFalse($organization->is_active);
    }
}