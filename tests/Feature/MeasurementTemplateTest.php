<?php

namespace Tests\Feature;

use App\Actions\Measurements\IssueMeasurementLink;
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

class MeasurementTemplateTest extends TestCase
{
    use RefreshDatabase;

    private OrderItem $shirt;
    private OrderItem $cap;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'admin', 'description' => 'Admin.']);
        $perm = Permission::firstOrCreate(['slug' => 'measurements.links.manage'], ['name' => 'm', 'description' => 'm.']);
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($role);
        $org = Organization::factory()->create();
        $contact = Contact::factory()->create(['organization_id' => $org->id, 'is_active' => true, 'email' => 's@example.com']);
        $q = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(), 'organization_id' => $org->id, 'contact_id' => $contact->id,
            'created_by' => $admin->id, 'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(), 'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_ACCEPTED, 'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'organization_id' => $org->id, 'quotation_id' => $q->id,
            'contact_id' => $contact->id, 'order_date' => now()->toDateString(), 'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(), 'status' => Order::STATUS_APPROVED,
            'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);
        $base = ['order_id' => $order->id, 'unit_price' => 100, 'line_total' => 100, 'measurement_eligibility' => 'required'];
        $this->shirt = OrderItem::create($base + ['item_name' => 'Shirt', 'quantity' => 5, 'unit' => 'pair', 'product_type' => 'shirt', 'sort_order' => 0]);
        $this->cap = OrderItem::create($base + ['item_name' => 'Cap', 'quantity' => 4, 'unit' => 'piece', 'product_type' => 'other', 'sort_order' => 1]);
        $this->token = app(IssueMeasurementLink::class)->execute($admin, $order, $contact)['token'];
    }

    private function url(OrderItem $item, string $query = '', ?string $token = null): string
    {
        return route('measurements.public.template', ['token' => $token ?? $this->token, 'item' => $item->id]) . $query;
    }

    public function test_individual_template_has_profile_header(): void
    {
        $body = $this->get($this->url($this->shirt))->assertOk()->streamedContent();

        $this->assertStringStartsWith('Name,Neck,Chest,Waist,"Shoulder width","Sleeve length","Shirt length"', $body);
    }

    public function test_sizes_template_lists_sizes(): void
    {
        $body = $this->get($this->url($this->cap, '?kind=sizes'))->assertOk()->streamedContent();

        $this->assertStringContainsString("Size,Quantity", $body);
        $this->assertStringContainsString("XL,", $body);
    }

    public function test_item_without_profile_has_no_individual_template(): void
    {
        $this->get($this->url($this->cap))->assertNotFound();
    }

    public function test_bad_token_is_not_found(): void
    {
        $this->get($this->url($this->shirt, '', 'bogus'))->assertNotFound();
    }
}
