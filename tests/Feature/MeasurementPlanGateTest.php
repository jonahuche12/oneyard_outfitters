<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\ProductionActivity;
use App\Models\ProductionPlan;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementPlanGateTest extends TestCase
{
    use RefreshDatabase;

    private function coordinatorFor(Order $order): User
    {
        $role = Role::create(['name' => 'Coordinator', 'slug' => 'coordinator', 'description' => 'Test.']);
        foreach (['production.manage', 'orders.view'] as $slug) {
            $role->permissions()->attach(Permission::create([
                'name' => $slug, 'slug' => $slug, 'description' => $slug . '.',
            ]));
        }

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
            'reason' => 'Test assignment.',
        ]);

        return $user;
    }

    private function inProductionOrder(?string $eligibility): Order
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(),
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'created_by' => User::factory()->create()->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 30000, 'discount' => 0, 'additional_charges' => 0, 'total' => 30000,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'order_date' => now()->toDateString(),
            'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(),
            'status' => Order::STATUS_IN_PRODUCTION,
            'subtotal' => 30000, 'discount' => 0, 'additional_charges' => 0, 'total' => 30000,
        ]);

        $attributes = [
            'order_id' => $order->id, 'item_name' => 'Plan gate uniform', 'quantity' => 10,
            'unit' => 'piece', 'unit_price' => 3000, 'line_total' => 30000, 'sort_order' => 0,
        ];
        if ($eligibility !== null) {
            $attributes['measurement_eligibility'] = $eligibility;
        }
        OrderItem::create($attributes);

        return $order;
    }

    private function requiredActivityIds(): array
    {
        ProductionActivity::query()->delete();

        $activity = new ProductionActivity();
        $activity->forceFill([
            'name' => 'Gate test activity',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 1,
        ])->save();

        return [$activity->id];
    }

    public function test_undecided_item_blocks_plan_creation(): void
    {
        $order = $this->inProductionOrder(null);
        $coordinator = $this->coordinatorFor($order);

        $this->actingAs($coordinator)
            ->from(route('orders.show', $order))
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => $this->requiredActivityIds(),
            ])
            ->assertSessionHasErrors('measurements');

        $this->assertSame(0, ProductionPlan::where('order_id', $order->id)->count());
    }

    public function test_not_required_item_allows_plan_creation(): void
    {
        $order = $this->inProductionOrder('not_required');
        $coordinator = $this->coordinatorFor($order);

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => $this->requiredActivityIds(),
            ])
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(1, ProductionPlan::where('order_id', $order->id)->count());
    }
}
