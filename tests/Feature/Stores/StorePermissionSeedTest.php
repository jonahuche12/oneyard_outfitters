<?php

namespace Tests\Feature\Stores;

use App\Models\Role;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePermissionSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_manage_is_defined_and_held_by_admin_and_super_admin(): void
    {
        $this->seed(RbacSeeder::class);

        $this->assertDatabaseHas('permissions', ['slug' => 'stores.manage']);

        foreach (['admin', 'super-admin'] as $slug) {
            $role = Role::query()->where('slug', $slug)->firstOrFail();

            $this->assertTrue(
                $role->permissions()->where('slug', 'stores.manage')->exists(),
                $slug.' should hold stores.manage'
            );
        }
    }

    public function test_no_other_role_holds_stores_manage(): void
    {
        $this->seed(RbacSeeder::class);

        $holders = Role::query()
            ->whereHas('permissions', fn ($query) => $query->where('slug', 'stores.manage'))
            ->pluck('slug')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['admin', 'super-admin'], $holders);
    }
}
