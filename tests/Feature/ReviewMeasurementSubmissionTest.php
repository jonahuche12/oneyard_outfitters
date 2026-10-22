<?php

namespace Tests\Feature;

use App\Actions\Measurements\EnsureOrderMeasurementsReadyForProduction;
use App\Actions\Measurements\ReviewMeasurementSubmission;
use App\Models\Contact;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReviewMeasurementSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;
    private OrderItem $item;
    private MeasurementSubmission $sub;

    private function userWith(string $roleSlug, array $perms = ['measurements.review']): User
    {
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => $roleSlug, 'description' => $roleSlug . '.']);
        foreach ($perms as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => $slug . '.']);
            $role->permissions()->syncWithoutDetaching([$p->id]);
        }
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::factory()->create();
        $contact = Contact::factory()->create(['organization_id' => $org->id, 'is_active' => true]);
        $q = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(), 'organization_id' => $org->id, 'contact_id' => $contact->id,
            'created_by' => User::factory()->create()->id, 'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(), 'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_ACCEPTED, 'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);
        $this->order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'organization_id' => $org->id, 'quotation_id' => $q->id,
            'contact_id' => $contact->id, 'order_date' => now()->toDateString(), 'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(), 'status' => Order::STATUS_APPROVED,
            'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);
        $this->item = OrderItem::create([
            'order_id' => $this->order->id, 'unit_price' => 100, 'line_total' => 100,
            'measurement_eligibility' => 'required', 'item_name' => 'Cap', 'quantity' => 4,
            'unit' => 'piece', 'product_type' => 'other', 'sort_order' => 0,
        ]);
        $this->sub = MeasurementSubmission::query()->forceCreate([
            'order_item_id' => $this->item->id, 'revision' => 1, 'measurement_method' => 'size_quantity',
            'input_source' => 'manual', 'status' => 'submitted', 'submitted_at' => now(),
        ]);
    }

    private function review(): ReviewMeasurementSubmission
    {
        return app(ReviewMeasurementSubmission::class);
    }

    private function assignCoordinator(User $user): void
    {
        OrderAssignment::create([
            'order_id' => $this->order->id, 'user_id' => $user->id,
            'assigned_by' => User::factory()->create()->id, 'assigned_at' => now()->subDay(),
        ]);
    }

    public function test_admin_accepts_and_gate_then_passes(): void
    {
        $admin = $this->userWith('admin');

        $r = $this->review()->accept($admin, $this->sub);

        $this->assertSame('accepted', $r->status);
        $this->assertSame($admin->id, $r->reviewed_by);
        $this->assertNotNull($r->reviewed_at);
        app(EnsureOrderMeasurementsReadyForProduction::class)->handle($this->order->fresh());
    }

    public function test_current_coordinator_can_accept(): void
    {
        $coordinator = $this->userWith('production');
        $this->assignCoordinator($coordinator);

        $this->assertSame('accepted', $this->review()->accept($coordinator, $this->sub)->status);
    }

    public function test_correction_requires_reason_and_records_it(): void
    {
        $admin = $this->userWith('admin');

        try {
            $this->review()->requestCorrection($admin, $this->sub, 'short');
            $this->fail('Expected a validation failure.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reason', $e->errors());
        }
        $this->assertSame('submitted', $this->sub->fresh()->status);

        $r = $this->review()->requestCorrection($admin, $this->sub, 'Sizes do not add up to the order.');
        $this->assertSame('correction_requested', $r->status);
        $this->assertSame('Sizes do not add up to the order.', $r->correction_reason);
    }

    public function test_user_without_permission_is_denied(): void
    {
        $admin = $this->userWith('admin', ['orders.view']);

        $this->expectException(AuthorizationException::class);
        $this->review()->accept($admin, $this->sub);
    }

    public function test_non_coordinator_staff_with_permission_is_denied(): void
    {
        $other = $this->userWith('production');

        try {
            $this->review()->accept($other, $this->sub);
            $this->fail('Expected denial.');
        } catch (AuthorizationException) {
            $this->assertSame('submitted', $this->sub->fresh()->status);
        }
    }

    public function test_reassigned_coordinator_is_denied(): void
    {
        $former = $this->userWith('production');
        OrderAssignment::create([
            'order_id' => $this->order->id, 'user_id' => $former->id,
            'assigned_by' => User::factory()->create()->id, 'assigned_at' => now()->subDay(), 'ended_at' => now(),
        ]);

        $this->expectException(AuthorizationException::class);
        $this->review()->accept($former, $this->sub);
    }

    public function test_inactive_admin_is_denied(): void
    {
        $admin = $this->userWith('admin');
        $admin->forceFill(['is_active' => false])->save();

        $this->expectException(AuthorizationException::class);
        $this->review()->accept($admin, $this->sub);
    }

    public function test_only_submitted_revisions_can_be_reviewed(): void
    {
        $admin = $this->userWith('admin');
        $this->sub->forceFill(['status' => 'draft'])->save();

        $this->expectException(ValidationException::class);
        $this->review()->accept($admin, $this->sub);
    }

    public function test_a_reviewed_revision_cannot_be_reviewed_again(): void
    {
        $admin = $this->userWith('admin');
        $this->review()->accept($admin, $this->sub);

        $this->expectException(ValidationException::class);
        $this->review()->requestCorrection($admin, $this->sub, 'Changed my mind about this.');
    }

    public function test_older_revision_cannot_be_reviewed(): void
    {
        $admin = $this->userWith('admin');
        MeasurementSubmission::query()->forceCreate([
            'order_item_id' => $this->item->id, 'revision' => 2, 'measurement_method' => 'size_quantity',
            'input_source' => 'manual', 'status' => 'draft',
        ]);

        $this->expectException(ValidationException::class);
        $this->review()->accept($admin, $this->sub);
    }

    public function test_review_is_locked_once_the_order_is_past_the_window(): void
    {
        $admin = $this->userWith('admin');
        $this->order->forceFill(['status' => Order::STATUS_READY])->save();

        $this->expectException(ValidationException::class);
        $this->review()->accept($admin, $this->sub);
    }
}
