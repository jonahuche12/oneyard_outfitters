<?php

namespace Tests\Feature;

use App\Models\Role;
use Database\Seeders\AssessmentRbacSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentRbacTest extends TestCase
{
    use RefreshDatabase;

    private function roleHas(string $roleSlug, string $permission): bool
    {
        return Role::where('slug', $roleSlug)
            ->firstOrFail()
            ->permissions()
            ->where('slug', $permission)
            ->exists();
    }

    public function test_admin_sales_and_delivery_can_record_assessments(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(AssessmentRbacSeeder::class);

        foreach (['super-admin', 'admin', 'sales', 'delivery'] as $slug) {
            $this->assertTrue(
                $this->roleHas($slug, 'assessments.create'),
                "{$slug} should have assessments.create"
            );
            $this->assertTrue(
                $this->roleHas($slug, 'assessments.view'),
                "{$slug} should have assessments.view"
            );
        }
    }

    public function test_delivery_cannot_update_assessments_but_admin_and_sales_can(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(AssessmentRbacSeeder::class);

        $this->assertFalse($this->roleHas('delivery', 'assessments.update'));
        $this->assertTrue($this->roleHas('admin', 'assessments.update'));
        $this->assertTrue($this->roleHas('sales', 'assessments.update'));
    }

    public function test_roles_without_assessment_recording_cannot_create(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(AssessmentRbacSeeder::class);

        foreach (['procurement', 'production', 'quality-control', 'finance'] as $slug) {
            $this->assertFalse(
                $this->roleHas($slug, 'assessments.create'),
                "{$slug} should not have assessments.create"
            );
        }
    }

    public function test_rbac_seeder_alone_strips_supplementary_permissions(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(AssessmentRbacSeeder::class);
        $this->seed(RbacSeeder::class);

        $this->assertFalse(
            $this->roleHas('sales', 'assessments.create'),
            'Running RbacSeeder alone removes assessment permissions; '
            . 'always reseed with the full DatabaseSeeder.'
        );
    }
}
