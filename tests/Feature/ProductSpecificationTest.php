<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationArtifact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSpecificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_specification_can_be_created_for_an_organization(): void
    {
        $organization = Organization::factory()->create();

        $specification = ProductSpecification::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->assertDatabaseHas('product_specifications', [
            'id' => $specification->id,
            'organization_id' => $organization->id,
            'item_name' => $specification->item_name,
        ]);
    }

    public function test_product_specification_belongs_to_organization(): void
    {
        $specification = ProductSpecification::factory()->create();

        $this->assertTrue(
            $specification->organization->is(
                Organization::find($specification->organization_id)
            )
        );
    }

    public function test_organization_retrieves_product_specifications(): void
    {
        $organization = Organization::factory()->create();

        ProductSpecification::factory()
            ->count(3)
            ->create([
                'organization_id' => $organization->id,
            ]);

        $this->assertCount(
            3,
            $organization->productSpecifications
        );
    }

    public function test_product_specification_can_have_an_optional_contact(): void
    {
        $contact = Contact::factory()->create();

        $specification = ProductSpecification::factory()
            ->forContact($contact)
            ->create();

        $this->assertTrue(
            $specification->contact->is($contact)
        );

        $this->assertSame(
            $contact->organization_id,
            $specification->organization_id
        );
    }

    public function test_contact_retrieves_product_specifications(): void
    {
        $contact = Contact::factory()->create();

        ProductSpecification::factory()
            ->count(2)
            ->forContact($contact)
            ->create();

        $this->assertCount(
            2,
            $contact->productSpecifications
        );
    }

    public function test_product_specification_belongs_to_creator(): void
    {
        $user = User::factory()->create();

        $specification = ProductSpecification::factory()->create([
            'created_by' => $user->id,
        ]);

        $this->assertTrue(
            $specification->createdBy->is($user)
        );
    }

    public function test_user_retrieves_product_specifications_created(): void
    {
        $user = User::factory()->create();

        ProductSpecification::factory()
            ->count(2)
            ->create([
                'created_by' => $user->id,
            ]);

        $this->assertCount(
            2,
            $user->productSpecificationsCreated
        );
    }

    public function test_specification_date_is_cast_to_date(): void
    {
        $specification = ProductSpecification::factory()->create([
            'specification_date' => '2026-09-21',
        ]);

        $this->assertInstanceOf(
            \Illuminate\Support\Carbon::class,
            $specification->specification_date
        );

        $this->assertSame(
            '2026-09-21',
            $specification->specification_date->format('Y-m-d')
        );
    }

    public function test_unit_price_is_cast_to_decimal(): void
    {
        $specification = ProductSpecification::factory()->create([
            'unit_price' => 12500.50,
        ]);

        $this->assertSame(
            '12500.50',
            $specification->unit_price
        );
    }

    public function test_price_updated_at_is_cast_to_date(): void
    {
        $specification = ProductSpecification::factory()->create([
            'price_updated_at' => '2026-09-21',
        ]);

        $this->assertInstanceOf(
            \Illuminate\Support\Carbon::class,
            $specification->price_updated_at
        );

        $this->assertSame(
            '2026-09-21',
            $specification->price_updated_at->format('Y-m-d')
        );
    }

    public function test_draft_is_the_default_status(): void
    {
        $specification = ProductSpecification::factory()->create();

        $this->assertSame(
            'draft',
            $specification->status
        );
    }

    public function test_product_specification_can_be_soft_deleted(): void
    {
        $specification = ProductSpecification::factory()->create();

        $specification->delete();

        $this->assertSoftDeleted(
            'product_specifications',
            ['id' => $specification->id]
        );

        $this->assertNull(
            ProductSpecification::find($specification->id)
        );

        $this->assertNotNull(
            ProductSpecification::withTrashed()
                ->find($specification->id)
        );
    }

    public function test_hard_deleting_organization_cascades_specifications(): void
    {
        $organization = Organization::factory()->create();

        $specification = ProductSpecification::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $organization->forceDelete();

        $this->assertDatabaseMissing(
            'product_specifications',
            ['id' => $specification->id]
        );
    }

    public function test_deleting_contact_preserves_specification_and_nulls_contact(): void
    {
        $contact = Contact::factory()->create();

        $specification = ProductSpecification::factory()
            ->forContact($contact)
            ->create();

        $contact->forceDelete();

        $this->assertDatabaseHas('product_specifications', [
            'id' => $specification->id,
            'contact_id' => null,
        ]);
    }

    public function test_deleting_creator_preserves_specification_and_nulls_creator(): void
    {
        $user = User::factory()->create();

        $specification = ProductSpecification::factory()->create([
            'created_by' => $user->id,
        ]);

        $user->forceDelete();

        $this->assertDatabaseHas('product_specifications', [
            'id' => $specification->id,
            'created_by' => null,
        ]);
    }

    public function test_specification_can_have_multiple_artifacts(): void
    {
        $specification = ProductSpecification::factory()->create();

        ProductSpecificationArtifact::factory()
            ->count(3)
            ->create([
                'product_specification_id' => $specification->id,
            ]);

        $this->assertCount(
            3,
            $specification->artifacts
        );
    }

    public function test_artifact_belongs_to_specification(): void
    {
        $specification = ProductSpecification::factory()->create();

        $artifact = ProductSpecificationArtifact::factory()->create([
            'product_specification_id' => $specification->id,
        ]);

        $this->assertTrue(
            $artifact->productSpecification->is($specification)
        );
    }

    public function test_artifact_belongs_to_uploader(): void
    {
        $user = User::factory()->create();

        $artifact = ProductSpecificationArtifact::factory()->create([
            'uploaded_by' => $user->id,
        ]);

        $this->assertTrue(
            $artifact->uploadedBy->is($user)
        );
    }

    public function test_uploader_retrieves_uploaded_artifacts(): void
    {
        $user = User::factory()->create();

        ProductSpecificationArtifact::factory()
            ->count(2)
            ->create([
                'uploaded_by' => $user->id,
            ]);

        $this->assertCount(
            2,
            $user->specificationArtifactsUploaded
        );
    }

    public function test_artifact_is_current_is_cast_to_boolean(): void
    {
        $artifact = ProductSpecificationArtifact::factory()->create([
            'is_current' => true,
        ]);

        $this->assertIsBool(
            $artifact->is_current
        );

        $this->assertTrue(
            $artifact->is_current
        );
    }

    public function test_artifact_can_be_soft_deleted(): void
    {
        $artifact = ProductSpecificationArtifact::factory()->create();

        $artifact->delete();

        $this->assertSoftDeleted(
            'product_specification_artifacts',
            ['id' => $artifact->id]
        );
    }

    public function test_hard_deleting_specification_cascades_artifacts(): void
    {
        $specification = ProductSpecification::factory()->create();

        $artifact = ProductSpecificationArtifact::factory()->create([
            'product_specification_id' => $specification->id,
        ]);

        $specification->forceDelete();

        $this->assertDatabaseMissing(
            'product_specification_artifacts',
            ['id' => $artifact->id]
        );
    }

    public function test_deleting_artifact_uploader_preserves_artifact_and_nulls_uploader(): void
    {
        $user = User::factory()->create();

        $artifact = ProductSpecificationArtifact::factory()->create([
            'uploaded_by' => $user->id,
        ]);

        $user->forceDelete();

        $this->assertDatabaseHas('product_specification_artifacts', [
            'id' => $artifact->id,
            'uploaded_by' => null,
        ]);
    }
}