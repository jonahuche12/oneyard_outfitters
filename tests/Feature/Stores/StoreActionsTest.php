<?php

namespace Tests\Feature\Stores;

use App\Actions\Stores\AssignStoreStaff;
use App\Actions\Stores\CreateStore;
use App\Actions\Stores\EndStoreStaffAssignment;
use App\Actions\Stores\ReassignStoreStaff;
use App\Actions\Stores\SetStoreActive;
use App\Actions\Stores\UpdateStore;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreActionsTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- CreateStore

    public function test_manager_creates_a_store_with_normalised_fields(): void
    {
        $manager = $this->manager();

        $store = app(CreateStore::class)->execute($manager, [
            'name' => '  Ikeja Fabrics  ',
            'code' => ' ikj-01 ',
            'type' => Store::TYPE_STORE,
            'address' => '',
            'phone' => '0803 000 0000',
        ]);

        $this->assertSame('Ikeja Fabrics', $store->name);
        $this->assertSame('IKJ-01', $store->code);
        $this->assertNull($store->address);
        $this->assertTrue($store->is_active);
        $this->assertSame($manager->id, $store->created_by);
    }

    public function test_duplicate_name_or_code_is_rejected(): void
    {
        $manager = $this->manager();
        app(CreateStore::class)->execute($manager, $this->storeInput(['name' => 'Ikeja Fabrics', 'code' => 'IKJ01']));

        $this->assertValidationFails(
            fn () => app(CreateStore::class)->execute($manager, $this->storeInput(['name' => 'Ikeja Fabrics'])),
            'name'
        );
        $this->assertValidationFails(
            fn () => app(CreateStore::class)->execute($manager, $this->storeInput(['code' => 'ikj01'])),
            'code'
        );
        $this->assertSame(1, Store::query()->count());
    }

    public function test_only_one_general_warehouse_can_be_created(): void
    {
        $manager = $this->manager();

        app(CreateStore::class)->execute($manager, $this->storeInput(['type' => Store::TYPE_WAREHOUSE]));

        $this->assertValidationFails(
            fn () => app(CreateStore::class)->execute($manager, $this->storeInput(['type' => Store::TYPE_WAREHOUSE])),
            'type'
        );
        $this->assertSame(1, Store::query()->where('type', Store::TYPE_WAREHOUSE)->count());
    }

    public function test_invalid_store_type_is_rejected(): void
    {
        $this->assertValidationFails(
            fn () => app(CreateStore::class)->execute($this->manager(), $this->storeInput(['type' => 'depot'])),
            'type'
        );
    }

    // ---------------------------------------------------------------- UpdateStore

    public function test_update_changes_details_but_never_the_type(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();

        $updated = app(UpdateStore::class)->execute($manager, $store, [
            'name' => ' Lekki Fabrics ',
            'code' => 'lek01',
            'address' => 'New address',
            'phone' => '',
            'type' => Store::TYPE_WAREHOUSE,
        ]);

        $fresh = $updated->fresh();

        $this->assertSame('Lekki Fabrics', $fresh->name);
        $this->assertSame('LEK01', $fresh->code);
        $this->assertSame('New address', $fresh->address);
        $this->assertNull($fresh->phone);
        $this->assertSame(Store::TYPE_STORE, $fresh->type);
        $this->assertNull($fresh->general_warehouse_slot);
    }

    public function test_update_rejects_another_stores_name_but_allows_its_own(): void
    {
        $manager = $this->manager();
        $this->makeStore(['name' => 'Alpha Store']);
        $beta = $this->makeStore(['name' => 'Beta Store']);

        $this->assertValidationFails(
            fn () => app(UpdateStore::class)->execute($manager, $beta, ['name' => 'Alpha Store', 'code' => $beta->code]),
            'name'
        );

        $same = app(UpdateStore::class)->execute($manager, $beta, ['name' => 'Beta Store', 'code' => $beta->code]);

        $this->assertSame('Beta Store', $same->name);
    }

    // -------------------------------------------------------------- SetStoreActive

    public function test_store_can_be_deactivated_and_reactivated_but_not_repeated(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();

        $off = app(SetStoreActive::class)->execute($manager, $store, false);
        $this->assertFalse($off->is_active);

        $this->assertValidationFails(
            fn () => app(SetStoreActive::class)->execute($manager, $store, false),
            'is_active'
        );

        $on = app(SetStoreActive::class)->execute($manager, $store, true);
        $this->assertTrue($on->is_active);
    }

    public function test_the_general_warehouse_cannot_be_deactivated(): void
    {
        $warehouse = $this->makeStore(['type' => Store::TYPE_WAREHOUSE]);

        $this->assertValidationFails(
            fn () => app(SetStoreActive::class)->execute($this->manager(), $warehouse, false),
            'is_active'
        );
        $this->assertTrue($warehouse->fresh()->is_active);
    }

    // ------------------------------------------------------------ AssignStoreStaff

    public function test_assigning_an_assistant_records_who_and_when(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();
        $user = $this->staffUser();

        $assignment = app(AssignStoreStaff::class)->execute($manager, $store, $user, StoreStaff::ROLE_ASSISTANT);

        $this->assertSame($store->id, $assignment->store_id);
        $this->assertSame($user->id, $assignment->user_id);
        $this->assertSame(StoreStaff::ROLE_ASSISTANT, $assignment->role);
        $this->assertTrue($assignment->isActive());
        $this->assertSame($manager->id, $assignment->assigned_by);
        $this->assertNotNull($assignment->starts_at);
    }

    public function test_a_new_coordinator_replaces_the_previous_one(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();

        $first = app(AssignStoreStaff::class)->execute($manager, $store, $this->staffUser(), StoreStaff::ROLE_COORDINATOR);
        $second = app(AssignStoreStaff::class)->execute($manager, $store, $this->staffUser(), StoreStaff::ROLE_COORDINATOR);

        $first->refresh();

        $this->assertNotNull($first->ends_at);
        $this->assertSame('Replaced by a new coordinator.', $first->ended_reason);
        $this->assertSame($manager->id, $first->ended_by);
        $this->assertTrue($second->isActive());
        $this->assertSame(1, StoreStaff::query()
            ->where('store_id', $store->id)
            ->active()
            ->where('role', StoreStaff::ROLE_COORDINATOR)
            ->count());
    }

    public function test_an_inactive_user_cannot_be_assigned(): void
    {
        $this->assertValidationFails(
            fn () => app(AssignStoreStaff::class)->execute(
                $this->manager(),
                $this->makeStore(),
                $this->staffUser(false),
                StoreStaff::ROLE_ASSISTANT
            ),
            'user_id'
        );
        $this->assertSame(0, StoreStaff::query()->count());
    }

    public function test_a_user_already_assigned_cannot_be_assigned_again(): void
    {
        $manager = $this->manager();
        $storeA = $this->makeStore();
        $storeB = $this->makeStore();
        $user = $this->staffUser();

        app(AssignStoreStaff::class)->execute($manager, $storeA, $user, StoreStaff::ROLE_ASSISTANT);

        $this->assertValidationFails(
            fn () => app(AssignStoreStaff::class)->execute($manager, $storeB, $user, StoreStaff::ROLE_ASSISTANT),
            'user_id'
        );
        $this->assertValidationFails(
            fn () => app(AssignStoreStaff::class)->execute($manager, $storeA, $user, StoreStaff::ROLE_COORDINATOR),
            'user_id'
        );
        $this->assertSame(1, StoreStaff::query()->count());
    }

    public function test_staff_cannot_be_assigned_to_the_warehouse_or_an_inactive_store(): void
    {
        $manager = $this->manager();
        $warehouse = $this->makeStore(['type' => Store::TYPE_WAREHOUSE]);
        $inactive = $this->makeStore(['is_active' => false]);

        $this->assertValidationFails(
            fn () => app(AssignStoreStaff::class)->execute($manager, $warehouse, $this->staffUser(), StoreStaff::ROLE_ASSISTANT),
            'store'
        );
        $this->assertValidationFails(
            fn () => app(AssignStoreStaff::class)->execute($manager, $inactive, $this->staffUser(), StoreStaff::ROLE_ASSISTANT),
            'store'
        );
        $this->assertSame(0, StoreStaff::query()->count());
    }

    public function test_an_invalid_role_is_rejected(): void
    {
        $this->assertValidationFails(
            fn () => app(AssignStoreStaff::class)->execute($this->manager(), $this->makeStore(), $this->staffUser(), 'manager'),
            'role'
        );
    }

    // ------------------------------------------------------ EndStoreStaffAssignment

    public function test_ending_an_assignment_keeps_history_and_frees_the_user(): void
    {
        $manager = $this->manager();
        $storeA = $this->makeStore();
        $storeB = $this->makeStore();
        $user = $this->staffUser();

        $assignment = app(AssignStoreStaff::class)->execute($manager, $storeA, $user, StoreStaff::ROLE_ASSISTANT);

        $ended = app(EndStoreStaffAssignment::class)->execute($manager, $assignment, 'Left the company in November.');

        $this->assertNotNull($ended->ends_at);
        $this->assertSame('Left the company in November.', $ended->ended_reason);
        $this->assertSame($manager->id, $ended->ended_by);
        $this->assertNull($ended->fresh()->active_user_slot);

        app(AssignStoreStaff::class)->execute($manager, $storeB, $user, StoreStaff::ROLE_ASSISTANT);

        $this->assertSame(2, $user->storeAssignments()->count());
    }

    public function test_ending_needs_a_real_reason_and_cannot_be_repeated(): void
    {
        $manager = $this->manager();
        $assignment = app(AssignStoreStaff::class)->execute(
            $manager,
            $this->makeStore(),
            $this->staffUser(),
            StoreStaff::ROLE_ASSISTANT
        );

        $this->assertValidationFails(
            fn () => app(EndStoreStaffAssignment::class)->execute($manager, $assignment, 'short'),
            'reason'
        );
        $this->assertTrue($assignment->fresh()->isActive());

        app(EndStoreStaffAssignment::class)->execute($manager, $assignment, 'Contract finished last week.');

        $this->assertValidationFails(
            fn () => app(EndStoreStaffAssignment::class)->execute($manager, $assignment, 'Trying to end it twice.'),
            'assignment'
        );
    }

    // ------------------------------------------------------- ReassignStoreStaff

    public function test_reassigning_moves_a_user_and_keeps_the_old_assignment_as_history(): void
    {
        $manager = $this->manager();
        $storeA = $this->makeStore(['name' => 'Alpha Store']);
        $storeB = $this->makeStore(['name' => 'Beta Store']);
        $user = $this->staffUser();

        $old = app(AssignStoreStaff::class)->execute($manager, $storeA, $user, StoreStaff::ROLE_ASSISTANT);
        $new = app(ReassignStoreStaff::class)->execute($manager, $user, $storeB, StoreStaff::ROLE_ASSISTANT);

        $old->refresh();

        $this->assertNotNull($old->ends_at);
        $this->assertSame('Reassigned to Beta Store.', $old->ended_reason);
        $this->assertSame($manager->id, $old->ended_by);
        $this->assertSame($storeB->id, $new->store_id);
        $this->assertTrue($new->isActive());
        $this->assertSame(2, $user->storeAssignments()->count());
        $this->assertTrue($user->activeStoreAssignment->is($new));
    }

    public function test_promoting_an_assistant_replaces_the_coordinator(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();
        $assistantUser = $this->staffUser();

        $coordinator = app(AssignStoreStaff::class)->execute($manager, $store, $this->staffUser(), StoreStaff::ROLE_COORDINATOR);
        $assistant = app(AssignStoreStaff::class)->execute($manager, $store, $assistantUser, StoreStaff::ROLE_ASSISTANT);

        $promoted = app(ReassignStoreStaff::class)->execute($manager, $assistantUser, $store, StoreStaff::ROLE_COORDINATOR);

        $assistant->refresh();
        $coordinator->refresh();

        $this->assertSame(StoreStaff::ROLE_COORDINATOR, $promoted->role);
        $this->assertSame('Role changed to coordinator.', $assistant->ended_reason);
        $this->assertNotNull($coordinator->ends_at);
        $this->assertSame('Replaced by a new coordinator.', $coordinator->ended_reason);
        $this->assertTrue($store->fresh()->coordinatorAssignment->is($promoted));
    }

    public function test_a_coordinator_moved_elsewhere_replaces_the_target_coordinator(): void
    {
        $manager = $this->manager();
        $storeA = $this->makeStore();
        $storeB = $this->makeStore();
        $moving = $this->staffUser();

        app(AssignStoreStaff::class)->execute($manager, $storeA, $moving, StoreStaff::ROLE_COORDINATOR);
        $targetCoordinator = app(AssignStoreStaff::class)->execute($manager, $storeB, $this->staffUser(), StoreStaff::ROLE_COORDINATOR);

        $moved = app(ReassignStoreStaff::class)->execute($manager, $moving, $storeB, StoreStaff::ROLE_COORDINATOR);

        $this->assertNull($storeA->fresh()->coordinatorAssignment);
        $this->assertTrue($storeB->fresh()->coordinatorAssignment->is($moved));
        $this->assertNotNull($targetCoordinator->fresh()->ends_at);
    }

    public function test_reassign_rejects_unassigned_users_and_unchanged_roles(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();
        $other = $this->makeStore();
        $user = $this->staffUser();

        $this->assertValidationFails(
            fn () => app(ReassignStoreStaff::class)->execute($manager, $user, $store, StoreStaff::ROLE_ASSISTANT),
            'user_id'
        );

        app(AssignStoreStaff::class)->execute($manager, $store, $user, StoreStaff::ROLE_ASSISTANT);

        $this->assertValidationFails(
            fn () => app(ReassignStoreStaff::class)->execute($manager, $user, $store, StoreStaff::ROLE_ASSISTANT),
            'role'
        );
        $this->assertValidationFails(
            fn () => app(ReassignStoreStaff::class)->execute($manager, $user, $other, 'boss'),
            'role'
        );
    }

    public function test_a_rejected_reassignment_leaves_the_current_assignment_untouched(): void
    {
        $manager = $this->manager();
        $store = $this->makeStore();
        $inactive = $this->makeStore(['is_active' => false]);
        $user = $this->staffUser();

        $original = app(AssignStoreStaff::class)->execute($manager, $store, $user, StoreStaff::ROLE_ASSISTANT);

        $this->assertValidationFails(
            fn () => app(ReassignStoreStaff::class)->execute($manager, $user, $inactive, StoreStaff::ROLE_ASSISTANT),
            'store'
        );

        $this->assertTrue($original->fresh()->isActive());
        $this->assertSame(1, $user->storeAssignments()->count());
    }

    // -------------------------------------------------------------- Authorisation

    public function test_every_action_denies_unauthorised_and_inactive_actors(): void
    {
        $store = $this->makeStore();
        $other = $this->makeStore();
        $member = $this->staffUser();
        $candidate = $this->staffUser();
        $assignment = StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $member->id,
            'role' => StoreStaff::ROLE_ASSISTANT,
            'starts_at' => now(),
        ]);

        foreach ([$this->staffUser(), $this->manager(false)] as $actor) {
            $calls = [
                fn () => app(CreateStore::class)->execute($actor, $this->storeInput()),
                fn () => app(UpdateStore::class)->execute($actor, $store, ['name' => 'Renamed Store', 'code' => 'RENAMED']),
                fn () => app(SetStoreActive::class)->execute($actor, $store, false),
                fn () => app(AssignStoreStaff::class)->execute($actor, $store, $candidate, StoreStaff::ROLE_ASSISTANT),
                fn () => app(EndStoreStaffAssignment::class)->execute($actor, $assignment, 'Trying without permission.'),
                fn () => app(ReassignStoreStaff::class)->execute($actor, $member, $other, StoreStaff::ROLE_ASSISTANT),
            ];

            foreach ($calls as $call) {
                try {
                    $call();
                    $this->fail('Expected an AuthorizationException.');
                } catch (AuthorizationException) {
                    // expected
                }
            }
        }

        $this->assertSame(2, Store::query()->count());
        $this->assertTrue($store->fresh()->is_active);
        $this->assertNull($assignment->fresh()->ends_at);
        $this->assertSame(1, StoreStaff::query()->count());
    }

    // -------------------------------------------------------------------- Helpers

    /**
     * An actor holding stores.manage through a role of its own
     * (each actor gets a distinct role slug).
     */
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

    private function assertValidationFails(callable $callback, string $key): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($key, $exception->errors());

            return;
        }

        $this->fail('Expected a ValidationException for "'.$key.'".');
    }
}
