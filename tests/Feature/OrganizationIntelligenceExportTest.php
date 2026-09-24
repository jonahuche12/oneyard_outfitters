<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Organization;
use App\Models\ProductSpecification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationIntelligenceExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_download_organization_intelligence_export(): void
    {
        $role = Role::factory()->create([
            'slug' => 'super-admin',
        ]);

        $permission = \App\Models\Permission::factory()->create([
            'slug' => 'organizations.export',
        ]);

        $role->permissions()->attach($permission);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->roles()->attach($role);

        $organization = Organization::factory()->create([
            'organization_code' => 'ORG-000001',
            'name' => 'Example Academy',
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $assessment = Assessment::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $followUp = FollowUp::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $productSpecification = ProductSpecification::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'organizations.export-intelligence',
                    $organization
                )
            );

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/json; charset=UTF-8'
        );
        $response->assertHeader(
            'Content-Disposition',
            'attachment; filename=org-000001-intelligence-export.json'
        );

        $payload = json_decode(
            $response->streamedContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            'oneyard_organization_intelligence',
            $payload['export_type']
        );

        $this->assertSame(
            '1.0',
            $payload['export_version']
        );

        $this->assertSame(
            'ORG-000001',
            $payload['organization']['code']
        );

        $this->assertCount(1, $payload['contacts']);
        $this->assertSame($contact->id, $payload['contacts'][0]['id']);

        $this->assertCount(1, $payload['assessments']);
        $this->assertSame(
            $assessment->id,
            $payload['assessments'][0]['id']
        );

        $this->assertCount(1, $payload['follow_ups']);
        $this->assertSame(
            $followUp->id,
            $payload['follow_ups'][0]['id']
        );

        $this->assertCount(1, $payload['product_specifications']);
        $this->assertSame(
            $productSpecification->id,
            $payload['product_specifications'][0]['id']
        );

        $this->assertArrayNotHasKey('artifacts', $payload);
    }
}
