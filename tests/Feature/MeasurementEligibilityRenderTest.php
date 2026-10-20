<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementEligibilityRenderTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(array $slugs): User
    {
        $role = Role::create([
            'name' => 'Role ' . uniqid(),
            'slug' => 'admin',
            'description' => 'Test role.',
        ]);

        foreach ($slugs as $slug) {
            $role->permissions()->attach(Permission::create([
                'name' => $slug,
                'slug' => $slug,
                'description' => $slug . '.',
            ]));
        }

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function orderWithItem(string $status = Order::STATUS_APPROVED): array
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

        $order = Order::create([
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

        $item = OrderItem::create([
            'order_id' => $order->id,
            'item_name' => 'Render test uniform',
            'quantity' => 10,
            'unit' => 'piece',
            'unit_price' => 3000,
            'line_total' => 30000,
            'sort_order' => 0,
        ]);

        return [$order, $item];
    }

    public function test_authorized_user_sees_decide_button_and_pending_banner(): void
    {
        [$order] = $this->orderWithItem();
        $user = $this->userWith(['orders.view', 'measurements.eligibility.manage']);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Measurement Eligibility')
            ->assertSee('open-measurement-eligibility', false)
            ->assertSee('Production cannot start until every item is decided');
    }

    public function test_user_without_permission_sees_status_but_no_decide_button(): void
    {
        [$order] = $this->orderWithItem();
        $user = $this->userWith(['orders.view']);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Measurement Eligibility')
            ->assertDontSee('open-measurement-eligibility', false);
    }

    public function test_decide_button_shows_in_production_until_a_plan_exists(): void
    {
        [$order] = $this->orderWithItem(Order::STATUS_IN_PRODUCTION);
        $user = $this->userWith(['orders.view', 'measurements.eligibility.manage']);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('open-measurement-eligibility', false);

        \App\Models\ProductionPlan::create([
            'order_id' => $order->id,
            'coordinator_id' => $user->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('open-measurement-eligibility', false);
    }

    public function test_decision_history_is_rendered(): void
    {
        [$order, $item] = $this->orderWithItem();
        $user = $this->userWith(['orders.view', 'measurements.eligibility.manage']);

        app(\App\Actions\Measurements\ResolveOrderItemMeasurementEligibility::class)
            ->execute($user, $item, [
                'eligibility' => 'required',
                'reason' => 'Tailored garments need individual measurements.',
            ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Decision history')
            ->assertSee('Tailored garments need individual measurements.')
            ->assertSee('Measurements required');
    }
}
