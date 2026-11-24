<?php

namespace Tests\Feature\Stores;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreFundAssignment;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreFundAssignmentHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_store_member_can_open_only_their_own_fund_page(): void
    {
        $ownStore = $this->makeStore('Own Store', 'OWN-001');
        $otherStore = $this->makeStore('Other Store', 'OTH-001');

        $coordinator = User::factory()->create(['is_active' => true]);

        $this->assign($ownStore, $coordinator, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($coordinator)
            ->get(route('stores.fund-assignments.index', $ownStore))
            ->assertOk()
            ->assertSee('Own Store');

        $this->actingAs($coordinator)
            ->get(route('stores.fund-assignments.index', $otherStore))
            ->assertForbidden();
    }

    public function test_active_assistant_can_open_their_own_fund_page(): void
    {
        $store = $this->makeStore('Assistant Store', 'AST-001');
        $assistant = User::factory()->create(['is_active' => true]);

        $this->assign($store, $assistant, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($assistant)
            ->get(route('stores.fund-assignments.index', $store))
            ->assertOk()
            ->assertSee('Assistant Store');
    }

    public function test_global_queue_is_not_available_to_store_staff(): void
    {
        $store = $this->makeStore('Staff Store', 'STF-001');
        $assistant = User::factory()->create(['is_active' => true]);

        $this->assign($store, $assistant, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($assistant)
            ->get(route('store-fund-assignments.index'))
            ->assertForbidden();
    }

    public function test_direct_assignment_store_list_excludes_an_admins_active_store(): void
    {
        $admin = $this->administratorWithFundPermissions();

        $assignedStore = $this->makeStore('Assigned Store', 'ASG-001');
        $availableStore = $this->makeStore('Available Store', 'AVL-001');

        // An administrator cannot directly assign funds to a store
        // where that administrator is currently assigned.
        $this->assign($assignedStore, $admin, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($admin)
            ->get(route('store-fund-assignments.create'))
            ->assertRedirect(route('store-fund-assignments.index', ['tab' => 'assign']));

        $this->actingAs($admin)
            ->followingRedirects()
            ->get(route('store-fund-assignments.create'))
            ->assertOk()
            ->assertSee('Assign Funds')
            ->assertSee('Available Store')
            ->assertDontSee('Assigned Store');
    }

    public function test_store_detail_exposes_assigned_funds_link_to_active_store_members(): void
    {
        $store = $this->makeStore('Member Store', 'MEM-001');
        $coordinator = User::factory()->create(['is_active' => true]);

        $this->assign($store, $coordinator, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($coordinator)
            ->get(route('stores.show', $store))
            ->assertOk()
            ->assertSee(route('stores.fund-assignments.index', $store));
    }

    public function test_finance_reviewer_can_open_request_queue_without_direct_assignment_controls(): void
    {
        $viewPermission = Permission::firstOrCreate(
            ['slug' => 'stores.funds.view'],
            [
                'name' => 'View Store Funds',
                'description' => 'View assigned store funds.',
            ]
        );

        $reviewPermission = Permission::firstOrCreate(
            ['slug' => 'stores.funds.review'],
            [
                'name' => 'Review Store Fund Requests',
                'description' => 'Approve or reject store fund requests.',
            ]
        );

        $role = Role::firstOrCreate(
            ['slug' => 'finance'],
            [
                'name' => 'Finance',
                'description' => 'Finance test role.',
            ]
        );

        $role->permissions()->syncWithoutDetaching([
            $viewPermission->id,
            $reviewPermission->id,
        ]);

        $financeUser = User::factory()->create(['is_active' => true]);
        $financeUser->roles()->attach($role);

        $this->actingAs($financeUser)
            ->get(route('store-fund-requests.index'))
            ->assertRedirect(route('store-fund-assignments.index', ['tab' => 'requests']));

        $this->actingAs($financeUser)
            ->followingRedirects()
            ->get(route('store-fund-requests.index'))
            ->assertOk()
            ->assertSee('Store Fund Requests')
            ->assertSee('Store fund requests')
            ->assertDontSee('Create direct assignment')
            ->assertDontSee('Store Funds History');
    }

    public function test_administrator_has_one_workspace_with_requests_history_and_direct_assignment(): void
    {
        $admin = $this->administratorWithFundPermissions();

        // Ensure an eligible active store exists so the direct assignment form renders.
        $this->makeStore('Direct Assignment Store', 'DIR-001');

        $this->actingAs($admin)
            ->get(route('store-fund-requests.index'))
            ->assertRedirect(route('store-fund-assignments.index', ['tab' => 'requests']));

        $this->actingAs($admin)
            ->get(route('store-fund-assignments.index', ['tab' => 'requests']))
            ->assertOk()
            ->assertSee('Store Fund Requests')
            ->assertDontSee('Create direct assignment');

        $this->actingAs($admin)
            ->get(route('store-fund-assignments.index', ['tab' => 'history']))
            ->assertOk()
            ->assertSee('Store Funds History')
            ->assertSee('Assign Funds')
            ->assertDontSee('Create direct assignment');

        $this->actingAs($admin)
            ->get(route('store-fund-assignments.index', ['tab' => 'assign']))
            ->assertOk()
            ->assertSee('Assign Funds')
            ->assertSee('Direct Assignment Store')
            ->assertSee('Create direct assignment')
            ->assertSee('requested_cash')
            ->assertSee('requested_bank');
    }

    public function test_finance_reviewer_is_restricted_to_requests_even_when_history_tab_is_requested(): void
    {
        $viewPermission = Permission::firstOrCreate(
            ['slug' => 'stores.funds.view'],
            [
                'name' => 'View Store Funds',
                'description' => 'View assigned store funds.',
            ]
        );

        $reviewPermission = Permission::firstOrCreate(
            ['slug' => 'stores.funds.review'],
            [
                'name' => 'Review Store Fund Requests',
                'description' => 'Approve or reject store fund requests.',
            ]
        );

        $role = Role::firstOrCreate(
            ['slug' => 'finance'],
            [
                'name' => 'Finance',
                'description' => 'Finance test role.',
            ]
        );

        $role->permissions()->syncWithoutDetaching([
            $viewPermission->id,
            $reviewPermission->id,
        ]);

        $financeUser = User::factory()->create(['is_active' => true]);
        $financeUser->roles()->attach($role);

        $this->actingAs($financeUser)
            ->get(route('store-fund-assignments.index', ['tab' => 'history']))
            ->assertOk()
            ->assertSee('Store Fund Requests')
            ->assertDontSee('Store Funds History')
            ->assertDontSee('Create direct assignment');
    }

    public function test_super_admin_sees_assign_funds_action_on_store_detail(): void
    {
        $viewPermission = Permission::firstOrCreate(
            ['slug' => 'stores.funds.view'],
            [
                'name' => 'View Store Funds',
                'description' => 'View assigned store funds.',
            ]
        );

        $managePermission = Permission::firstOrCreate(
            ['slug' => 'stores.funds.manage'],
            [
                'name' => 'Manage Store Funds',
                'description' => 'Manage assigned store funds.',
            ]
        );

        $storeManagePermission = Permission::firstOrCreate(
            ['slug' => 'stores.manage'],
            [
                'name' => 'Manage Stores',
                'description' => 'Manage stores.',
            ]
        );

        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Administrator',
                'description' => 'Test super administrator role.',
            ]
        );

        $role->permissions()->syncWithoutDetaching([
            $viewPermission->id,
            $managePermission->id,
            $storeManagePermission->id,
        ]);

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->roles()->attach($role);

        $store = $this->makeStore('Super Admin Store', 'SAD-001');

        $this->actingAs($superAdmin)
            ->get(route('stores.show', $store))
            ->assertOk()
            ->assertSee('Assign Funds')
            ->assertSee(route('stores.fund-assignments.index', $store));
    }

    public function test_finance_reviewer_cannot_open_direct_assignment_page(): void
    {
        $viewPermission = Permission::firstOrCreate(
            ['slug' => 'stores.funds.view'],
            [
                'name' => 'View Store Funds',
                'description' => 'View assigned store funds.',
            ]
        );

        $reviewPermission = Permission::firstOrCreate(
            ['slug' => 'stores.funds.review'],
            [
                'name' => 'Review Store Fund Requests',
                'description' => 'Approve or reject store fund requests.',
            ]
        );

        $role = Role::firstOrCreate(
            ['slug' => 'finance'],
            [
                'name' => 'Finance',
                'description' => 'Finance test role.',
            ]
        );

        $role->permissions()->syncWithoutDetaching([
            $viewPermission->id,
            $reviewPermission->id,
        ]);

        $financeUser = User::factory()->create(['is_active' => true]);
        $financeUser->roles()->attach($role);

        $this->actingAs($financeUser)
            ->get(route('store-fund-assignments.create'))
            ->assertForbidden();
    }

    public function test_admin_sidebar_has_one_consolidated_store_funds_navigation_link(): void
    {
        $admin = $this->administratorWithFundPermissions();

        $this->actingAs($admin)
            ->get(route('store-fund-assignments.index'))
            ->assertOk()
            ->assertSee('Store Funds')
            ->assertSee(route('store-fund-assignments.index'))
            ->assertDontSee(route('store-fund-requests.index'))
            ->assertDontSee(route('store-fund-assignments.create'));
    }

    private function administratorWithFundPermissions(): User
    {
        $viewPermission = Permission::firstOrCreate(
            ['slug' => 'stores.funds.view'],
            [
                'name' => 'View Store Funds',
                'description' => 'View assigned store funds.',
            ]
        );

        $managePermission = Permission::firstOrCreate(
            ['slug' => 'stores.funds.manage'],
            [
                'name' => 'Manage Store Funds',
                'description' => 'Manage assigned store funds.',
            ]
        );

        $role = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'Test administrator role.',
            ]
        );

        $role->permissions()->syncWithoutDetaching([
            $viewPermission->id,
            $managePermission->id,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function makeStore(string $name, string $code): Store
    {
        return Store::query()->create([
            'name' => $name,
            'code' => $code,
            'type' => Store::TYPE_STORE,
            'is_active' => true,
        ]);
    }

    private function assign(Store $store, User $user, string $role): StoreStaff
    {
        return StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now(),
        ]);
    }
}
