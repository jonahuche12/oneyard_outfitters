<?php

namespace Tests\Feature;

use App\Actions\Measurements\IssueMeasurementLink;
use App\Models\Contact;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMeasurementSubmitTest extends TestCase
{
    use RefreshDatabase;

    private string $token;
    private OrderItem $shirt;
    private OrderItem $cap;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'admin', 'description' => 'Admin.']);
        $perm = Permission::firstOrCreate(['slug' => 'measurements.links.manage'], ['name' => 'm', 'description' => 'm.']);
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($role);

        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create(['organization_id' => $organization->id, 'is_active' => true, 'email' => 's@example.com']);
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(), 'organization_id' => $organization->id,
            'contact_id' => $contact->id, 'created_by' => $admin->id,
            'quotation_date' => now()->toDateString(), 'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30, 'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'organization_id' => $organization->id,
            'quotation_id' => $quotation->id, 'contact_id' => $contact->id,
            'order_date' => now()->toDateString(), 'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(), 'status' => Order::STATUS_APPROVED,
            'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);
        $base = ['order_id' => $order->id, 'unit_price' => 100, 'line_total' => 100, 'measurement_eligibility' => 'required'];
        $this->shirt = OrderItem::create($base + ['item_name' => 'Shirt', 'quantity' => 2, 'unit' => 'piece', 'product_type' => 'shirt', 'sort_order' => 0]);
        $this->cap = OrderItem::create($base + ['item_name' => 'Cap', 'quantity' => 4, 'unit' => 'piece', 'product_type' => 'other', 'sort_order' => 1]);

        $this->token = app(IssueMeasurementLink::class)->execute($admin, $order, $contact)['token'];
    }

    private function person(string $waist): array
    {
        return ['label' => 'P', 'measurements' => ['neck' => '15', 'chest' => '38', 'waist' => $waist, 'shoulder' => '17', 'sleeve' => '24', 'length' => '29']];
    }

    private function payload(): array
    {
        return ['items' => [
            $this->shirt->id => ['method' => 'individual', 'people' => [$this->person('30'), $this->person('32')]],
            $this->cap->id => ['method' => 'size_quantity', 'sizes' => ['S' => '1', 'M' => '3']],
        ]];
    }

    public function test_form_renders_both_methods_and_a_row_per_piece(): void
    {
        $this->get(route('measurements.public.show', $this->token))
            ->assertOk()
            ->assertSee('Choose our commercial sizes')
            ->assertSee('items[' . $this->shirt->id . '][people][1][label]', false)
            ->assertSee('Submit Measurements');
    }

    public function test_contact_submits_manual_and_size_measurements(): void
    {
        $this->post(route('measurements.public.store', $this->token), $this->payload())
            ->assertRedirect(route('measurements.public.show', $this->token))
            ->assertSessionHas('status');

        $this->assertSame(2, MeasurementSubmission::where('status', 'submitted')->count());

        $this->get(route('measurements.public.show', $this->token))
            ->assertSee('Received and under review')
            ->assertDontSee('Submit Measurements');
    }

    public function test_invalid_input_returns_with_errors_and_saves_nothing(): void
    {
        $payload = $this->payload();
        $payload['items'][$this->cap->id]['sizes'] = ['S' => '1'];

        $this->from(route('measurements.public.show', $this->token))
            ->post(route('measurements.public.store', $this->token), $payload)
            ->assertSessionHasErrors('items.' . $this->cap->id . '.sizes');

        $this->assertSame(0, MeasurementSubmission::count());
    }

    public function test_invalid_token_cannot_submit(): void
    {
        $this->post(route('measurements.public.store', str_repeat('z', 48)), $this->payload())
            ->assertSessionHasErrors('link');

        $this->assertSame(0, MeasurementSubmission::count());
    }
}
