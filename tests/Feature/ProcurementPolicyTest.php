<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Procurement;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function permission(string $slug): Permission
    {
        return Permission::create([
            'name' => ucwords(str_replace(['.', '-'], ' ', $slug)),
            'slug' => $slug,
            'description' => 'Test permission.',
        ]);
    }

    private function userWithRole(
        string $roleSlug,
        array $permissions
    ): User {
        $role = Role::create([
            'name' => ucwords(str_replace('-', ' ', $roleSlug)),
            'slug' => $roleSlug,
            'description' => 'Test role.',
        ]);

        foreach ($permissions as $permission) {
            $role->permissions()->attach(
                $this->permission($permission)
            );
        }

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->roles()->attach($role);

        return $user;
    }

    private function order(): Order
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $quotation = Quotation::create([
            'quotation_number' => 'TMP-' . uniqid('', true),
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'quotation_date' => now()->toDateString(),
            'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_DRAFT,
            'subtotal' => 0,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 0,
        ]);

        return Order::create([
            'order_number' => 'ORD-' . uniqid('', true),
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => null,
            'order_date' => now()->toDateString(),
            'status' => Order::STATUS_IN_PRODUCTION,
            'subtotal' => 0,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 0,
        ]);
    }

    private function procurementPayload(): array
    {
        return [
            'item_name' => 'School Uniform Fabric',
            'description' => 'Navy blue uniform fabric.',
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 3500,
            'commission_per_unit' => 100,
            'required_by' => now()->addDays(14)->toDateString(),
            'offer_deadline' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'priority' => 'normal',
            'notes' => 'Procure according to approved specification.',
        ];
    }

    public function test_staff_with_procurement_view_permission_sees_procurement_navigation(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Procurement')
            ->assertSee(route('procurements.index'), false);
    }

    public function test_staff_without_procurement_view_permission_does_not_see_procurement_navigation(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSee('Procurement')
            ->assertDontSee(route('procurements.index'), false);
    }

    public function test_guest_cannot_access_procurement_store(): void
    {
        $response = $this->post(
            route('procurements.store'),
            $this->procurementPayload()
        );

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_procurement_manage_cannot_create_independent_procurement(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.store'),
                $this->procurementPayload()
            );

        $response->assertForbidden();

        expect(Procurement::query()->count())->toBe(0);
    }

    public function test_admin_can_create_independent_procurement_through_http(): void
    {
        $admin = $this->userWithRole(
            'admin',
            ['procurement.manage']
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route('procurements.store'),
                $this->procurementPayload()
            );

        $response->assertRedirect();

        $procurement = Procurement::query()->first();

        expect($procurement)->not->toBeNull()
            ->and($procurement->order_id)->toBeNull()
            ->and($procurement->created_by)->toBe($admin->id)
            ->and($procurement->item_name)
            ->toBe('School Uniform Fabric');
    }

    public function test_coordinator_cannot_create_independent_procurement_through_http(): void
    {
        $coordinator = $this->userWithRole(
            'coordinator',
            ['procurement.manage']
        );

        $response = $this
            ->actingAs($coordinator)
            ->post(
                route('procurements.store'),
                $this->procurementPayload()
            );

        $response->assertForbidden();

        expect(Procurement::query()->count())->toBe(0);
    }

    public function test_coordinator_can_create_procurement_for_assigned_order_through_http(): void
    {
        $coordinator = $this->userWithRole(
            'coordinator',
            ['procurement.manage']
        );

        $order = $this->order();

        OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $coordinator->id,
            'assigned_by' => $coordinator->id,
            'assigned_at' => now(),
        ]);

        $response = $this
            ->actingAs($coordinator)
            ->post(
                route('orders.procurements.store', $order),
                $this->procurementPayload()
            );

        $response->assertRedirect();

        $procurement = Procurement::query()->first();

        expect($procurement)->not->toBeNull()
            ->and($procurement->order_id)->toBe($order->id)
            ->and($procurement->created_by)->toBe($coordinator->id);
    }

    public function test_coordinator_cannot_create_procurement_for_unassigned_order_through_http(): void
    {
        $coordinator = $this->userWithRole(
            'coordinator',
            ['procurement.manage']
        );

        $order = $this->order();

        $response = $this
            ->actingAs($coordinator)
            ->post(
                route('orders.procurements.store', $order),
                $this->procurementPayload()
            );

        $response->assertForbidden();

        expect(Procurement::query()->count())->toBe(0);
    }

    public function test_admin_can_update_procurement_through_http(): void
    {
        $admin = $this->userWithRole(
            'admin',
            ['procurement.manage']
        );

        $procurement = Procurement::create([
            'created_by' => User::factory()->create()->id,
            'item_name' => 'Original Fabric',
            'quantity' => 50,
            'unit' => 'Yard',
            'maximum_unit_price' => 2500,
        ]);

        $payload = $this->procurementPayload();
        $payload['item_name'] = 'Updated Fabric';
        $payload['status'] = Procurement::STATUS_READY;

        $response = $this
            ->actingAs($admin)
            ->patch(
                route('procurements.update', $procurement),
                $payload
            );

        $response->assertRedirect();

        $procurement->refresh();

        expect($procurement->item_name)
            ->toBe('Updated Fabric')
            ->and($procurement->status)
            ->toBe(Procurement::STATUS_READY)
            ->and($procurement->updated_by)
            ->toBe($admin->id);
    }

    public function test_coordinator_cannot_update_procurement_for_another_order_through_http(): void
    {
        $coordinator = $this->userWithRole(
            'coordinator',
            ['procurement.manage']
        );

        $assignedOrder = $this->order();
        $otherOrder = $this->order();

        OrderAssignment::create([
            'order_id' => $assignedOrder->id,
            'user_id' => $coordinator->id,
            'assigned_by' => $coordinator->id,
            'assigned_at' => now(),
        ]);

        $procurement = Procurement::create([
            'order_id' => $otherOrder->id,
            'created_by' => $coordinator->id,
            'item_name' => 'Other Order Fabric',
            'quantity' => 50,
            'unit' => 'Yard',
            'maximum_unit_price' => 2500,
        ]);

        $payload = $this->procurementPayload();
        $payload['item_name'] = 'Unauthorized Update';
        $payload['status'] = Procurement::STATUS_READY;

        $response = $this
            ->actingAs($coordinator)
            ->patch(
                route('procurements.update', $procurement),
                $payload
            );

        $response->assertForbidden();

        expect($procurement->fresh()->item_name)
            ->toBe('Other Order Fabric');
    }

    public function test_coordinator_cannot_update_independent_procurement_through_http(): void
    {
        $coordinator = $this->userWithRole(
            'coordinator',
            ['procurement.manage']
        );

        $procurement = Procurement::create([
            'order_id' => null,
            'created_by' => $coordinator->id,
            'item_name' => 'Independent Fabric',
            'quantity' => 50,
            'unit' => 'Yard',
            'maximum_unit_price' => 2500,
        ]);

        $payload = $this->procurementPayload();
        $payload['item_name'] = 'Unauthorized Update';
        $payload['status'] = Procurement::STATUS_READY;

        $response = $this
            ->actingAs($coordinator)
            ->patch(
                route('procurements.update', $procurement),
                $payload
            );

        $response->assertForbidden();

        expect($procurement->fresh()->item_name)
            ->toBe('Independent Fabric');
    }

    public function test_update_cannot_change_procurement_order_link(): void
    {
        $admin = $this->userWithRole(
            'admin',
            ['procurement.manage']
        );

        $order = $this->order();
        $otherOrder = $this->order();

        $procurement = Procurement::create([
            'order_id' => $order->id,
            'created_by' => $admin->id,
            'item_name' => 'Order Fabric',
            'quantity' => 50,
            'unit' => 'Yard',
            'maximum_unit_price' => 2500,
        ]);

        $payload = $this->procurementPayload();
        $payload['item_name'] = 'Updated Order Fabric';
        $payload['status'] = Procurement::STATUS_READY;
        $payload['order_id'] = $otherOrder->id;

        $response = $this
            ->actingAs($admin)
            ->patch(
                route('procurements.update', $procurement),
                $payload
            );

        $response->assertRedirect();

        expect($procurement->fresh()->order_id)
            ->toBe($order->id);
    }

    public function test_staff_with_procurement_view_permission_can_access_procurement_index(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $response = $this
            ->actingAs($user)
            ->get(route('procurements.index'));

        $response->assertOk()
            ->assertViewIs('procurements.index');
    }

    public function test_staff_without_procurement_view_permission_cannot_access_procurement_index(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('procurements.index'));

        $response->assertForbidden();
    }

    public function test_procurement_index_can_search_by_requirement_name(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'Navy Blue Uniform Fabric',
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 3500,
        ]);

        Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'School Shoes',
            'quantity' => 50,
            'unit' => 'Pair',
            'maximum_unit_price' => 12000,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('procurements.index', [
                'search' => 'Uniform Fabric',
            ]));

        $response->assertOk()
            ->assertSee('Navy Blue Uniform Fabric')
            ->assertDontSee('School Shoes');
    }

    public function test_procurement_index_can_search_by_order_number(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $order = $this->order();

        Procurement::create([
            'order_id' => $order->id,
            'created_by' => $user->id,
            'item_name' => 'Order Fabric',
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 3500,
        ]);

        Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'Independent Fabric',
            'quantity' => 50,
            'unit' => 'Yard',
            'maximum_unit_price' => 3000,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('procurements.index', [
                'search' => $order->order_number,
            ]));

        $response->assertOk()
            ->assertSee('Order Fabric')
            ->assertSee($order->order_number)
            ->assertDontSee('Independent Fabric');
    }

    public function test_procurement_index_can_filter_by_status(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'Ready Fabric',
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 3500,
            'status' => Procurement::STATUS_READY,
        ]);

        Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'Draft Fabric',
            'quantity' => 50,
            'unit' => 'Yard',
            'maximum_unit_price' => 3000,
            'status' => Procurement::STATUS_DRAFT,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('procurements.index', [
                'status' => Procurement::STATUS_READY,
            ]));

        $response->assertOk()
            ->assertSee('Ready Fabric')
            ->assertDontSee('Draft Fabric');
    }


    public function test_admin_with_procurement_manage_permission_can_access_independent_create_page(): void
    {
        $admin = $this->userWithRole(
            'admin',
            ['procurement.manage']
        );

        $response = $this
            ->actingAs($admin)
            ->get(route('procurements.create'));

        $response->assertOk()
            ->assertViewIs('procurements.create')
            ->assertSee('New Procurement Requirement')
            ->assertSee('Independent Procurement')
            ->assertSee('Create Procurement');
    }

    public function test_user_without_procurement_manage_permission_cannot_access_independent_create_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('procurements.create'));

        $response->assertForbidden();
    }

    public function test_coordinator_with_procurement_manage_permission_can_access_assigned_order_create_page(): void
    {
        $coordinator = $this->userWithRole(
            'coordinator',
            ['procurement.manage']
        );

        $order = $this->order();

        OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $coordinator->id,
            'assigned_by' => $coordinator->id,
            'assigned_at' => now(),
        ]);

        $response = $this
            ->actingAs($coordinator)
            ->get(route('orders.procurements.create', $order));

        $response->assertOk()
            ->assertViewIs('procurements.create')
            ->assertSee('Order-linked Procurement')
            ->assertSee($order->order_number)
            ->assertSee('Create Procurement');
    }

    public function test_coordinator_cannot_access_create_page_for_unassigned_order(): void
    {
        $coordinator = $this->userWithRole(
            'coordinator',
            ['procurement.manage']
        );

        $order = $this->order();

        $response = $this
            ->actingAs($coordinator)
            ->get(route('orders.procurements.create', $order));

        $response->assertForbidden();
    }

    public function test_procurement_creation_requires_valid_requirement_fields(): void
    {
        $admin = $this->userWithRole(
            'admin',
            ['procurement.manage']
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route('procurements.store'),
                [
                    'item_name' => '',
                    'quantity' => 0,
                    'unit' => '',
                    'maximum_unit_price' => -1,
                    'priority' => 'invalid',
                ]
            );

        $response->assertSessionHasErrors([
            'item_name',
            'quantity',
            'unit',
            'maximum_unit_price',
            'priority',
        ]);

        expect(Procurement::query()->count())->toBe(0);
    }

    public function test_order_linked_procurement_creation_requires_valid_requirement_fields(): void
    {
        $coordinator = $this->userWithRole(
            'coordinator',
            ['procurement.manage']
        );

        $order = $this->order();

        OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $coordinator->id,
            'assigned_by' => $coordinator->id,
            'assigned_at' => now(),
        ]);

        $response = $this
            ->actingAs($coordinator)
            ->post(
                route('orders.procurements.store', $order),
                [
                    'item_name' => '',
                    'quantity' => 0,
                    'unit' => '',
                    'maximum_unit_price' => -1,
                    'priority' => 'invalid',
                ]
            );

        $response->assertSessionHasErrors([
            'item_name',
            'quantity',
            'unit',
            'maximum_unit_price',
            'priority',
        ]);

        expect(Procurement::query()->count())->toBe(0);
    }


}
