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

class StoreStaffHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_assigns_an_assistant(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore(['name' => 'Alpha Store']);
        $user = $this->staffUser(true, 'Bola Adeyemi');

        $this->actingAs($manager)
            ->post(route('stores.staff.assign', $store), ['user_id' => $user->id, 'role' => StoreStaff::ROLE_ASSISTANT])
            ->assertRedirect(route('stores.show', $store))
            ->assertSessionHas('success');

        $assignment = $user->fresh()->activeStoreAssignment;

        $this->assertNotNull($assignment);
        $this->assertSame($store->id, $assignment->store_id);
        $this->assertSame(StoreStaff::ROLE_ASSISTANT, $assignment->role);
        $this->assertSame($manager->id, $assignment->assigned_by);
    }

    public function test_assigning_a_coordinator_replaces_the_current_one(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();
        $first = $this->assign($store, $this->staffUser(), StoreStaff::ROLE_COORDINATOR);
        $newCoordinator = $this->staffUser();

        $this->actingAs($manager)
            ->post(route('stores.staff.assign', $store), ['user_id' => $newCoordinator->id, 'role' => StoreStaff::ROLE_COORDINATOR])
            ->assertRedirect(route('stores.show', $store));

        $this->assertNotNull($first->fresh()->ends_at);
        $this->assertSame($newCoordinator->id, $store->fresh()->coordinatorAssignment->user_id);
    }

    public function test_users_without_manage_rights_are_denied_on_every_staff_route(): void
    {
        $store = $this->makeStore();
        $other = $this->makeStore();
        $coordinatorUser = $this->staffUser();
        $coordinator = $this->assign($store, $coordinatorUser, StoreStaff::ROLE_COORDINATOR);
        $assistant = $this->assign($store, $this->staffUser(), StoreStaff::ROLE_ASSISTANT);
        $candidate = $this->staffUser();

        foreach ([$this->staffUser(), $coordinatorUser] as $actor) {
            $this->actingAs($actor)
                ->post(route('stores.staff.assign', $store), ['user_id' => $candidate->id, 'role' => StoreStaff::ROLE_ASSISTANT])
                ->assertForbidden();

            $this->actingAs($actor)
                ->post(route('stores.staff.move', [$store, $assistant]), ['to_store_id' => $other->id, 'role' => StoreStaff::ROLE_ASSISTANT])
                ->assertForbidden();

            $this->actingAs($actor)
                ->post(route('stores.staff.end', [$store, $assistant]), ['reason' => 'A perfectly valid reason.'])
                ->assertForbidden();
        }

        $this->assertTrue($coordinator->fresh()->isActive());
        $this->assertTrue($assistant->fresh()->isActive());
        $this->assertSame(2, StoreStaff::query()->count());
    }

    public function test_manager_moves_staff_to_another_store_and_history_is_kept(): void
    {
        $manager = $this->manager();
        $alpha = $this->makeStore(['name' => 'Alpha Store']);
        $beta = $this->makeStore(['name' => 'Beta Store']);
        $user = $this->staffUser();
        $old = $this->assign($alpha, $user, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($manager)
            ->post(route('stores.staff.move', [$alpha, $old]), ['to_store_id' => $beta->id, 'role' => StoreStaff::ROLE_ASSISTANT])
            ->assertRedirect(route('stores.show', $alpha))
            ->assertSessionHas('success');

        $this->assertNotNull($old->fresh()->ends_at);
        $this->assertSame('Reassigned to Beta Store.', $old->fresh()->ended_reason);
        $this->assertSame($beta->id, $user->fresh()->activeStoreAssignment->store_id);
        $this->assertSame(2, $user->storeAssignments()->count());
    }

    public function test_making_an_assistant_coordinator_replaces_the_coordinator(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();
        $currentCoordinator = $this->assign($store, $this->staffUser(), StoreStaff::ROLE_COORDINATOR);
        $assistantUser = $this->staffUser();
        $assistant = $this->assign($store, $assistantUser, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($manager)
            ->post(route('stores.staff.move', [$store, $assistant]), ['to_store_id' => $store->id, 'role' => StoreStaff::ROLE_COORDINATOR])
            ->assertRedirect(route('stores.show', $store))
            ->assertSessionHas('success');

        $this->assertSame('Role changed to coordinator.', $assistant->fresh()->ended_reason);
        $this->assertNotNull($currentCoordinator->fresh()->ends_at);
        $this->assertSame($assistantUser->id, $store->fresh()->coordinatorAssignment->user_id);
    }

    public function test_ending_an_assignment_needs_a_real_reason(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();
        $assignment = $this->assign($store, $this->staffUser(), StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($manager)
            ->post(route('stores.staff.end', [$store, $assignment]), ['reason' => 'short'])
            ->assertSessionHasErrors('reason');

        $this->assertTrue($assignment->fresh()->isActive());

        $this->actingAs($manager)
            ->post(route('stores.staff.end', [$store, $assignment]), ['reason' => 'Left the company in November.'])
            ->assertRedirect(route('stores.show', $store))
            ->assertSessionHas('success');

        $fresh = $assignment->fresh();

        $this->assertNotNull($fresh->ends_at);
        $this->assertSame('Left the company in November.', $fresh->ended_reason);
        $this->assertSame($manager->id, $fresh->ended_by);
    }

    public function test_an_assignment_from_another_store_in_the_url_is_not_found(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();
        $other = $this->makeStore();
        $assignment = $this->assign($store, $this->staffUser(), StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($manager)
            ->post(route('stores.staff.move', [$other, $assignment]), ['to_store_id' => $store->id, 'role' => StoreStaff::ROLE_ASSISTANT])
            ->assertNotFound();

        $this->actingAs($manager)
            ->post(route('stores.staff.end', [$other, $assignment]), ['reason' => 'A perfectly valid reason.'])
            ->assertNotFound();

        $this->assertTrue($assignment->fresh()->isActive());
    }

    public function test_a_stale_move_of_an_ended_assignment_is_rejected(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();
        $other = $this->makeStore();
        $ended = $this->assign($store, $this->staffUser(), StoreStaff::ROLE_ASSISTANT, [
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDay(),
            'ended_reason' => 'Left the business.',
        ]);

        $this->actingAs($manager)
            ->post(route('stores.staff.move', [$store, $ended]), ['to_store_id' => $other->id, 'role' => StoreStaff::ROLE_ASSISTANT])
            ->assertSessionHasErrors('assignment');

        $this->assertSame(1, StoreStaff::query()->count());
    }

    public function test_assign_validation_errors(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();
        $elsewhere = $this->staffUser();
        $this->assign($this->makeStore(), $elsewhere, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($manager)
            ->post(route('stores.staff.assign', $store), ['user_id' => $elsewhere->id, 'role' => StoreStaff::ROLE_ASSISTANT])
            ->assertSessionHasErrors('user_id');

        $this->actingAs($manager)
            ->post(route('stores.staff.assign', $store), ['user_id' => 999999, 'role' => StoreStaff::ROLE_ASSISTANT])
            ->assertSessionHasErrors('user_id');

        $this->actingAs($manager)
            ->post(route('stores.staff.assign', $store), ['user_id' => $this->staffUser()->id])
            ->assertSessionHasErrors('role');

        $this->assertSame(1, StoreStaff::query()->count());
    }

    public function test_the_store_page_offers_staff_controls_and_only_free_active_people(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore(['name' => 'Alpha Store']);
        $this->makeStore(['name' => 'Beta Store']);

        $this->staffUser(true, 'Zed Unassigned');
        $this->assign($this->makeStore(), $this->staffUser(true, 'Yolanda Elsewhere'), StoreStaff::ROLE_ASSISTANT);
        $this->staffUser(false, 'Xavier Inactive');

        $this->actingAs($manager)
            ->get(route('stores.show', $store))
            ->assertOk()
            ->assertSee('Assign coordinator')
            ->assertSee('Add assistant')
            ->assertSee('Zed Unassigned')
            ->assertDontSee('Yolanda Elsewhere')
            ->assertDontSee('Xavier Inactive')
            ->assertSee('Beta Store')
            ->assertSee('store-assign-modal');

        $this->assign($store, $this->staffUser(), StoreStaff::ROLE_COORDINATOR);
        $this->assign($store, $this->staffUser(), StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($manager)
            ->get(route('stores.show', $store))
            ->assertOk()
            ->assertSee('Replace coordinator')
            ->assertSee('Make coordinator')
            ->assertSee('End assignment');
    }

    public function test_members_see_no_staff_controls(): void
    {
        $store = $this->makeStore(['name' => 'Alpha Store']);
        $member = $this->staffUser();
        $this->assign($store, $member, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($member)
            ->get(route('stores.show', $store))
            ->assertOk()
            ->assertDontSee('data-store-modal-open', false)
            ->assertDontSee('store-assign-modal');
    }

    public function test_an_inactive_store_offers_no_assignment_and_rejects_it(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore(['name' => 'Alpha Store', 'is_active' => false]);
        $user = $this->staffUser();

        $this->actingAs($manager)
            ->get(route('stores.show', $store))
            ->assertOk()
            ->assertDontSee('Add assistant');

        $this->actingAs($manager)
            ->post(route('stores.staff.assign', $store), ['user_id' => $user->id, 'role' => StoreStaff::ROLE_ASSISTANT])
            ->assertSessionHasErrors('store');

        $this->assertSame(0, StoreStaff::query()->count());
    }

    public function test_a_rejected_assign_reopens_its_modal_with_the_error(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();
        $user = $this->staffUser(true, 'Bola Adeyemi');
        $this->assign($this->makeStore(), $user, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($manager)
            ->from(route('stores.show', $store))
            ->followingRedirects()
            ->post(route('stores.staff.assign', $store), [
                '_staff_modal' => 'assign',
                'user_id' => $user->id,
                'role' => StoreStaff::ROLE_ASSISTANT,
            ])
            ->assertOk()
            ->assertSee('data-reopen="1"', false)
            ->assertSee('is already assigned to');
    }

    public function test_my_store_redirects_members_and_unassigned_users(): void
    {
        $store = $this->makeStore();
        $member = $this->staffUser();
        $this->assign($store, $member, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($member)
            ->get(route('my-store'))
            ->assertRedirect(route('stores.show', $store));

        $this->actingAs($this->staffUser())
            ->get(route('my-store'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_the_sidebar_shows_my_store_only_to_assigned_staff(): void
    {
        $store = $this->makeStore();
        $member = $this->staffUser();
        $this->assign($store, $member, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Store');

        $this->actingAs($this->staffUser())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('My Store');
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
