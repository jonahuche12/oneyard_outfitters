<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationArtifact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductSpecificationArtifactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RbacSeeder::class);

        $this->user = User::factory()->create([
            'email' => 'sales-artifact-test@oneyard.test',
            'is_active' => true,
        ]);

        $this->user->roles()->attach(
            \App\Models\Role::where('slug', 'sales')->first()
        );
    }

    public function test_artifact_upload_version_download_and_soft_delete_lifecycle(): void
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();

        $specification = ProductSpecification::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $firstUpload = UploadedFile::fake()->create(
            'uniform-design-v1.pdf',
            100,
            'application/pdf'
        );

        $response = $this->post(
            route('product-specification-artifacts.store', $specification),
            [
                'artifact_type' => 'design',
                'title' => 'Uniform Design Version 1',
                'description' => 'Initial approved design reference.',
                'file' => $firstUpload,
            ]
        );

        $response->assertRedirect(
            route('product-specifications.show', $specification)
        );

        $firstArtifact = ProductSpecificationArtifact::query()
            ->where('product_specification_id', $specification->id)
            ->firstOrFail();

        $this->assertTrue($firstArtifact->is_current);
        $this->assertSame('design', $firstArtifact->artifact_type);
        $this->assertSame('Uniform Design Version 1', $firstArtifact->title);

        Storage::disk('local')->assertExists($firstArtifact->file_path);

        $secondUpload = UploadedFile::fake()->create(
            'uniform-design-v2.pdf',
            120,
            'application/pdf'
        );

        $response = $this->post(
            route('product-specification-artifacts.store', $specification),
            [
                'artifact_type' => 'design',
                'title' => 'Uniform Design Version 2',
                'description' => 'Updated design reference.',
                'file' => $secondUpload,
            ]
        );

        $response->assertRedirect(
            route('product-specifications.show', $specification)
        );

        $firstArtifact->refresh();

        $secondArtifact = ProductSpecificationArtifact::query()
            ->where('product_specification_id', $specification->id)
            ->where('title', 'Uniform Design Version 2')
            ->firstOrFail();

        $this->assertFalse($firstArtifact->is_current);
        $this->assertTrue($secondArtifact->is_current);

        $this->assertSame(
            2,
            ProductSpecificationArtifact::query()
                ->where('product_specification_id', $specification->id)
                ->count()
        );

        Storage::disk('local')->assertExists($firstArtifact->file_path);
        Storage::disk('local')->assertExists($secondArtifact->file_path);

        $download = $this->get(
            route('product-specification-artifacts.download', $secondArtifact)
        );

        $download->assertOk();

        $delete = $this->delete(
            route('product-specification-artifacts.destroy', $secondArtifact)
        );

        $delete->assertRedirect(
            route('product-specifications.show', $specification)
        );

        $this->assertSoftDeleted(
            'product_specification_artifacts',
            ['id' => $secondArtifact->id]
        );

        Storage::disk('local')->assertExists($secondArtifact->file_path);

        $this->assertDatabaseHas(
            'product_specification_artifacts',
            [
                'id' => $firstArtifact->id,
                'is_current' => false,
                'deleted_at' => null,
            ]
        );
    }
}
