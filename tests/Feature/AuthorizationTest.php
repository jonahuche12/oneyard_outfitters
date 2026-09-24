<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([
            'web',
            'auth',
            'permission:organizations.view',
        ])->get('/test/organizations', function () {
            return response()->json([
                'message' => 'authorized',
            ]);
        });
    }

    public function test_guest_cannot_access_permission_protected_route(): void
    {
        $response = $this->getJson('/test/organizations');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_without_permission_receives_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/test/organizations');

        $response->assertForbidden();
    }

    public function test_authenticated_user_with_permission_can_access_protected_route(): void
    {
        $user = User::factory()->create();

        $role = Role::create([
            'name' => 'Sales',
            'slug' => 'sales',
            'description' => 'Sales staff.',
        ]);

        $permission = Permission::create([
            'name' => 'View Organizations',
            'slug' => 'organizations.view',
            'description' => 'View organizations.',
        ]);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $response = $this
            ->actingAs($user)
            ->getJson('/test/organizations');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'authorized',
            ]);
    }

    public function test_user_with_multiple_roles_can_use_permission_from_any_role(): void
    {
        $user = User::factory()->create();

        $sales = Role::create([
            'name' => 'Sales',
            'slug' => 'sales',
        ]);

        $finance = Role::create([
            'name' => 'Finance',
            'slug' => 'finance',
        ]);

        $permission = Permission::create([
            'name' => 'View Organizations',
            'slug' => 'organizations.view',
        ]);

        $finance->permissions()->attach($permission);

        $user->roles()->attach([
            $sales->id,
            $finance->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/test/organizations');

        $response->assertOk();
    }

    public function test_has_any_permission_returns_true_when_one_permission_exists(): void
    {
        $user = User::factory()->create();

        $role = Role::create([
            'name' => 'Sales',
            'slug' => 'sales',
        ]);

        $permission = Permission::create([
            'name' => 'View Organizations',
            'slug' => 'organizations.view',
        ]);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->assertTrue(
            $user->hasAnyPermission([
                'organizations.delete',
                'organizations.view',
            ])
        );
    }

    public function test_has_any_permission_returns_false_when_none_exist(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(
            $user->hasAnyPermission([
                'organizations.view',
                'organizations.delete',
            ])
        );
    }

    public function test_has_all_permissions_requires_every_permission(): void
    {
        $user = User::factory()->create();

        $role = Role::create([
            'name' => 'Sales',
            'slug' => 'sales',
        ]);

        $view = Permission::create([
            'name' => 'View Organizations',
            'slug' => 'organizations.view',
        ]);

        $create = Permission::create([
            'name' => 'Create Organizations',
            'slug' => 'organizations.create',
        ]);

        $role->permissions()->attach([
            $view->id,
            $create->id,
        ]);

        $user->roles()->attach($role);

        $this->assertTrue(
            $user->hasAllPermissions([
                'organizations.view',
                'organizations.create',
            ])
        );

        $this->assertFalse(
            $user->hasAllPermissions([
                'organizations.view',
                'organizations.delete',
            ])
        );
    }
}