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

class StoreHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected(): void
    {
        $store = $this->makeStore();

        $this->get(route('stores.index'))->assertRedirect();
        $this->get(route('stores.show', $store))->assertRedirect();
    }

    public function test_manager_sees_stores_with_the_warehouse_first_and_coordinator_state(): void
    {
        $manager = $this->manager();

        $this->makeStore(['name' => 'General Warehouse', 'code' => 'WH', 'type' => Store::TYPE_WAREHOUSE]);
        $alpha = $this->makeStore(['name' => 'Alpha Store', 'code' => 'ALPHA']);
        $this->makeStore(['name' => 'Beta Store', 'code' => 'BETA']);

        $ada = $this->staffUser(true, 'Ada Okafor');
        $this->assign($alpha, $ada, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($manager)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertSeeInOrder(['General Warehouse', 'Alpha Store', 'Beta Store'])
            ->assertSee('Ada Okafor')
            ->assertSee('No coordinator');
    }

    public function test_search_filters_by_name_or_code(): void
    {
        $manager = $this->manager();

        $this->makeStore(['name' => 'Alpha Store', 'code' => 'ALPHA']);
        $this->makeStore(['name' => 'Beta Store', 'code' => 'BETA']);

        $this->actingAs($manager)
            ->get(route('stores.index', ['search' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Store')
            ->assertDontSee('Beta Store');

        $this->actingAs($manager)
            ->get(route('stores.index', ['search' => 'BETA']))
            ->assertOk()
            ->assertSee('Beta Store')
            ->assertDontSee('Alpha Store');
    }

    public function test_users_without_the_permission_are_denied_everywhere(): void
    {
        $user = $this->staffUser();
        $store = $this->makeStore(['name' => 'Alpha Store']);

        $this->actingAs($user)->get(route('stores.index'))->assertForbidden();
        $this->actingAs($user)->get(route('stores.create'))->assertForbidden();
        $this->actingAs($user)->post(route('stores.store'), $this->storeInput())->assertForbidden();
        $this->actingAs($user)->get(route('stores.show', $store))->assertForbidden();
        $this->actingAs($user)->get(route('stores.edit', $store))->assertForbidden();
        $this->actingAs($user)
            ->put(route('stores.update', $store), ['name' => 'Renamed Store', 'code' => 'RENAMED'])
            ->assertForbidden();
        $this->actingAs($user)->patch(route('stores.deactivate', $store))->assertForbidden();
        $this->actingAs($user)->patch(route('stores.activate', $store))->assertForbidden();

        $this->assertSame(1, Store::query()->count());
        $this->assertSame('Alpha Store', $store->fresh()->name);
        $this->assertTrue($store->fresh()->is_active);
    }

    public function test_manager_creates_a_store_and_is_redirected_to_it(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager)->post(route('stores.store'), [
            'name' => 'Ikeja Fabrics',
            'code' => 'ikj-01',
            'type' => Store::TYPE_STORE,
            'address' => '12 Market Road',
            'phone' => '0803 000 0000',
        ]);

        $store = Store::query()->where('code', 'IKJ-01')->firstOrFail();

        $response->assertRedirect(route('stores.show', $store))->assertSessionHas('success');

        $this->assertSame('Ikeja Fabrics', $store->name);
        $this->assertSame(Store::TYPE_STORE, $store->type);
        $this->assertSame($manager->id, $store->created_by);
    }

    public function test_invalid_creates_are_rejected_with_errors(): void
    {
        $manager = $this->manager();

        $this->makeStore(['name' => 'Ikeja Fabrics', 'code' => 'IKJ01']);

        $this->actingAs($manager)
            ->post(route('stores.store'), $this->storeInput(['name' => 'Ikeja Fabrics']))
            ->assertSessionHasErrors('name');

        $this->actingAs($manager)
            ->post(route('stores.store'), $this->storeInput(['code' => 'ikj01']))
            ->assertSessionHasErrors('code');

        $this->makeStore(['name' => 'General Warehouse', 'code' => 'WH', 'type' => Store::TYPE_WAREHOUSE]);

        $this->actingAs($manager)
            ->post(route('stores.store'), $this->storeInput(['type' => Store::TYPE_WAREHOUSE]))
            ->assertSessionHasErrors('type');

        $this->assertSame(2, Store::query()->count());
    }

    public function test_create_page_explains_when_a_warehouse_already_exists(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)
            ->get(route('stores.create'))
            ->assertOk()
            ->assertDontSee('A general warehouse already exists');

        $this->makeStore(['name' => 'General Warehouse', 'code' => 'WH', 'type' => Store::TYPE_WAREHOUSE]);

        $this->actingAs($manager)
            ->get(route('stores.create'))
            ->assertOk()
            ->assertSee('A general warehouse already exists');
    }

    public function test_manager_updates_a_store_but_the_type_stays_fixed(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore(['name' => 'Alpha Store', 'code' => 'ALPHA']);

        $this->actingAs($manager)
            ->put(route('stores.update', $store), [
                'name' => ' Lekki Fabrics ',
                'code' => 'lek01',
                'address' => 'New address',
                'phone' => '',
                'type' => Store::TYPE_WAREHOUSE,
            ])
            ->assertRedirect(route('stores.show', $store))
            ->assertSessionHas('success');

        $fresh = $store->fresh();

        $this->assertSame('Lekki Fabrics', $fresh->name);
        $this->assertSame('LEK01', $fresh->code);
        $this->assertSame('New address', $fresh->address);
        $this->assertNull($fresh->phone);
        $this->assertSame(Store::TYPE_STORE, $fresh->type);
    }

    public function test_manager_deactivates_and_reactivates_a_store(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();

        $this->actingAs($manager)
            ->patch(route('stores.deactivate', $store))
            ->assertRedirect(route('stores.show', $store))
            ->assertSessionHas('success');

        $this->assertFalse($store->fresh()->is_active);

        $this->actingAs($manager)
            ->patch(route('stores.activate', $store))
            ->assertRedirect(route('stores.show', $store));

        $this->assertTrue($store->fresh()->is_active);
    }

    public function test_the_warehouse_cannot_be_deactivated(): void
    {
        $manager = $this->manager();
        $warehouse = $this->makeStore(['name' => 'General Warehouse', 'code' => 'WH', 'type' => Store::TYPE_WAREHOUSE]);

        $this->actingAs($manager)
            ->patch(route('stores.deactivate', $warehouse))
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($warehouse->fresh()->is_active);
    }

    public function test_members_can_open_only_their_own_store_without_management_controls(): void
    {
        $own = $this->makeStore(['name' => 'Alpha Store', 'code' => 'ALPHA']);
        $other = $this->makeStore(['name' => 'Beta Store', 'code' => 'BETA']);
        $former = $this->makeStore(['name' => 'Gamma Store', 'code' => 'GAMMA']);

        $member = $this->staffUser();
        $this->assign($own, $member, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($member)
            ->get(route('stores.show', $own))
            ->assertOk()
            ->assertSee('Alpha Store')
            ->assertDontSee('Edit Store')
            ->assertDontSee('Assignment history');

        $this->actingAs($member)->get(route('stores.show', $other))->assertForbidden();
        $this->actingAs($member)->get(route('stores.index'))->assertForbidden();

        $ended = $this->staffUser();
        $this->assign($former, $ended, StoreStaff::ROLE_ASSISTANT, [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'ended_reason' => 'Moved to another store',
        ]);

        $this->actingAs($ended)->get(route('stores.show', $former))->assertForbidden();
    }

    public function test_manager_sees_staff_and_history_on_the_store_page(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore(['name' => 'Alpha Store', 'code' => 'ALPHA']);

        $this->assign($store, $this->staffUser(true, 'Ada Okafor'), StoreStaff::ROLE_COORDINATOR);
        $this->assign($store, $this->staffUser(true, 'Bola Adeyemi'), StoreStaff::ROLE_ASSISTANT);
        $this->assign($store, $this->staffUser(true, 'Chidi Eze'), StoreStaff::ROLE_ASSISTANT, [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'ended_reason' => 'Moved to another store',
            'ended_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('stores.show', $store))
            ->assertOk()
            ->assertSee('Ada Okafor')
            ->assertSee('Bola Adeyemi')
            ->assertSee('Assignment history')
            ->assertSee('Chidi Eze')
            ->assertSee('Moved to another store')
            ->assertSee('Edit Store');
    }

    public function test_general_store_page_supports_normal_staff_management(): void
    {
        $manager = $this->manager();
        $warehouse = $this->makeStore(['name' => 'General Store', 'code' => 'WH', 'type' => Store::TYPE_WAREHOUSE]);

        $this->actingAs($manager)
            ->get(route('stores.show', $warehouse))
            ->assertOk()
            ->assertSee('Store coordinator')
            ->assertSee('Assistants')
            ->assertSee('Assign coordinator')
            ->assertSee('Add assistant')
            ->assertSee('Assignment history')
            ->assertDontSee('operated by administrators')
            ->assertDontSee('Deactivate');
    }

    // -------------------------------------------------------------------- Helpers

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

    private function staffUser(bool $active = true, ?string $name = null): User
    {
        return User::factory()->create(array_filter([
            'is_active' => $active,
            'name' => $name,
        ], fn ($value) => $value !== null));
    }

    private function storeInput(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
            'type' => Store::TYPE_STORE,
            'address' => '12 Market Road',
            'phone' => '08030000000',
        ], $overrides);
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
