<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemMeasurementEligibilityAudit;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementEligibilityUiTest extends TestCase
{
    use RefreshDatabase;

    private function manager(bool $active = true): User
    {
        $permission = Permission::create([
            'name' => 'Manage Measurement Eligibility',
            'slug' => 'measurements.eligibility.manage',
            'description' => 'Test permission.',
        ]);
        $role = Role::create([
            'name' => 'Eligibility Manager',
            'slug' => 'eligibility-manager',
            'description' => 'Test role.',
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['is_active' => $active]);
        $user->roles()->attach($role);

        return $user;
    }

    private function createOrder(string $status = Order::STATUS_APPROVED): Order
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(),
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'created_by' => User::factory()->create()->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
        ]);

        return Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'order_date' => now()->toDateString(),
            'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(),
            'status' => $status,
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
        ]);
    }

    private function addItem(Order $order): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'item_name' => 'Test uniform',
            'quantity' => 10,
            'unit' => 'piece',
            'unit_price' => 3000,
            'line_total' => 30000,
            'sort_order' => 0,
        ]);
    }

    private function payload(string $eligibility = 'required'): array
    {
        return [
            'eligibility' => $eligibility,
            'reason' => 'Decision recorded for automated testing.',
        ];
    }

    public function test_authorized_user_can_decide_eligibility(): void
    {
        $order = $this->createOrder();
        $item = $this->addItem($order);

        $this->actingAs($this->manager())
            ->post(route('orders.items.measurement-eligibility', [$order, $item]), $this->payload())
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('status');

        $this->assertSame('required', $item->fresh()->measurement_eligibility);
        $this->assertSame(1, OrderItemMeasurementEligibilityAudit::query()->count());
    }

    public function test_user_without_permission_is_denied(): void
    {
        $order = $this->createOrder();
        $item = $this->addItem($order);

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->post(route('orders.items.measurement-eligibility', [$order, $item]), $this->payload())
            ->assertForbidden();

        $this->assertSame('pending', $item->fresh()->measurement_eligibility);
    }

    public function test_inactive_user_cannot_decide(): void
    {
        $order = $this->createOrder();
        $item = $this->addItem($order);

        $this->actingAs($this->manager(false))
            ->post(route('orders.items.measurement-eligibility', [$order, $item]), $this->payload());

        $this->assertSame('pending', $item->fresh()->measurement_eligibility);
        $this->assertSame(0, OrderItemMeasurementEligibilityAudit::query()->count());
    }

    public function test_item_from_another_order_returns_404(): void
    {
        $order = $this->createOrder();
        $otherItem = $this->addItem($this->createOrder());

        $this->actingAs($this->manager())
            ->post(route('orders.items.measurement-eligibility', [$order, $otherItem]), $this->payload())
            ->assertNotFound();

        $this->assertSame('pending', $otherItem->fresh()->measurement_eligibility);
    }

    public function test_decision_is_allowed_in_production_without_plan_and_locked_with_one(): void
    {
        $order = $this->createOrder(Order::STATUS_IN_PRODUCTION);
        $item = $this->addItem($order);
        $manager = $this->manager();

        $this->actingAs($manager)
            ->post(route('orders.items.measurement-eligibility', [$order, $item]), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame('required', $item->fresh()->measurement_eligibility);

        \App\Models\ProductionPlan::create([
            'order_id' => $order->id,
            'coordinator_id' => $manager->id,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->from(route('orders.show', $order))
            ->post(route('orders.items.measurement-eligibility', [$order, $item]), $this->payload('not_required'))
            ->assertSessionHasErrors('eligibility');

        $this->assertSame('required', $item->fresh()->measurement_eligibility);
    }

    public function test_short_reason_is_rejected_with_a_validation_error(): void
    {
        $order = $this->createOrder();
        $item = $this->addItem($order);

        $this->actingAs($this->manager())
            ->from(route('orders.show', $order))
            ->post(route('orders.items.measurement-eligibility', [$order, $item]), [
                'eligibility' => 'required',
                'reason' => 'short',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $item->fresh()->measurement_eligibility);
    }
}
