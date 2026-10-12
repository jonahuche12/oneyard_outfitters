<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class OrderVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $slug, array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true]);

        $role = Role::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => 'Role ' . $slug]
        );

        foreach ($permissions as $permissionSlug) {
            $permission = $role->permissions()->getRelated()->newQuery()
                ->firstOrCreate(
                    ['slug' => $permissionSlug],
                    ['name' => $permissionSlug]
                );

            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    private function coordinator(): User
    {
        return $this->userWithRole('coordinator-test', [
            'orders.view',
            'production.manage',
        ]);
    }

    private function makeOrder(
        string $number,
        string $status = Order::STATUS_IN_PRODUCTION,
        ?User $coordinator = null,
        bool $assignmentEnded = false
    ): Order {
        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $creator = User::factory()->create(['is_active' => true]);

        $quotation = Quotation::forceCreate([
            'quotation_number' => 'QUO-' . uniqid(),
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'created_by' => $creator->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
            'terms' => null,
        ]);

        $order = Order::forceCreate([
            'order_number' => $number,
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'order_date' => now()->toDateString(),
            'status' => $status,
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
            'terms' => null,
            'notes' => null,
        ]);

        if ($coordinator !== null) {
            OrderAssignment::create([
                'order_id' => $order->id,
                'user_id' => $coordinator->id,
                'assigned_by' => $coordinator->id,
                'assigned_at' => now()->subDays(2),
                'ended_at' => $assignmentEnded ? now()->subDay() : null,
            ]);
        }

        return $order;
    }

    public function test_non_admin_sees_only_orders_they_coordinate(): void
    {
        $user = $this->coordinator();
        $other = $this->coordinator();

        $this->makeOrder('ORD-900001', Order::STATUS_IN_PRODUCTION, $user);
        $this->makeOrder('ORD-900002', Order::STATUS_IN_PRODUCTION, $other);
        $this->makeOrder('ORD-900003', Order::STATUS_PENDING);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('ORD-900001')
            ->assertDontSee('ORD-900002')
            ->assertDontSee('ORD-900003');
    }

    public function test_non_coordinator_cannot_view_another_users_order(): void
    {
        $user = $this->coordinator();
        $other = $this->coordinator();

        $own = $this->makeOrder('ORD-900011', Order::STATUS_IN_PRODUCTION, $user);
        $theirs = $this->makeOrder('ORD-900012', Order::STATUS_IN_PRODUCTION, $other);
        $unassigned = $this->makeOrder('ORD-900013', Order::STATUS_PENDING);

        $this->assertTrue(Gate::forUser($user)->allows('view', $own));

        $this->actingAs($user)
            ->get(route('orders.show', $theirs))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('orders.show', $unassigned))
            ->assertForbidden();
    }

    public function test_ended_assignment_no_longer_grants_visibility(): void
    {
        $user = $this->coordinator();

        $order = $this->makeOrder(
            'ORD-900021',
            Order::STATUS_IN_PRODUCTION,
            $user,
            assignmentEnded: true
        );

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertDontSee('ORD-900021');

        $this->assertFalse(Gate::forUser($user)->allows('view', $order));
    }

    public function test_admin_and_super_admin_see_all_orders(): void
    {
        $coordinator = $this->coordinator();

        $this->makeOrder('ORD-900031', Order::STATUS_IN_PRODUCTION, $coordinator);
        $this->makeOrder('ORD-900032', Order::STATUS_PENDING);

        foreach (['admin', 'super-admin'] as $slug) {
            $admin = $this->userWithRole($slug, ['orders.view']);

            $this->actingAs($admin)
                ->get(route('orders.index'))
                ->assertOk()
                ->assertSee('ORD-900031')
                ->assertSee('ORD-900032');
        }
    }

    public function test_user_without_orders_view_permission_is_denied(): void
    {
        $user = $this->userWithRole('no-orders-test', ['production.manage']);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertForbidden();
    }

    public function test_delivered_orders_are_hidden_by_default_and_available_by_filter(): void
    {
        $admin = $this->userWithRole('admin', ['orders.view']);

        $this->makeOrder('ORD-900041', Order::STATUS_DELIVERED);
        $this->makeOrder('ORD-900042', Order::STATUS_IN_PRODUCTION);

        $this->actingAs($admin)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertDontSee('ORD-900041')
            ->assertSee('ORD-900042');

        $this->actingAs($admin)
            ->get(route('orders.index', ['status' => 'delivered']))
            ->assertOk()
            ->assertSee('ORD-900041')
            ->assertDontSee('ORD-900042');
    }

    public function test_non_admin_delivered_filter_shows_only_their_own_delivered_orders(): void
    {
        $user = $this->coordinator();
        $other = $this->coordinator();

        $this->makeOrder('ORD-900051', Order::STATUS_DELIVERED, $user);
        $this->makeOrder('ORD-900052', Order::STATUS_DELIVERED, $other);

        $this->actingAs($user)
            ->get(route('orders.index', ['status' => 'delivered']))
            ->assertOk()
            ->assertSee('ORD-900051')
            ->assertDontSee('ORD-900052');
    }

    public function test_orders_are_listed_most_recently_updated_first(): void
    {
        $admin = $this->userWithRole('admin', ['orders.view']);

        $older = $this->makeOrder('ORD-900061', Order::STATUS_IN_PRODUCTION);
        $newer = $this->makeOrder('ORD-900062', Order::STATUS_IN_PRODUCTION);

        Order::query()->whereKey($older->id)->update([
            'updated_at' => now()->subDays(3),
        ]);
        Order::query()->whereKey($newer->id)->update([
            'updated_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSeeInOrder(['ORD-900062', 'ORD-900061']);
    }
}
