<?php

namespace Tests\Feature;

use App\Mail\MeasurementLinkInvitation;
use App\Models\Contact;
use App\Models\MeasurementLink;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MeasurementLinkHttpTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'admin', 'description' => 'Admin.']);
        $perm = Permission::firstOrCreate(
            ['slug' => 'measurements.links.manage'],
            ['name' => 'Manage Measurement Links', 'description' => 'Test.']
        );
        $viewPerm = Permission::firstOrCreate(
            ['slug' => 'orders.view'],
            ['name' => 'orders.view', 'description' => 'View orders.']
        );
        $role->permissions()->syncWithoutDetaching([$perm->id, $viewPerm->id]);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function order(): array
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create([
            'organization_id' => $organization->id, 'is_active' => true, 'email' => 'c' . uniqid() . '@example.com',
        ]);
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(), 'organization_id' => $organization->id,
            'contact_id' => $contact->id, 'created_by' => User::factory()->create()->id,
            'quotation_date' => now()->toDateString(), 'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30, 'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 30000, 'discount' => 0, 'additional_charges' => 0, 'total' => 30000,
        ]);
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'organization_id' => $organization->id,
            'quotation_id' => $quotation->id, 'contact_id' => $contact->id,
            'order_date' => now()->toDateString(), 'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(), 'status' => Order::STATUS_APPROVED,
            'subtotal' => 30000, 'discount' => 0, 'additional_charges' => 0, 'total' => 30000,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'item_name' => 'Link uniform', 'quantity' => 10, 'unit' => 'piece',
            'unit_price' => 3000, 'line_total' => 30000, 'sort_order' => 0, 'measurement_eligibility' => 'required',
        ]);

        return [$order, $contact];
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        Mail::fake();
        [$order, $contact] = $this->order();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('orders.measurement-links.store', $order), ['contact_ids' => [$contact->id]])
            ->assertForbidden();

        $this->assertSame(0, MeasurementLink::count());
        Mail::assertNothingSent();
    }

    public function test_admin_can_revoke_and_reason_is_required(): void
    {
        Mail::fake();
        [$order, $contact] = $this->order();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('orders.measurement-links.store', $order), ['contact_ids' => [$contact->id]]);
        $link = MeasurementLink::firstOrFail();

        $this->actingAs($admin)
            ->post(route('orders.measurement-links.revoke', [$order, $link]), ['reason' => 'short'])
            ->assertSessionHasErrors('reason');
        $this->assertNull($link->fresh()->revoked_at);

        $this->actingAs($admin)
            ->post(route('orders.measurement-links.revoke', [$order, $link]), ['reason' => 'Sent to the wrong person.'])
            ->assertSessionHas('status');
        $this->assertNotNull($link->fresh()->revoked_at);
    }

    public function test_link_from_another_order_returns_404(): void
    {
        Mail::fake();
        [$orderA, $contactA] = $this->order();
        [$orderB] = $this->order();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('orders.measurement-links.store', $orderA), ['contact_ids' => [$contactA->id]]);
        $link = MeasurementLink::firstOrFail();

        $this->actingAs($admin)
            ->post(route('orders.measurement-links.revoke', [$orderB, $link]), ['reason' => 'Mismatched order test.'])
            ->assertNotFound();
    }

    public function test_coordinator_sends_to_several_contacts_and_failures_are_reported_by_name(): void
    {
        Mail::fake();
        [$order, $first] = $this->order();
        $second = Contact::factory()->create([
            'organization_id' => $order->organization_id, 'is_active' => true, 'email' => 'second@example.com',
        ]);
        $inactive = Contact::factory()->create([
            'organization_id' => $order->organization_id, 'is_active' => false, 'email' => 'off@example.com',
        ]);

        $this->actingAs($this->admin())
            ->post(route('orders.measurement-links.store', $order), [
                'contact_ids' => [$first->id, $second->id, $inactive->id],
            ])
            ->assertSessionHas('status')
            ->assertSessionHasErrors('measurements');

        $this->assertSame(2, MeasurementLink::whereNotNull('sent_at')->count());
        $this->assertSame(2, MeasurementLink::count());
        Mail::assertSent(MeasurementLinkInvitation::class, 2);
    }

    public function test_at_least_one_contact_is_required(): void
    {
        [$order] = $this->order();

        $this->actingAs($this->admin())
            ->post(route('orders.measurement-links.store', $order), ['contact_ids' => []])
            ->assertSessionHasErrors('contact_ids');
    }

    public function test_order_page_shows_the_send_panel_to_a_manager_with_required_items(): void
    {
        [$order] = $this->order();

        $this->actingAs($this->admin())
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Customer Measurements')
            ->assertSee('Send Measurement Link');
    }

    public function test_order_page_hides_the_panel_without_permission(): void
    {
        [$order] = $this->order();
        $role = Role::create(['name' => 'Viewer', 'slug' => 'viewer-x', 'description' => 'Viewer.']);
        $perm = Permission::firstOrCreate(['slug' => 'orders.view'], ['name' => 'orders.view', 'description' => 'View.']);
        $role->permissions()->attach($perm);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);
        \App\Models\OrderAssignment::create([
            'order_id' => $order->id, 'user_id' => $user->id, 'assigned_by' => $user->id,
            'assigned_at' => now(), 'reason' => 'Test assignment.',
        ]);

        $this->actingAs($user)->get(route('orders.show', $order))
            ->assertOk()->assertDontSee('Customer Measurements');
    }
}
