<?php

namespace Tests\Feature;

use App\Actions\Measurements\ResolveOrderItemMeasurementEligibility;
use App\Models\Contact;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemMeasurementEligibilityAudit;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MeasurementEligibilityAndAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(string $permissionSlug): User
    {
        $permission = Permission::create([
            'name' => $permissionSlug,
            'slug' => $permissionSlug,
            'description' => $permissionSlug . '.',
        ]);

        $role = Role::create([
            'name' => 'Role ' . $permissionSlug,
            'slug' => 'role-' . str_replace('.', '-', $permissionSlug),
            'description' => 'Test role.',
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['is_active' => true]);
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

    private function addItem(Order $order, ?string $eligibility = null): OrderItem
    {
        $attributes = [
            'order_id' => $order->id,
            'item_name' => 'Test uniform',
            'quantity' => 10,
            'unit' => 'piece',
            'unit_price' => 3000,
            'line_total' => 30000,
            'sort_order' => 0,
        ];

        if ($eligibility !== null) {
            $attributes['measurement_eligibility'] = $eligibility;
        }

        return OrderItem::create($attributes);
    }

    private function addRevision(OrderItem $item, string $status): void
    {
        MeasurementSubmission::create([
            'order_item_id' => $item->id,
            'revision' => 1,
            'measurement_method' => 'individual',
            'input_source' => 'manual',
            'status' => $status,
        ]);
    }

    private function decide(User $actor, OrderItem $item, string $eligibility): OrderItem
    {
        return app(ResolveOrderItemMeasurementEligibility::class)->execute($actor, $item, [
            'eligibility' => $eligibility,
            'reason' => 'Decision recorded for automated testing purposes.',
        ]);
    }

    private function coordinator(): User
    {
        return $this->userWith('production.manage');
    }

    // ---- Eligibility guards ----

    public function test_no_op_decision_is_rejected_and_not_audited(): void
    {
        $actor = $this->userWith('measurements.eligibility.manage');
        $item = $this->addItem($this->createOrder());

        $this->decide($actor, $item, 'required');

        try {
            $this->decide($actor, $item, 'required');
            $this->fail('Expected a no-op decision to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('eligibility', $e->errors());
        }

        $this->assertSame(1, OrderItemMeasurementEligibilityAudit::query()->count());
    }

    public function test_not_required_is_blocked_with_a_submitted_revision(): void
    {
        $actor = $this->userWith('measurements.eligibility.manage');
        $item = $this->addItem($this->createOrder(), 'required');
        $this->addRevision($item, 'submitted');

        $this->expectException(ValidationException::class);
        $this->decide($actor, $item, 'not_required');
    }

    public function test_not_required_is_blocked_with_an_accepted_revision(): void
    {
        $actor = $this->userWith('measurements.eligibility.manage');
        $item = $this->addItem($this->createOrder(), 'required');
        $this->addRevision($item, 'accepted');

        $this->expectException(ValidationException::class);
        $this->decide($actor, $item, 'not_required');
    }

    public function test_not_required_is_allowed_when_only_a_draft_exists(): void
    {
        $actor = $this->userWith('measurements.eligibility.manage');
        $item = $this->addItem($this->createOrder(), 'required');
        $this->addRevision($item, 'draft');

        $updated = $this->decide($actor, $item, 'not_required');

        $this->assertSame('not_required', $updated->measurement_eligibility);
    }

    public function test_rejected_decision_leaves_item_unchanged(): void
    {
        $actor = $this->userWith('measurements.eligibility.manage');
        $item = $this->addItem($this->createOrder(), 'required');
        $this->addRevision($item, 'accepted');

        try {
            $this->decide($actor, $item, 'not_required');
        } catch (ValidationException) {
        }

        $this->assertSame('required', $item->fresh()->measurement_eligibility);
        $this->assertSame(0, OrderItemMeasurementEligibilityAudit::query()->count());
    }

    public function test_audit_entries_cannot_be_updated_or_deleted(): void
    {
        $actor = $this->userWith('measurements.eligibility.manage');
        $item = $this->addItem($this->createOrder());
        $this->decide($actor, $item, 'required');

        $audit = OrderItemMeasurementEligibilityAudit::query()->sole();

        try {
            $audit->update(['reason' => 'Tampered reason text here.']);
            $this->fail('Expected update to be rejected.');
        } catch (\LogicException) {
        }

        try {
            $audit->delete();
            $this->fail('Expected delete to be rejected.');
        } catch (\LogicException) {
        }

        $this->assertSame(1, OrderItemMeasurementEligibilityAudit::query()->count());
    }

    // ---- assignCoordinator and the production gate ----

    public function test_undecided_item_does_not_block_coordinator_assignment(): void
    {
        $assigner = $this->userWith('orders.assign');
        $coordinator = $this->coordinator();
        $order = $this->createOrder();
        $this->addItem($order);

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(Order::STATUS_IN_PRODUCTION, $order->fresh()->status);
    }

    public function test_not_required_item_allows_coordinator_assignment(): void
    {
        $assigner = $this->userWith('orders.assign');
        $coordinator = $this->coordinator();
        $order = $this->createOrder();
        $this->addItem($order, 'not_required');

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(Order::STATUS_IN_PRODUCTION, $order->fresh()->status);
        $this->assertDatabaseHas('order_assignments', [
            'order_id' => $order->id,
            'user_id' => $coordinator->id,
        ]);
    }

    public function test_required_item_with_accepted_revision_allows_assignment(): void
    {
        $assigner = $this->userWith('orders.assign');
        $coordinator = $this->coordinator();
        $order = $this->createOrder();
        $item = $this->addItem($order, 'required');
        $this->addRevision($item, 'accepted');

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(Order::STATUS_IN_PRODUCTION, $order->fresh()->status);
    }

    public function test_reassignment_on_in_production_order_is_not_gated(): void
    {
        $assigner = $this->userWith('orders.assign');
        $first = $this->coordinator();
        $second = User::factory()->create(['is_active' => true]);
        $second->roles()->attach($first->roles()->first());

        $order = $this->createOrder();
        $item = $this->addItem($order, 'not_required');

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), ['user_id' => $first->id]);

        $this->assertSame(Order::STATUS_IN_PRODUCTION, $order->fresh()->status);

        // Item reverts to unresolved state directly, simulating legacy data.
        $item->forceFill(['measurement_eligibility' => 'pending'])->save();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), ['user_id' => $second->id])
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('order_assignments', [
            'order_id' => $order->id,
            'user_id' => $second->id,
            'ended_at' => null,
        ]);
    }
}
