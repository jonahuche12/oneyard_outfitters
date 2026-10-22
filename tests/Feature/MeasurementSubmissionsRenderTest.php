<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\IndividualMeasurement;
use App\Models\MeasurementSizeQuantity;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementSubmissionsRenderTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;
    private OrderItem $shirt;
    private OrderItem $cap;
    private OrderItem $skipped;

    private function userWith(string $roleSlug, array $perms = ['orders.view']): User
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

        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(), 'organization_id' => $organization->id,
            'contact_id' => $contact->id, 'created_by' => User::factory()->create()->id,
            'quotation_date' => now()->toDateString(), 'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30, 'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);
        $this->order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'organization_id' => $organization->id,
            'quotation_id' => $quotation->id, 'contact_id' => $contact->id,
            'order_date' => now()->toDateString(), 'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(), 'status' => Order::STATUS_APPROVED,
            'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);

        $base = ['order_id' => $this->order->id, 'unit_price' => 100, 'line_total' => 100, 'measurement_eligibility' => 'required'];
        $this->shirt = OrderItem::create($base + ['item_name' => 'Shirt', 'quantity' => 5, 'unit' => 'pair', 'product_type' => 'shirt', 'sort_order' => 0]);
        $this->cap = OrderItem::create($base + ['item_name' => 'Cap', 'quantity' => 4, 'unit' => 'piece', 'product_type' => 'other', 'sort_order' => 1]);
        $this->skipped = OrderItem::create($base + ['item_name' => 'Belt', 'quantity' => 2, 'unit' => 'piece', 'product_type' => 'other', 'sort_order' => 2]);

        // Shirt: a DRAFT with one person. Cap: SUBMITTED sizes. Belt: nothing.
        $draft = MeasurementSubmission::query()->forceCreate([
            'order_item_id' => $this->shirt->id, 'revision' => 1, 'measurement_method' => 'individual',
            'input_source' => 'manual', 'status' => 'draft',
        ]);
        IndividualMeasurement::query()->forceCreate([
            'measurement_submission_id' => $draft->id, 'person_label' => 'Zainab Okoro',
            'measurements' => ['neck' => '15', 'chest' => '38', 'waist' => '32'], 'notes' => 'Left handed', 'position' => 1,
        ]);

        $sizes = MeasurementSubmission::query()->forceCreate([
            'order_item_id' => $this->cap->id, 'revision' => 1, 'measurement_method' => 'size_quantity',
            'input_source' => 'manual', 'status' => 'submitted', 'submitted_at' => now(),
        ]);
        foreach (['S' => 1, 'M' => 3] as $label => $qty) {
            MeasurementSizeQuantity::query()->forceCreate([
                'measurement_submission_id' => $sizes->id, 'size_label' => $label, 'quantity' => $qty,
            ]);
        }
    }

    private function assign(User $coordinator, bool $ended = false): void
    {
        OrderAssignment::create([
            'order_id' => $this->order->id,
            'user_id' => $coordinator->id,
            'assigned_by' => User::factory()->create()->id,
            'assigned_at' => now()->subDay(),
            'ended_at' => $ended ? now() : null,
        ]);
    }

    public function test_admin_sees_draft_and_submitted_measurements(): void
    {
        $admin = $this->userWith('admin');

        $this->actingAs($admin)->get(route('orders.show', $this->order))
            ->assertOk()
            ->assertSee('Measurements Received')
            ->assertSee('Draft, not yet submitted')
            ->assertSee('Zainab Okoro')
            ->assertSee('Left handed')
            ->assertSee('Submitted, awaiting review')
            ->assertSee('Commercial sizes')
            ->assertSee('Not started');
    }

    public function test_super_admin_sees_measurements(): void
    {
        $super = $this->userWith('super-admin');

        $this->actingAs($super)->get(route('orders.show', $this->order))
            ->assertOk()->assertSee('Zainab Okoro');
    }

    public function test_current_coordinator_sees_measurements(): void
    {
        $coordinator = $this->userWith('production');
        $this->assign($coordinator);

        $this->actingAs($coordinator)->get(route('orders.show', $this->order))
            ->assertOk()->assertSee('Measurements Received')->assertSee('Zainab Okoro');
    }

    public function test_other_staff_cannot_open_the_order_so_see_no_measurements(): void
    {
        $other = $this->userWith('sales');

        $this->actingAs($other)->get(route('orders.show', $this->order))
            ->assertForbidden();
    }

    public function test_reassigned_coordinator_is_locked_out(): void
    {
        $former = $this->userWith('production');
        $this->assign($former, ended: true);

        $this->actingAs($former)->get(route('orders.show', $this->order))
            ->assertForbidden();
    }

    public function test_size_grid_shows_total(): void
    {
        $admin = $this->userWith('admin');
        $html = $this->actingAs($admin)->get(route('orders.show', $this->order))->getContent();

        $this->assertMatchesRegularExpression('/Total<\/td>\s*<td class="py-1">4</', $html);
    }

    private function section(string $html): string
    {
        $start = strpos($html, 'id="measurement-submissions"');
        $this->assertNotFalse($start);

        return substr($html, $start, strpos($html, '</section>', $start) - $start);
    }

    public function test_measurements_are_paginated_five_per_page(): void
    {
        $base = ['order_id' => $this->order->id, 'unit_price' => 100, 'line_total' => 100,
            'measurement_eligibility' => 'required', 'quantity' => 1, 'unit' => 'piece', 'product_type' => 'other'];
        foreach (range(1, 4) as $n) {
            OrderItem::create($base + ['item_name' => "Extra $n", 'sort_order' => 10 + $n]);
        }
        $admin = $this->userWith('admin');
        $names = ['Shirt', 'Cap', 'Belt', 'Extra 1', 'Extra 2', 'Extra 3', 'Extra 4'];

        $p1 = $this->section($this->actingAs($admin)->get(route('orders.show', $this->order))->getContent());
        $p2 = $this->section($this->actingAs($admin)->get(route('orders.show', $this->order) . '?measurements_page=2')->getContent());

        $this->assertStringContainsString('data-measurement-pager', $p1);
        foreach ($names as $n) {
            $this->assertNotSame(str_contains($p1, '>' . $n), str_contains($p2, '>' . $n), "$n must be on exactly one page");
        }
        $this->assertSame(5, substr_count($p1, 'rounded-lg border border-slate-200 p-4'));
        $this->assertSame(2, substr_count($p2, 'rounded-lg border border-slate-200 p-4'));
    }
}
