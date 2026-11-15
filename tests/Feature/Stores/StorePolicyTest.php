<?php

namespace Tests\Feature\Stores;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorePolicyTest extends TestCase
{
    use RefreshDatabase;

    private const MANAGEMENT_ABILITIES = ['update', 'toggleActive', 'manageStaff'];

    public function test_a_manager_is_allowed_every_store_ability(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();

        $this->assertTrue($manager->can('viewAny', Store::class));
        $this->assertTrue($manager->can('create', Store::class));
        $this->assertTrue($manager->can('view', $store));

        foreach (self::MANAGEMENT_ABILITIES as $ability) {
            $this->assertTrue($manager->can($ability, $store), $ability);
        }
    }

    public function test_an_inactive_manager_and_a_user_without_the_permission_are_denied(): void
    {
        $store = $this->makeStore();

        foreach ([$this->manager(false), $this->staffUser()] as $user) {
            $this->assertFalse($user->can('viewAny', Store::class));
            $this->assertFalse($user->can('create', Store::class));
            $this->assertFalse($user->can('view', $store));

            foreach (self::MANAGEMENT_ABILITIES as $ability) {
                $this->assertFalse($user->can($ability, $store), $ability);
            }
        }
    }

    public function test_active_members_can_view_only_their_own_store_and_cannot_manage(): void
    {
        $store = $this->makeStore();
        $other = $this->makeStore();

        foreach ([StoreStaff::ROLE_COORDINATOR, StoreStaff::ROLE_ASSISTANT] as $role) {
            $user = $this->staffUser();
            $this->assign($store, $user, $role);

            $this->assertTrue($user->can('view', $store), $role);
            $this->assertFalse($user->can('view', $other), $role);
            $this->assertFalse($user->can('viewAny', Store::class), $role);
            $this->assertFalse($user->can('create', Store::class), $role);

            foreach (self::MANAGEMENT_ABILITIES as $ability) {
                $this->assertFalse($user->can($ability, $store), $role.' '.$ability);
            }
        }
    }

    public function test_ended_and_inactive_members_cannot_view_the_store(): void
    {
        $store = $this->makeStore();

        $ended = $this->staffUser();
        $this->assign($store, $ended, StoreStaff::ROLE_ASSISTANT, [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'ended_reason' => 'Moved to another store',
        ]);

        $inactive = $this->staffUser(false);
        $this->assign($store, $inactive, StoreStaff::ROLE_ASSISTANT);

        $this->assertFalse($ended->can('view', $store));
        $this->assertFalse($inactive->can('view', $store));
    }

    private function manager(bool $active = true): User
    {
        $slug = 'store-manager-'.Str::lower(Str::random(6));

        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => 'Test role.']);
        $permission = Permission::firstOrCreate(
            ['slug' => 'stores.manage'],
            ['name' => 'Manage Stores', 'description' => 'Test.']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create(['is_active' => $active]);
        $user->roles()->attach($role);

        return $user;
    }

    private function staffUser(bool $active = true): User
    {
        return User::factory()->create(['is_active' => $active]);
    }

    private function makeStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
        ], $attributes));
    }

    private function assign(Store $store, User $user, string $role, array $attributes = []): StoreStaff
    {
        return StoreStaff::query()->create(array_merge([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now(),
        ], $attributes));
    }
}
