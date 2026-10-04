<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Organization;
use App\Models\Contact;
use App\Models\Quotation;

use App\Models\OrderItem;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanActivity;
use App\Models\QualityControlInspection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QualityControlTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $role = Role::factory()->create([
            'name' => 'QC Test Role ' . uniqid(),
            'slug' => 'qc-test-' . uniqid(),
        ]);

        $role->permissions()->createMany(
            collect($permissions)
                ->map(fn (string $slug) => [
                    'name' => $slug,
                    'slug' => $slug,
                ])
                ->all()
        );

        $user->roles()->attach($role);

        return $user;
    }

    private function orderReadyForQualityControl(): Order
    {
        $order = $this->createQcOrder();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_name' => 'School Shirt',
            'unit' => 'piece',
            'quantity' => 20,
            'unit_price' => 5000,
            'line_total' => 100000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_name' => 'Fabric',
            'unit' => 'yard',
            'quantity' => 50,
            'unit_price' => 3000,
            'line_total' => 150000,
        ]);

        return $order->fresh('items');
    }

    private function createQcOrder(
        string $status = Order::STATUS_READY_FOR_QUALITY_CONTROL
    ): Order {
        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $quotationCreator = User::factory()->create([
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(),
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'created_by' => $quotationCreator->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
            'terms' => null,
            'notes' => 'Quality Control test quotation.',
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
            'terms' => null,
            'notes' => 'Quality Control test order.',
        ]);
    }

    public function test_quality_control_staff_can_view_the_qc_queue(): void
    {
        $user = $this->userWithPermissions([
            'quality-control.view',
        ]);

        $order = $this->orderReadyForQualityControl();

        $this->actingAs($user)
            ->get(route('quality-control.index'))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_staff_without_quality_control_permission_cannot_view_the_qc_queue(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('quality-control.index'))
            ->assertForbidden();
    }

    public function test_quality_control_staff_can_start_an_inspection(): void
    {
        $user = $this->userWithPermissions([
            'quality-control.view',
            'quality-control.inspect',
        ]);

        $order = $this->orderReadyForQualityControl();

        $response = $this->actingAs($user)
            ->post(route('quality-control.start', $order));

        $inspection = QualityControlInspection::query()
            ->where('order_id', $order->id)
            ->first();

        $this->assertNotNull($inspection);

        $this->assertDatabaseCount(
            'quality_control_inspection_items',
            $order->items->count()
        );

        $response
            ->assertRedirect(route('quality-control.show', $inspection))
            ->assertSessionHas('success');
    }

    public function test_piece_items_capture_failed_quantity(): void
    {
        $user = $this->userWithPermissions([
            'quality-control.view',
            'quality-control.inspect',
            'quality-control.approve',
        ]);

        $order = $this->orderReadyForQualityControl();

        $this->actingAs($user)
            ->post(route('quality-control.start', $order));

        $inspection = QualityControlInspection::query()
            ->where('order_id', $order->id)
            ->firstOrFail();

        $piece = $inspection->items()
            ->where('unit', 'piece')
            ->firstOrFail();

        $yard = $inspection->items()
            ->where('unit', 'yard')
            ->firstOrFail();

        $response = $this->actingAs($user)->post(
            route('quality-control.complete', $inspection),
            [
                'items' => [
                    $piece->id => [
                        'failed_quantity' => 3,
                        'findings' => 'Three shirts have stitching defects.',
                    ],
                    $yard->id => [
                        'findings' => 'Fabric checked and accepted.',
                    ],
                ],
                'findings' => 'Partial failure found.',
                'correction_notes' => 'Rework the three defective shirts.',
            ]
        );

        $response
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('quality_control_inspection_items', [
            'id' => $piece->id,
            'failed_quantity' => 3,
        ]);

        $this->assertDatabaseHas('quality_control_inspections', [
            'id' => $inspection->id,
            'status' => QualityControlInspection::STATUS_COMPLETED,
            'result' => QualityControlInspection::RESULT_FAIL,
            'correction_required' => true,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_CORRECTION_REQUIRED,
        ]);
    }

    public function test_failed_quantity_cannot_exceed_ordered_quantity(): void
    {
        $user = $this->userWithPermissions([
            'quality-control.view',
            'quality-control.inspect',
            'quality-control.approve',
        ]);

        $order = $this->orderReadyForQualityControl();

        $this->actingAs($user)
            ->post(route('quality-control.start', $order));

        $inspection = QualityControlInspection::query()
            ->where('order_id', $order->id)
            ->firstOrFail();

        $piece = $inspection->items()
            ->where('unit', 'piece')
            ->firstOrFail();

        $response = $this->actingAs($user)->post(
            route('quality-control.complete', $inspection),
            [
                'items' => [
                    $piece->id => [
                        'failed_quantity' => 21,
                    ],
                ],
            ]
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('quality_control_inspections', [
            'id' => $inspection->id,
            'status' => QualityControlInspection::STATUS_IN_PROGRESS,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_READY_FOR_QUALITY_CONTROL,
        ]);
    }

    public function test_passing_qc_moves_order_to_ready(): void
    {
        $user = $this->userWithPermissions([
            'quality-control.view',
            'quality-control.inspect',
            'quality-control.approve',
        ]);

        $order = $this->orderReadyForQualityControl();

        $this->actingAs($user)
            ->post(route('quality-control.start', $order));

        $inspection = QualityControlInspection::query()
            ->where('order_id', $order->id)
            ->firstOrFail();

        $response = $this->actingAs($user)->post(
            route('quality-control.complete', $inspection),
            [
                'items' => $inspection->items
                    ->mapWithKeys(fn ($item) => [
                        $item->id => [
                            'failed_quantity' => $item->isPiece() ? 0 : null,
                        ],
                    ])
                    ->all(),
                'findings' => 'All inspected items passed.',
            ]
        );

        $response
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('quality_control_inspections', [
            'id' => $inspection->id,
            'status' => QualityControlInspection::STATUS_COMPLETED,
            'result' => QualityControlInspection::RESULT_PASS,
            'correction_required' => false,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_READY,
        ]);
    }

    public function test_failed_qc_can_be_returned_to_production_for_correction(): void
    {
        $user = $this->userWithPermissions([
            'quality-control.view',
            'quality-control.inspect',
            'quality-control.approve',
            'production.manage',
        ]);

        $order = $this->orderReadyForQualityControl();

        $plan = ProductionPlan::create([
            'order_id' => $order->id,
            'coordinator_id' => $user->id,
            'created_by' => $user->id,
        ]);

        $productionActivity = \App\Models\ProductionActivity::create([
            'name' => 'Correction Production Activity',
            'description' => 'Production activity used for the QC correction workflow test.',
            'sort_order' => 1,
            'is_required' => true,
            'is_active' => true,
        ]);

        $activity = ProductionPlanActivity::create([
            'production_plan_id' => $plan->id,
            'production_activity_id' => $productionActivity->id,
            'sort_order' => 1,
            'status' => ProductionPlanActivity::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('quality-control.start', $order));

        $inspection = QualityControlInspection::query()
            ->where('order_id', $order->id)
            ->firstOrFail();

        $piece = $inspection->items()
            ->where('unit', 'piece')
            ->firstOrFail();

        $this->actingAs($user)->post(
            route('quality-control.complete', $inspection),
            [
                'items' => [
                    $piece->id => [
                        'failed_quantity' => 2,
                    ],
                ],
                'correction_notes' => 'Repair two defective shirts.',
            ]
        )->assertRedirect();

        $response = $this->actingAs($user)->post(
            route('orders.production.correction', $order)
        );

        $response
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_IN_PRODUCTION,
        ]);

        $preservedActivity = ProductionPlanActivity::query()
            ->findOrFail($activity->id);

        $this->assertSame(
            ProductionPlanActivity::STATUS_COMPLETED,
            $preservedActivity->status
        );

        $this->assertNotNull($preservedActivity->started_at);
        $this->assertNotNull($preservedActivity->completed_at);

        $this->assertSame(
            $activity->started_at->toDateTimeString(),
            $preservedActivity->started_at->toDateTimeString()
        );

        $this->assertSame(
            $activity->completed_at->toDateTimeString(),
            $preservedActivity->completed_at->toDateTimeString()
        );

        $this->assertDatabaseHas('quality_control_inspections', [
            'id' => $inspection->id,
            'result' => QualityControlInspection::RESULT_FAIL,
            'status' => QualityControlInspection::STATUS_COMPLETED,
        ]);
    }

    public function test_coordinator_can_resubmit_corrected_order_to_quality_control_without_restarting_production(): void
    {
        $user = $this->userWithPermissions([
            'quality-control.view',
            'quality-control.inspect',
            'quality-control.approve',
            'production.manage',
        ]);

        $order = $this->orderReadyForQualityControl();

        \App\Models\OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);

        $plan = ProductionPlan::create([
            'order_id' => $order->id,
            'coordinator_id' => $user->id,
            'created_by' => $user->id,
        ]);

        $productionActivity = \App\Models\ProductionActivity::create([
            'name' => 'QC Correction Resubmission Activity',
            'description' => 'Production activity used for QC resubmission regression coverage.',
            'sort_order' => 1,
            'is_required' => true,
            'is_active' => true,
        ]);

        $activity = ProductionPlanActivity::create([
            'production_plan_id' => $plan->id,
            'production_activity_id' => $productionActivity->id,
            'sort_order' => 1,
            'status' => ProductionPlanActivity::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('quality-control.start', $order))
            ->assertRedirect();

        $inspection = QualityControlInspection::query()
            ->where('order_id', $order->id)
            ->firstOrFail();

        $piece = $inspection->items()
            ->where('unit', 'piece')
            ->firstOrFail();

        $this->actingAs($user)
            ->post(
                route('quality-control.complete', $inspection),
                [
                    'items' => [
                        $piece->id => [
                            'failed_quantity' => 2,
                        ],
                    ],
                    'correction_notes' => 'Repair two defective shirts.',
                ]
            )
            ->assertRedirect();

        $this->actingAs($user)
            ->post(
                route('orders.production.correction', $order),
                [
                    'notes' => 'QC correction work has been returned to Production.',
                ]
            )
            ->assertRedirect(route('orders.show', $order));

        $activityBeforeResubmission = ProductionPlanActivity::query()
            ->findOrFail($activity->id);

        $this->assertSame(
            ProductionPlanActivity::STATUS_COMPLETED,
            $activityBeforeResubmission->status
        );

        $startedAt = $activityBeforeResubmission->started_at->toDateTimeString();
        $completedAt = $activityBeforeResubmission->completed_at->toDateTimeString();

        $response = $this->actingAs($user)->post(
            route(
                'orders.production.correction.resubmit-quality-control',
                $order
            ),
            [
                'correction_confirmed' => '1',
                'notes' => 'All Quality Control corrections have been completed and checked.',
            ]
        );

        $response
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_READY_FOR_QUALITY_CONTROL,
        ]);

        $resubmittedActivity = ProductionPlanActivity::query()
            ->findOrFail($activity->id);

        $this->assertSame(
            ProductionPlanActivity::STATUS_COMPLETED,
            $resubmittedActivity->status
        );

        $this->assertSame(
            $startedAt,
            $resubmittedActivity->started_at->toDateTimeString()
        );

        $this->assertSame(
            $completedAt,
            $resubmittedActivity->completed_at->toDateTimeString()
        );

        $this->assertDatabaseHas('production_plans', [
            'id' => $plan->id,
            'coordinator_checked_by' => $user->id,
            'coordinator_check_notes' =>
                'All Quality Control corrections have been completed and checked.',
        ]);

        $this->assertNotNull(
            ProductionPlan::query()
                ->findOrFail($plan->id)
                ->coordinator_checked_at
        );

        $this->assertDatabaseHas('quality_control_inspections', [
            'id' => $inspection->id,
            'result' => QualityControlInspection::RESULT_FAIL,
            'status' => QualityControlInspection::STATUS_COMPLETED,
            'correction_required' => true,
        ]);

        $this->assertDatabaseCount(
            'quality_control_inspections',
            1
        );
    }

    public function test_new_qc_attempt_can_be_started_after_correction_and_coordinator_handoff(): void
    {
        $user = $this->userWithPermissions([
            'quality-control.view',
            'quality-control.inspect',
        ]);

        $order = $this->orderReadyForQualityControl();

        $first = QualityControlInspection::create([
            'order_id' => $order->id,
            'inspected_by' => $user->id,
            'status' => QualityControlInspection::STATUS_COMPLETED,
            'result' => QualityControlInspection::RESULT_FAIL,
            'correction_required' => true,
            'correction_notes' => 'Correct defective pieces.',
            'inspected_at' => now(),
        ]);

        $order->update([
            'status' => Order::STATUS_READY_FOR_QUALITY_CONTROL,
        ]);

        $response = $this->actingAs($user)
            ->post(route('quality-control.start', $order));

        $response->assertRedirect();

        $this->assertDatabaseCount(
            'quality_control_inspections',
            2
        );

        $second = QualityControlInspection::query()
            ->where('order_id', $order->id)
            ->whereKeyNot($first->id)
            ->first();

        $this->assertNotNull($second);
        $this->assertSame(
            QualityControlInspection::STATUS_IN_PROGRESS,
            $second->status
        );
    }
}
