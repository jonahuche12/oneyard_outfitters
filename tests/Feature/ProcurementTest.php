<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\Procurement;
use App\Models\ProcurementAttachment;
use App\Models\ProcurementOffer;
use App\Models\ProductSpecificationArtifact;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProcurementTest extends TestCase
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

    private function createProcurementOrder(): Order
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

    public function test_procurement_can_be_linked_to_an_order(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $quotation = Quotation::create([
            'quotation_number' => 'TMP-' . uniqid('', true),
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'quotation_date' => '2026-09-24',
            'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_DRAFT,
            'subtotal' => 0,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 0,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . uniqid('', true),
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => null,
            'order_date' => '2026-09-24',
            'status' => Order::STATUS_PENDING,
            'subtotal' => 0,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 0,
        ]);

        $procurement = Procurement::create([
            'order_id' => $order->id,
            'created_by' => $user->id,
            'item_name' => 'Shirt Fabric',
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 2500,
        ]);

        $this->assertTrue($procurement->order->is($order));
        $this->assertTrue(
            $order->procurements->contains($procurement)
        );
    }

    public function test_independent_procurement_can_exist_without_an_order(): void
    {
        $user = User::factory()->create();

        $procurement = Procurement::create([
            'order_id' => null,
            'created_by' => $user->id,
            'item_name' => 'General Packaging Materials',
            'quantity' => 50,
            'unit' => 'Piece',
            'maximum_unit_price' => 500,
        ]);

        $this->assertNull($procurement->order_id);
        $this->assertNull($procurement->order);
    }

    public function test_procurement_tracks_creator_and_updater(): void
    {
        $creator = User::factory()->create();
        $updater = User::factory()->create();

        $procurement = Procurement::create([
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
            'item_name' => 'Cotton Fabric',
            'quantity' => 20,
            'unit' => 'Yard',
            'maximum_unit_price' => 3000,
        ]);

        $this->assertTrue($procurement->createdBy->is($creator));
        $this->assertTrue($procurement->updatedBy->is($updater));
    }

    public function test_procurement_can_have_attachments_and_offers(): void
    {
        $user = User::factory()->create();

        $procurement = Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'School Bags',
            'quantity' => 100,
            'unit' => 'Piece',
            'maximum_unit_price' => 15000,
        ]);

        $attachment = ProcurementAttachment::create([
            'procurement_id' => $procurement->id,
            'uploaded_by' => $user->id,
            'original_name' => 'bag-reference.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'note' => 'Reference image.',
        ]);

        $offer = ProcurementOffer::create([
            'procurement_id' => $procurement->id,
            'user_id' => $user->id,
            'quantity' => 100,
            'unit_price' => 12000,
            'total_price' => 1200000,
            'submitted_at' => now(),
        ]);

        $this->assertTrue(
            $procurement->attachments->contains($attachment)
        );

        $this->assertTrue(
            $procurement->offers->contains($offer)
        );

        $this->assertTrue($attachment->procurement->is($procurement));
        $this->assertTrue($offer->procurement->is($procurement));
    }

    public function test_procurement_attachment_can_reference_product_specification_artifact(): void
    {
        $user = User::factory()->create();

        $artifact = ProductSpecificationArtifact::factory()->create([
            'uploaded_by' => $user->id,
        ]);

        $procurement = Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'Fabric',
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 2500,
        ]);

        $attachment = ProcurementAttachment::create([
            'procurement_id' => $procurement->id,
            'product_specification_artifact_id' => $artifact->id,
            'uploaded_by' => $user->id,
            'note' => 'Existing specification artifact.',
        ]);

        $this->assertTrue(
            $attachment->productSpecificationArtifact->is($artifact)
        );

        $this->assertTrue(
            $artifact->procurementAttachments->contains($attachment)
        );
    }

    public function test_procurement_attachment_can_be_a_new_uploaded_file_reference(): void
    {
        $user = User::factory()->create();

        $procurement = Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'Packaging Material',
            'quantity' => 20,
            'unit' => 'Pack',
            'maximum_unit_price' => 5000,
        ]);

        $attachment = ProcurementAttachment::create([
            'procurement_id' => $procurement->id,
            'product_specification_artifact_id' => null,
            'uploaded_by' => $user->id,
            'file_path' => 'procurement/1/reference.pdf',
            'original_name' => 'reference.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'note' => 'New procurement-specific reference.',
        ]);

        $this->assertNull(
            $attachment->product_specification_artifact_id
        );

        $this->assertSame(
            'procurement/1/reference.pdf',
            $attachment->file_path
        );
    }

    public function test_procurement_offer_belongs_to_submitting_user(): void
    {
        $user = User::factory()->create();

        $procurement = Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'Trouser Fabric',
            'quantity' => 50,
            'unit' => 'Yard',
            'maximum_unit_price' => 4000,
        ]);

        $offer = ProcurementOffer::create([
            'procurement_id' => $procurement->id,
            'user_id' => $user->id,
            'quantity' => 50,
            'unit_price' => 3500,
            'total_price' => 175000,
            'submitted_at' => now(),
        ]);

        $this->assertTrue($offer->user->is($user));
        $this->assertTrue(
            $user->procurementOffers->contains($offer)
        );
    }

    public function test_procurement_casts_decimal_and_date_fields(): void
    {
        $user = User::factory()->create();

        $procurement = Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'Fabric',
            'quantity' => 12.50,
            'unit' => 'Yard',
            'maximum_unit_price' => 2500.75,
            'required_by' => '2026-10-15',
            'offer_deadline' => '2026-10-10 17:00:00',
        ]);

        $procurement->refresh();

        $this->assertSame('12.50', $procurement->quantity);
        $this->assertSame('2500.75', $procurement->maximum_unit_price);
        $this->assertSame('2026-10-15', $procurement->required_by->format('Y-m-d'));
        $this->assertSame(
            '2026-10-10 17:00:00',
            $procurement->offer_deadline->format('Y-m-d H:i:s')
        );
    }

    public function test_procurement_can_be_soft_deleted(): void
    {
        $user = User::factory()->create();

        $procurement = Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'Deleted Procurement',
            'quantity' => 10,
            'unit' => 'Piece',
            'maximum_unit_price' => 1000,
        ]);

        $procurement->delete();

        $this->assertSoftDeleted('procurements', [
            'id' => $procurement->id,
        ]);

        $this->assertNull(
            Procurement::find($procurement->id)
        );

        $this->assertNotNull(
            Procurement::withTrashed()->find($procurement->id)
        );
    }

    public function test_staff_with_procurement_view_permission_can_view_procurement(): void
    {
        $permission = $this->permission('procurement.view', 'View Procurement');
        $role = $this->roleWithPermissions('procurement-viewer', [$permission]);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        $procurement = Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'Fabric',
            'quantity' => 10,
            'unit' => 'Yard',
            'maximum_unit_price' => 2500,
        ]);

        $this->assertTrue(
            Gate::forUser($user)->allows('view', $procurement)
        );
    }

    public function test_staff_without_procurement_view_permission_cannot_view_procurement(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $procurement = Procurement::create([
            'created_by' => $user->id,
            'item_name' => 'Fabric',
            'quantity' => 10,
            'unit' => 'Yard',
            'maximum_unit_price' => 2500,
        ]);

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $procurement)
        );
    }

    public function test_admin_with_procurement_manage_can_create_independent_procurement(): void
    {
        $permission = $this->permission('procurement.manage', 'Manage Procurement');
        $role = $this->roleWithPermissions('admin', [$permission]);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        $this->assertTrue(
            Gate::forUser($user)->allows('create', Procurement::class)
        );
    }

    public function test_coordinator_cannot_create_independent_procurement(): void
    {
        $permission = $this->permission('procurement.manage', 'Manage Procurement');
        $role = $this->roleWithPermissions('coordinator', [$permission]);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        $this->assertFalse(
            Gate::forUser($user)->allows('create', Procurement::class)
        );
    }

    public function test_coordinator_can_create_procurement_for_assigned_order(): void
    {
        $permission = $this->permission('procurement.manage', 'Manage Procurement');
        $role = $this->roleWithPermissions('coordinator', [$permission]);

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($role);

        $order = $this->createProcurementOrder();

        OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $coordinator->id,
            'assigned_by' => $coordinator->id,
            'assigned_at' => now(),
        ]);

        $order->load('currentAssignment');

        $this->assertTrue(
            Gate::forUser($coordinator)->allows(
                'createForOrder',
                [Procurement::class, $order]
            )
        );
    }

    public function test_coordinator_cannot_create_procurement_for_unassigned_order(): void
    {
        $permission = $this->permission('procurement.manage', 'Manage Procurement');
        $role = $this->roleWithPermissions('coordinator', [$permission]);

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($role);

        $order = $this->createProcurementOrder();

        $this->assertFalse(
            Gate::forUser($coordinator)->allows(
                'createForOrder',
                [Procurement::class, $order]
            )
        );
    }

    public function test_admin_can_update_any_procurement(): void
    {
        $permission = $this->permission('procurement.manage', 'Manage Procurement');
        $role = $this->roleWithPermissions('admin', [$permission]);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($role);

        $procurement = Procurement::create([
            'created_by' => User::factory()->create()->id,
            'item_name' => 'Independent Fabric',
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 3000,
        ]);

        $this->assertTrue(
            Gate::forUser($admin)->allows('update', $procurement)
        );
    }

    public function test_coordinator_can_update_procurement_for_assigned_order(): void
    {
        $permission = $this->permission('procurement.manage', 'Manage Procurement');
        $role = $this->roleWithPermissions('coordinator', [$permission]);

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($role);

        $order = $this->createProcurementOrder();

        OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $coordinator->id,
            'assigned_by' => $coordinator->id,
            'assigned_at' => now(),
        ]);

        $procurement = Procurement::create([
            'order_id' => $order->id,
            'created_by' => $coordinator->id,
            'item_name' => 'Order Fabric',
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 3000,
        ]);

        $procurement->load('order.currentAssignment');

        $this->assertTrue(
            Gate::forUser($coordinator)->allows('update', $procurement)
        );
    }

    public function test_coordinator_cannot_update_procurement_for_another_order(): void
    {
        $permission = $this->permission('procurement.manage', 'Manage Procurement');
        $role = $this->roleWithPermissions('coordinator', [$permission]);

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($role);

        $assignedOrder = $this->createProcurementOrder();
        $otherOrder = $this->createProcurementOrder();

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
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 3000,
        ]);

        $procurement->load('order.currentAssignment');

        $this->assertFalse(
            Gate::forUser($coordinator)->allows('update', $procurement)
        );
    }

    public function test_coordinator_cannot_update_independent_procurement(): void
    {
        $permission = $this->permission('procurement.manage', 'Manage Procurement');
        $role = $this->roleWithPermissions('coordinator', [$permission]);

        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->roles()->attach($role);

        $procurement = Procurement::create([
            'order_id' => null,
            'created_by' => $coordinator->id,
            'item_name' => 'Independent Fabric',
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 3000,
        ]);

        $this->assertFalse(
            Gate::forUser($coordinator)->allows('update', $procurement)
        );
    }
}
