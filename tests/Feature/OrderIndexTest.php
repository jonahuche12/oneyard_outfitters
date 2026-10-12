<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OrderIndexTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $slug, array $permissions = ['orders.view']): User
    {
        $role = Role::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'description' => 'Test role.',
        ]);

        foreach ($permissions as $permission) {
            $role->permissions()->attach(
                Permission::firstOrCreate(
                    ['slug' => $permission],
                    ['name' => $permission, 'description' => 'Test permission.']
                )
            );
        }

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function order(
        string $number,
        array $overrides = [],
        ?Organization $organization = null
    ): Order {
        $organization ??= Organization::factory()->create();

        $quotation = Quotation::create([
            'quotation_number' => 'TMP-' . uniqid('', true),
            'organization_id' => $organization->id,
            'created_by' => User::factory()->create()->id,
            'quotation_date' => now()->toDateString(),
            'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 0,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 0,
        ]);

        return Order::create(array_merge([
            'order_number' => $number,
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => null,
            'order_date' => now()->toDateString(),
            'status' => Order::STATUS_APPROVED,
            'subtotal' => 0,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 0,
        ], $overrides));
    }

    private function ajax(User $user, array $query = []): TestResponse
    {
        return $this->actingAs($user)->get(
            route('orders.index', $query),
            ['X-Requested-With' => 'XMLHttpRequest']
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('orders.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('orders.index'))
            ->assertForbidden();
    }

    public function test_index_renders_the_full_page_with_filters_and_results(): void
    {
        $this->order('ORD-FULL001');

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('orders.index'))
            ->assertOk()
            ->assertViewIs('orders.index')
            ->assertSee('Order Records')
            ->assertSee('orders-filter-form', false)
            ->assertSee('orders-results', false)
            ->assertSee('ORD-FULL001');
    }

    public function test_ajax_request_returns_only_the_results_partial(): void
    {
        $this->order('ORD-AJAX001');

        $response = $this->ajax($this->userWithRole('admin'))
            ->assertOk()
            ->assertViewIs('orders.partials.table')
            ->assertSee('data-list-total="1"', false)
            ->assertSee('ORD-AJAX001');

        $this->assertStringNotContainsString('<html', $response->getContent());
    }

    public function test_delivered_orders_are_hidden_until_the_delivered_status_is_selected(): void
    {
        $this->order('ORD-ACTIVE01');
        $this->order('ORD-DONE0001', ['status' => Order::STATUS_DELIVERED]);

        $admin = $this->userWithRole('admin');

        $this->ajax($admin)
            ->assertSee('ORD-ACTIVE01')
            ->assertDontSee('ORD-DONE0001');

        $this->ajax($admin, ['status' => Order::STATUS_DELIVERED])
            ->assertSee('ORD-DONE0001')
            ->assertDontSee('ORD-ACTIVE01');
    }

    public function test_ready_for_quality_control_status_filter_works(): void
    {
        $this->order('ORD-QC000001', ['status' => Order::STATUS_READY_FOR_QUALITY_CONTROL]);
        $this->order('ORD-APPROVED1', ['status' => Order::STATUS_APPROVED]);

        $this->ajax(
            $this->userWithRole('admin'),
            ['status' => Order::STATUS_READY_FOR_QUALITY_CONTROL]
        )
            ->assertSee('ORD-QC000001')
            ->assertDontSee('ORD-APPROVED1');
    }

    public function test_unknown_status_is_ignored(): void
    {
        $this->order('ORD-ONE00001');
        $this->order('ORD-TWO00001', ['status' => Order::STATUS_IN_PRODUCTION]);

        $this->ajax($this->userWithRole('admin'), ['status' => 'bogus'])
            ->assertOk()
            ->assertSee('ORD-ONE00001')
            ->assertSee('ORD-TWO00001');
    }

    public function test_search_matches_order_number_organization_name_and_code(): void
    {
        $alpha = Organization::factory()->create(['name' => 'Alpha School']);
        $beta = Organization::factory()->create(['name' => 'Beta Academy']);

        $this->order('ORD-AAA00001', [], $alpha);
        $this->order('ORD-BBB00001', [], $beta);

        $admin = $this->userWithRole('admin');

        $this->ajax($admin, ['search' => 'AAA00001'])
            ->assertSee('ORD-AAA00001')
            ->assertDontSee('ORD-BBB00001');

        $this->ajax($admin, ['search' => 'Beta Acad'])
            ->assertSee('ORD-BBB00001')
            ->assertDontSee('ORD-AAA00001');

        $this->ajax($admin, ['search' => $alpha->organization_code])
            ->assertSee('ORD-AAA00001')
            ->assertDontSee('ORD-BBB00001');
    }

    public function test_non_administrator_only_sees_orders_they_coordinate(): void
    {
        $coordinator = $this->userWithRole('order-coordinator');

        $mine = $this->order('ORD-MINE0001');
        $this->order('ORD-OTHER001');

        OrderAssignment::create([
            'order_id' => $mine->id,
            'user_id' => $coordinator->id,
            'assigned_by' => $coordinator->id,
            'assigned_at' => now(),
        ]);

        $this->ajax($coordinator)
            ->assertSee('ORD-MINE0001')
            ->assertDontSee('ORD-OTHER001')
            ->assertSee('data-list-total="1"', false);
    }

    public function test_results_are_paginated_twenty_per_page_and_keep_filters(): void
    {
        $alpha = Organization::factory()->create(['name' => 'Alpha School']);

        for ($i = 1; $i <= 21; $i++) {
            $this->order(sprintf('ORD-PAGE%04d', $i), [], $alpha);
        }

        $this->order('ORD-OTHER999');

        $admin = $this->userWithRole('admin');

        $first = $this->ajax($admin, ['search' => 'Alpha'])->assertOk();

        $this->assertSame(20, substr_count($first->getContent(), 'data-order-row='));
        $this->assertStringContainsString('data-list-total="21"', $first->getContent());
        $this->assertStringContainsString('search=Alpha', $first->getContent());

        $second = $this->ajax($admin, ['search' => 'Alpha', 'page' => 2])->assertOk();

        $this->assertSame(1, substr_count($second->getContent(), 'data-order-row='));
    }

    public function test_delivery_estimate_shows_remaining_time_and_overdue_state(): void
    {
        $this->order('ORD-LATE0001', ['expected_delivery_date' => today()->subDays(3)->toDateString()]);
        $this->order('ORD-TODAY001', ['expected_delivery_date' => today()->toDateString()]);
        $this->order('ORD-SOON0001', ['expected_delivery_date' => today()->addDays(2)->toDateString()]);
        $this->order('ORD-LATER001', ['expected_delivery_date' => today()->addDays(10)->toDateString()]);

        $this->ajax($this->userWithRole('admin'))
            ->assertOk()
            ->assertSee('3 days overdue')
            ->assertSee('Due today')
            ->assertSee('2 days to delivery')
            ->assertSee('10 days to delivery');
    }

    public function test_assigned_coordinator_name_is_shown(): void
    {
        $order = $this->order('ORD-COORD001');
        $coordinator = User::factory()->create(['name' => 'Chidi Coordinator']);

        OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $coordinator->id,
            'assigned_by' => $coordinator->id,
            'assigned_at' => now(),
        ]);

        $this->ajax($this->userWithRole('admin'))
            ->assertSee('Chidi Coordinator');
    }

    public function test_empty_state_explains_how_orders_are_created(): void
    {
        $this->ajax($this->userWithRole('admin'))
            ->assertOk()
            ->assertSee('No orders found.')
            ->assertSee('created automatically after a verified quotation payment');
    }
}
