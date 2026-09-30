<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\ProductionActivity;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanActivityEvidence;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderProductionPlanTest extends TestCase
{
    use RefreshDatabase;

    private function permission(string $slug, string $name): Permission
    {
        return Permission::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $name . '.',
        ]);
    }

    private function roleWithPermissions(string $slug, array $permissions): Role
    {
        $role = Role::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'description' => 'Test role.',
        ]);

        foreach ($permissions as $permission) {
            $role->permissions()->attach($permission);
        }

        return $role;
    }

    private function createOrder(string $status = Order::STATUS_APPROVED): Order
    {
        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        $quotation = \App\Models\Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(),
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'created_by' => User::factory()->create()->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30,
            'status' => \App\Models\Quotation::STATUS_ACCEPTED,
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

    private function seedActivities(): void
    {
        $this->seed(\Database\Seeders\ProductionActivitySeeder::class);
    }

    public function test_authorized_staff_can_assign_approved_order_to_production_coordinator(): void
    {
        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $coordinator = User::factory()->create([
            'is_active' => true,
        ]);
        $coordinator->roles()->attach($coordinatorRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();

        $this->assertSame(
            Order::STATUS_IN_PRODUCTION,
            $order->status
        );

        $this->assertDatabaseHas('order_assignments', [
            'order_id' => $order->id,
            'user_id' => $coordinator->id,
            'assigned_by' => $assigner->id,
        ]);
    }

    public function test_user_without_assign_permission_cannot_assign_order(): void
    {
        $assigner = User::factory()->create(['is_active' => true]);

        $coordinatorPermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$coordinatorPermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('order_assignments', 0);
    }

    public function test_inactive_or_unauthorized_staff_cannot_be_assigned(): void
    {
        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $inactive = User::factory()->create(['is_active' => false]);
        $unauthorized = User::factory()->create(['is_active' => true]);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $inactive->id,
            ])
            ->assertSessionHasErrors('user_id');

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $unauthorized->id,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('order_assignments', 0);
    }

    public function test_reassignment_ends_previous_assignment_and_preserves_history(): void
    {
        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $first = User::factory()->create(['is_active' => true]);
        $second = User::factory()->create(['is_active' => true]);

        $first->roles()->attach($coordinatorRole);
        $second->roles()->attach($coordinatorRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $first->id,
            ])
            ->assertRedirect();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $second->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('order_assignments', 2);

        $this->assertDatabaseHas('order_assignments', [
            'order_id' => $order->id,
            'user_id' => $first->id,
        ]);

        $this->assertDatabaseHas('order_assignments', [
            'order_id' => $order->id,
            'user_id' => $second->id,
            'ended_at' => null,
        ]);

        $this->assertNotNull(
            $order->assignments()
                ->where('user_id', $first->id)
                ->firstOrFail()
                ->ended_at
        );
    }

    public function test_coordinator_can_create_production_plan_with_required_activities(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect();

        $activities = ProductionActivity::query()
            ->orderBy('sort_order')
            ->get();

        $selectedIds = $activities
            ->whereIn('name', [
                'Material Purchase',
                'Sewing',
                'Quality Control',
                'Delivery',
            ])
            ->pluck('id')
            ->all();

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => $selectedIds,
            ])
            ->assertRedirect(route('orders.show', $order));

        $plan = ProductionPlan::where('order_id', $order->id)
            ->firstOrFail();

        $this->assertCount(4, $plan->activities);

        $this->assertDatabaseHas('production_plan_activities', [
            'production_plan_id' => $plan->id,
            'production_activity_id' => ProductionActivity::where(
                'name',
                'Quality Control'
            )->value('id'),
            'status' => 'pending',
        ]);
    }

    public function test_quality_control_and_delivery_cannot_be_omitted(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $role = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($role);

        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect();

        $materialPurchase = ProductionActivity::where(
            'name',
            'Material Purchase'
        )->value('id');

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => [$materialPurchase],
            ])
            ->assertSessionHasErrors('activity_ids');

        $this->assertDatabaseCount('production_plans', 0);
    }

    public function test_non_coordinator_with_production_permission_cannot_create_plan(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $role = $this->roleWithPermissions(
            'production',
            [$managePermission]
        );

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        $order = $this->createOrder(Order::STATUS_IN_PRODUCTION);

        $this->actingAs($user)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => ProductionActivity::query()
                    ->where('is_required', true)
                    ->pluck('id')
                    ->all(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('production_plans', 0);
    }

    public function test_production_plan_cannot_be_created_twice(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect();

        $required = ProductionActivity::where('is_required', true)
            ->pluck('id')
            ->all();

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => $required,
            ])
            ->assertRedirect();

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => $required,
            ])
            ->assertSessionHasErrors('activity_ids');

        $this->assertDatabaseCount('production_plans', 1);
    }

    public function test_same_current_coordinator_cannot_be_assigned_again(): void
    {
        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('order_assignments', 1);
    }

    public function test_reassignment_leaves_exactly_one_active_assignment(): void
    {
        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $first = User::factory()->create(['is_active' => true]);
        $second = User::factory()->create(['is_active' => true]);

        $first->roles()->attach($coordinatorRole);
        $second->roles()->attach($coordinatorRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $first->id,
            ])
            ->assertRedirect();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $second->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('order_assignments', 2);

        $this->assertSame(
            1,
            \App\Models\OrderAssignment::query()
                ->where('order_id', $order->id)
                ->whereNull('ended_at')
                ->count()
        );

        $this->assertDatabaseHas('order_assignments', [
            'order_id' => $order->id,
            'user_id' => $second->id,
            'ended_at' => null,
        ]);
    }

    public function test_production_plan_records_current_coordinator(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect();

        $required = ProductionActivity::query()
            ->where('is_required', true)
            ->pluck('id')
            ->all();

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => $required,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('production_plans', [
            'order_id' => $order->id,
            'coordinator_id' => $coordinator->id,
            'created_by' => $coordinator->id,
        ]);
    }

    public function test_inactive_activity_cannot_be_selected_for_production_plan(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect();

        $inactive = ProductionActivity::query()
            ->where('is_required', false)
            ->firstOrFail();

        $inactive->update([
            'is_active' => false,
        ]);

        $required = ProductionActivity::query()
            ->where('is_required', true)
            ->pluck('id')
            ->all();

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => array_merge($required, [$inactive->id]),
            ])
            ->assertSessionHasErrors('activity_ids');

        $this->assertDatabaseCount('production_plans', 0);
    }

    public function test_production_plan_cannot_be_created_for_order_not_in_production(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $order = $this->createOrder(Order::STATUS_PENDING);

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => ProductionActivity::query()
                    ->where('is_required', true)
                    ->pluck('id')
                    ->all(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('production_plans', 0);
    }


    public function test_current_coordinator_can_start_pending_production_activity(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect();

        $activity = ProductionActivity::where('name', 'Sewing')
            ->firstOrFail();

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => ProductionActivity::whereIn('name', [
                    'Sewing',
                    'Quality Control',
                    'Delivery',
                ])->pluck('id')->all(),
            ])
            ->assertRedirect();

        $planActivity = $order->productionPlan
            ->activities()
            ->where('production_activity_id', $activity->id)
            ->firstOrFail();

        $this->actingAs($coordinator)
            ->post(
                route(
                    'orders.production-plan.activities.start',
                    [$order, $planActivity]
                ),
                [
                    'notes' => 'Sewing started.',
                ]
            )
            ->assertRedirect(route('orders.show', $order));

        $planActivity->refresh();

        $this->assertSame(
            'started',
            $planActivity->status
        );

        $this->assertNotNull($planActivity->started_at);
        $this->assertNull($planActivity->completed_at);
        $this->assertSame('Sewing started.', $planActivity->notes);
    }

    public function test_current_coordinator_can_complete_started_production_activity(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect();

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => ProductionActivity::whereIn('name', [
                    'Sewing',
                    'Quality Control',
                    'Delivery',
                ])->pluck('id')->all(),
            ])
            ->assertRedirect();

        $planActivity = $order->productionPlan
            ->activities()
            ->whereHas(
                'activity',
                fn ($query) => $query->where('name', 'Sewing')
            )
            ->firstOrFail();

        $this->actingAs($coordinator)
            ->post(
                route(
                    'orders.production-plan.activities.start',
                    [$order, $planActivity]
                )
            )
            ->assertRedirect();

        $this->actingAs($coordinator)
            ->post(
                route(
                    'orders.production-plan.activities.complete',
                    [$order, $planActivity]
                ),
                [
                    'evidence' => UploadedFile::fake()->create(
                        'sewing-completed.pdf',
                        512,
                        'application/pdf'
                    ),
                    'notes' => 'Sewing completed and ready for QC.',
                ]
            )
            ->assertRedirect(route('orders.show', $order));

        $planActivity->refresh();

        $this->assertSame(
            'completed',
            $planActivity->status
        );

        $this->assertNotNull($planActivity->started_at);
        $this->assertNotNull($planActivity->completed_at);
        $this->assertSame(
            'Sewing completed and ready for QC.',
            $planActivity->notes
        );

        $evidence = ProductionPlanActivityEvidence::query()
            ->where('production_plan_activity_id', $planActivity->id)
            ->firstOrFail();

        $this->assertSame(
            'sewing-completed.pdf',
            $evidence->original_name
        );

        $this->assertSame(
            'application/pdf',
            $evidence->mime_type
        );

        $this->assertSame(
            512 * 1024,
            $evidence->file_size
        );

        $this->assertSame(
            $coordinator->id,
            $evidence->uploaded_by
        );

        Storage::disk('local')->assertExists($evidence->file_path);
    }

    public function test_non_coordinator_with_production_permission_cannot_execute_activity(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $otherUser = User::factory()->create(['is_active' => true]);
        $otherUser->roles()->attach($coordinatorRole);

        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect();

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => ProductionActivity::whereIn('name', [
                    'Sewing',
                    'Quality Control',
                    'Delivery',
                ])->pluck('id')->all(),
            ])
            ->assertRedirect();

        $planActivity = $order->productionPlan
            ->activities()
            ->whereHas(
                'activity',
                fn ($query) => $query->where('name', 'Sewing')
            )
            ->firstOrFail();

        $this->actingAs($otherUser)
            ->post(
                route(
                    'orders.production-plan.activities.start',
                    [$order, $planActivity]
                )
            )
            ->assertForbidden();

        $this->assertSame(
            'pending',
            $planActivity->refresh()->status
        );
    }

    public function test_activity_status_transitions_cannot_be_skipped_or_repeated(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect();

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => ProductionActivity::whereIn('name', [
                    'Sewing',
                    'Quality Control',
                    'Delivery',
                ])->pluck('id')->all(),
            ])
            ->assertRedirect();

        $planActivity = $order->productionPlan
            ->activities()
            ->whereHas(
                'activity',
                fn ($query) => $query->where('name', 'Sewing')
            )
            ->firstOrFail();

        $this->actingAs($coordinator)
            ->post(
                route(
                    'orders.production-plan.activities.complete',
                    [$order, $planActivity]
                ),
                [
                    'evidence' => UploadedFile::fake()->create(
                        'pending-completion-attempt.pdf',
                        256,
                        'application/pdf'
                    ),
                ]
            )
            ->assertStatus(422);

        $this->actingAs($coordinator)
            ->post(
                route(
                    'orders.production-plan.activities.start',
                    [$order, $planActivity]
                )
            )
            ->assertRedirect();

        $this->actingAs($coordinator)
            ->post(
                route(
                    'orders.production-plan.activities.start',
                    [$order, $planActivity]
                )
            )
            ->assertStatus(422);

        $this->actingAs($coordinator)
            ->post(
                route(
                    'orders.production-plan.activities.complete',
                    [$order, $planActivity]
                ),
                [
                    'evidence' => UploadedFile::fake()->create(
                        'sewing-completed.pdf',
                        256,
                        'application/pdf'
                    ),
                ]
            )
            ->assertRedirect();

        $this->actingAs($coordinator)
            ->post(
                route(
                    'orders.production-plan.activities.complete',
                    [$order, $planActivity]
                ),
                [
                    'evidence' => UploadedFile::fake()->create(
                        'sewing-completed-again.pdf',
                        256,
                        'application/pdf'
                    ),
                ]
            )
            ->assertStatus(422);
    }

    public function test_activity_from_another_order_cannot_be_executed(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $firstOrder = $this->createOrder();
        $secondOrder = $this->createOrder();

        foreach ([$firstOrder, $secondOrder] as $order) {
            $this->actingAs($assigner)
                ->post(route('orders.assign-coordinator', $order), [
                    'user_id' => $coordinator->id,
                ])
                ->assertRedirect();

            $this->actingAs($coordinator)
                ->post(route('orders.production-plan.store', $order), [
                    'activity_ids' => ProductionActivity::whereIn('name', [
                        'Sewing',
                        'Quality Control',
                        'Delivery',
                    ])->pluck('id')->all(),
                ])
                ->assertRedirect();
        }

        $activity = $secondOrder->productionPlan
            ->activities()
            ->whereHas(
                'activity',
                fn ($query) => $query->where('name', 'Sewing')
            )
            ->firstOrFail();

        $this->actingAs($coordinator)
            ->post(
                route(
                    'orders.production-plan.activities.start',
                    [$firstOrder, $activity]
                )
            )
            ->assertForbidden();
    }

    public function test_completed_activity_cannot_be_executed_when_order_leaves_production(): void
    {
        $this->seedActivities();

        $managePermission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $coordinatorRole = $this->roleWithPermissions(
            'coordinator',
            [$managePermission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($coordinatorRole);

        $assignPermission = $this->permission(
            'orders.assign',
            'Assign Orders'
        );

        $assignerRole = $this->roleWithPermissions(
            'order-admin',
            [$assignPermission]
        );

        $assigner = User::factory()->create(['is_active' => true]);
        $assigner->roles()->attach($assignerRole);

        $order = $this->createOrder();

        $this->actingAs($assigner)
            ->post(route('orders.assign-coordinator', $order), [
                'user_id' => $coordinator->id,
            ])
            ->assertRedirect();

        $this->actingAs($coordinator)
            ->post(route('orders.production-plan.store', $order), [
                'activity_ids' => ProductionActivity::whereIn('name', [
                    'Sewing',
                    'Quality Control',
                    'Delivery',
                ])->pluck('id')->all(),
            ])
            ->assertRedirect();

        $planActivity = $order->productionPlan
            ->activities()
            ->whereHas(
                'activity',
                fn ($query) => $query->where('name', 'Sewing')
            )
            ->firstOrFail();

        $order->update([
            'status' => Order::STATUS_READY,
        ]);

        $this->actingAs($coordinator)
            ->post(
                route(
                    'orders.production-plan.activities.start',
                    [$order, $planActivity]
                )
            )
            ->assertForbidden();
    }

    public function test_admin_can_unmark_completed_production_activity(): void
    {
        $this->seedActivities();

        $permission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $role = $this->roleWithPermissions(
            'admin',
            [$permission]
        );

        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($role);

        $order = $this->createOrder(Order::STATUS_IN_PRODUCTION);

        $plan = ProductionPlan::create([
            'order_id' => $order->id,
            'coordinator_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $activity = $plan->activities()->create([
            'production_activity_id' => ProductionActivity::query()
                ->where('is_required', true)
                ->firstOrFail()
                ->id,
            'sort_order' => 1,
            'status' => \App\Models\ProductionPlanActivity::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'notes' => 'Completed successfully.',
        ]);

        $this->actingAs($admin)
            ->postJson(
                route(
                    'orders.production-plan.activities.unmark',
                    [$order, $activity]
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'activity.status',
                \App\Models\ProductionPlanActivity::STATUS_STARTED
            )
            ->assertJsonPath('activity.completed_at', null);

        $activity->refresh();

        $this->assertSame(
            \App\Models\ProductionPlanActivity::STATUS_STARTED,
            $activity->status
        );

        $this->assertNull($activity->completed_at);
        $this->assertNotNull($activity->started_at);
    }

    public function test_super_admin_can_unmark_completed_production_activity(): void
    {
        $this->seedActivities();

        $permission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $role = $this->roleWithPermissions(
            'super-admin',
            [$permission]
        );

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->roles()->attach($role);

        $order = $this->createOrder(Order::STATUS_IN_PRODUCTION);

        $plan = ProductionPlan::create([
            'order_id' => $order->id,
            'coordinator_id' => $superAdmin->id,
            'created_by' => $superAdmin->id,
        ]);

        $activity = $plan->activities()->create([
            'production_activity_id' => ProductionActivity::query()
                ->where('is_required', true)
                ->firstOrFail()
                ->id,
            'sort_order' => 1,
            'status' => \App\Models\ProductionPlanActivity::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->postJson(
                route(
                    'orders.production-plan.activities.unmark',
                    [$order, $activity]
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'activity.status',
                \App\Models\ProductionPlanActivity::STATUS_STARTED
            );

        $this->assertDatabaseHas('production_plan_activities', [
            'id' => $activity->id,
            'status' => \App\Models\ProductionPlanActivity::STATUS_STARTED,
            'completed_at' => null,
        ]);
    }

    public function test_production_coordinator_cannot_unmark_completed_activity(): void
    {
        $this->seedActivities();

        $permission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $role = $this->roleWithPermissions(
            'coordinator',
            [$permission]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($role);

        $order = $this->createOrder(Order::STATUS_IN_PRODUCTION);

        $plan = ProductionPlan::create([
            'order_id' => $order->id,
            'coordinator_id' => $coordinator->id,
            'created_by' => $coordinator->id,
        ]);

        $activity = $plan->activities()->create([
            'production_activity_id' => ProductionActivity::query()
                ->where('is_required', true)
                ->firstOrFail()
                ->id,
            'sort_order' => 1,
            'status' => \App\Models\ProductionPlanActivity::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->actingAs($coordinator)
            ->postJson(
                route(
                    'orders.production-plan.activities.unmark',
                    [$order, $activity]
                )
            )
            ->assertForbidden();

        $this->assertDatabaseHas('production_plan_activities', [
            'id' => $activity->id,
            'status' => \App\Models\ProductionPlanActivity::STATUS_COMPLETED,
        ]);
    }

    public function test_user_without_production_manage_cannot_unmark_completed_activity(): void
    {
        $this->seedActivities();

        $adminRole = $this->roleWithPermissions(
            'admin',
            []
        );

        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($adminRole);

        $order = $this->createOrder(Order::STATUS_IN_PRODUCTION);

        $plan = ProductionPlan::create([
            'order_id' => $order->id,
            'coordinator_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $activity = $plan->activities()->create([
            'production_activity_id' => ProductionActivity::query()
                ->where('is_required', true)
                ->firstOrFail()
                ->id,
            'sort_order' => 1,
            'status' => \App\Models\ProductionPlanActivity::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->postJson(
                route(
                    'orders.production-plan.activities.unmark',
                    [$order, $activity]
                )
            )
            ->assertForbidden();
    }

    public function test_unmark_rejects_pending_activity(): void
    {
        $this->seedActivities();

        $permission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $role = $this->roleWithPermissions(
            'admin',
            [$permission]
        );

        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($role);

        $order = $this->createOrder(Order::STATUS_IN_PRODUCTION);

        $plan = ProductionPlan::create([
            'order_id' => $order->id,
            'coordinator_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $activity = $plan->activities()->create([
            'production_activity_id' => ProductionActivity::query()
                ->where('is_required', true)
                ->firstOrFail()
                ->id,
            'sort_order' => 1,
            'status' => \App\Models\ProductionPlanActivity::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->postJson(
                route(
                    'orders.production-plan.activities.unmark',
                    [$order, $activity]
                )
            )
            ->assertStatus(422);

        $this->assertDatabaseHas('production_plan_activities', [
            'id' => $activity->id,
            'status' => \App\Models\ProductionPlanActivity::STATUS_PENDING,
        ]);
    }

    public function test_unmark_rejects_started_activity(): void
    {
        $this->seedActivities();

        $permission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $role = $this->roleWithPermissions(
            'admin',
            [$permission]
        );

        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($role);

        $order = $this->createOrder(Order::STATUS_IN_PRODUCTION);

        $plan = ProductionPlan::create([
            'order_id' => $order->id,
            'coordinator_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $activity = $plan->activities()->create([
            'production_activity_id' => ProductionActivity::query()
                ->where('is_required', true)
                ->firstOrFail()
                ->id,
            'sort_order' => 1,
            'status' => \App\Models\ProductionPlanActivity::STATUS_STARTED,
            'started_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->postJson(
                route(
                    'orders.production-plan.activities.unmark',
                    [$order, $activity]
                )
            )
            ->assertStatus(422);

        $this->assertDatabaseHas('production_plan_activities', [
            'id' => $activity->id,
            'status' => \App\Models\ProductionPlanActivity::STATUS_STARTED,
        ]);
    }

    public function test_unmark_rejects_activity_from_another_order(): void
    {
        $this->seedActivities();

        $permission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $role = $this->roleWithPermissions(
            'admin',
            [$permission]
        );

        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($role);

        $firstOrder = $this->createOrder(Order::STATUS_IN_PRODUCTION);
        $secondOrder = $this->createOrder(Order::STATUS_IN_PRODUCTION);

        $plan = ProductionPlan::create([
            'order_id' => $secondOrder->id,
            'coordinator_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $activity = $plan->activities()->create([
            'production_activity_id' => ProductionActivity::query()
                ->where('is_required', true)
                ->firstOrFail()
                ->id,
            'sort_order' => 1,
            'status' => \App\Models\ProductionPlanActivity::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->postJson(
                route(
                    'orders.production-plan.activities.unmark',
                    [$firstOrder, $activity]
                )
            )
            ->assertForbidden();

        $this->assertDatabaseHas('production_plan_activities', [
            'id' => $activity->id,
            'status' => \App\Models\ProductionPlanActivity::STATUS_COMPLETED,
        ]);
    }

    public function test_unmark_preserves_existing_completion_evidence(): void
    {
        $this->seedActivities();

        $permission = $this->permission(
            'production.manage',
            'Manage Production'
        );

        $role = $this->roleWithPermissions(
            'admin',
            [$permission]
        );

        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($role);

        $order = $this->createOrder(Order::STATUS_IN_PRODUCTION);

        $plan = ProductionPlan::create([
            'order_id' => $order->id,
            'coordinator_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $activity = $plan->activities()->create([
            'production_activity_id' => ProductionActivity::query()
                ->where('is_required', true)
                ->firstOrFail()
                ->id,
            'sort_order' => 1,
            'status' => \App\Models\ProductionPlanActivity::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $evidence = \App\Models\ProductionPlanActivityEvidence::create([
            'production_plan_activity_id' => $activity->id,
            'uploaded_by' => $admin->id,
            'file_path' => 'production-evidence/test-proof.pdf',
            'original_name' => 'test-proof.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'note' => 'Completion proof.',
        ]);

        $this->actingAs($admin)
            ->postJson(
                route(
                    'orders.production-plan.activities.unmark',
                    [$order, $activity]
                )
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'production_plan_activity_evidence',
            [
                'id' => $evidence->id,
                'production_plan_activity_id' => $activity->id,
                'original_name' => 'test-proof.pdf',
            ]
        );
    }

}
