<?php

namespace Tests\Feature;

use App\Actions\Measurements\IssueMeasurementLink;
use App\Actions\Measurements\RevokeMeasurementLink;
use App\Actions\Measurements\SubmitMeasurements;
use App\Models\Contact;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\ProductionPlan;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubmitMeasurementsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Order $order;
    private OrderItem $shirt;
    private OrderItem $cap;
    private string $token;
    private $link;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'admin', 'description' => 'Admin.']);
        $perm = Permission::firstOrCreate(['slug' => 'measurements.links.manage'], ['name' => 'm', 'description' => 'm.']);
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($role);

        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create(['organization_id' => $organization->id, 'is_active' => true, 'email' => 's@example.com']);
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(), 'organization_id' => $organization->id,
            'contact_id' => $contact->id, 'created_by' => $this->admin->id,
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

        $issued = app(IssueMeasurementLink::class)->execute($this->admin, $this->order, $contact);
        $this->link = $issued['link'];
        $this->token = $issued['token'];
    }

    private function shirtPayload(string $waist = '32'): array
    {
        return ['method' => 'individual', 'people' => [['label' => 'Ada', 'measurements' => [
            'neck' => '15', 'chest' => '38', 'waist' => $waist, 'shoulder' => '17', 'sleeve' => '24', 'length' => '29',
        ]]]];
    }

    private function capPayload(): array
    {
        return ['method' => 'size_quantity', 'sizes' => ['S' => '1', 'M' => '3']];
    }

    private function all(): array
    {
        return [$this->shirt->id => $this->shirtPayload(), $this->cap->id => $this->capPayload()];
    }

    private function submit(array $payloads)
    {
        return app(SubmitMeasurements::class)->execute($this->token, $payloads);
    }

    public function test_submits_every_required_item_as_revision_one(): void
    {
        $created = $this->submit($this->all());

        $this->assertCount(2, $created);
        $s = MeasurementSubmission::where('order_item_id', $this->shirt->id)->firstOrFail();
        $this->assertSame(1, $s->revision);
        $this->assertSame('submitted', $s->status);
        $this->assertSame('manual', $s->input_source);
        $this->assertSame($this->link->id, $s->measurement_link_id);
        $this->assertNotNull($s->submitted_at);
        $this->assertSame('32.00', $s->individualMeasurements->first()->measurements['waist']);

        $c = MeasurementSubmission::where('order_item_id', $this->cap->id)->firstOrFail();
        $this->assertSame(['M' => 3, 'S' => 1], $c->sizeQuantities->pluck('quantity', 'size_label')->all());
    }

    public function test_one_invalid_item_writes_nothing(): void
    {
        $payloads = $this->all();
        $payloads[$this->cap->id]['sizes'] = ['S' => '1'];

        try {
            $this->submit($payloads);
            $this->fail('Expected validation failure.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items.' . $this->cap->id . '.sizes', $e->errors());
        }
        $this->assertSame(0, MeasurementSubmission::count());
    }

    public function test_every_pending_item_is_required(): void
    {
        try {
            $this->submit([$this->shirt->id => $this->shirtPayload()]);
            $this->fail('Expected validation failure.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items.' . $this->cap->id, $e->errors());
        }
        $this->assertSame(0, MeasurementSubmission::count());
    }

    public function test_unknown_or_not_required_items_are_rejected(): void
    {
        $other = OrderItem::create([
            'order_id' => $this->order->id, 'item_name' => 'Badge', 'quantity' => 1, 'unit' => 'piece',
            'unit_price' => 1, 'line_total' => 1, 'sort_order' => 2, 'measurement_eligibility' => 'not_required',
        ]);

        foreach ([$other->id, 999999] as $id) {
            try {
                $this->submit($this->all() + [$id => $this->capPayload()]);
                $this->fail('Expected validation failure.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey("items.$id", $e->errors());
            }
        }
        $this->assertSame(0, MeasurementSubmission::count());
    }

    public function test_submitted_items_are_locked_against_resubmission(): void
    {
        $this->submit($this->all());

        $this->expectException(ValidationException::class);
        $this->submit($this->all());
    }

    public function test_only_corrected_items_are_resubmitted_as_a_new_revision(): void
    {
        $this->submit($this->all());
        MeasurementSubmission::where('order_item_id', $this->shirt->id)->update([
            'status' => 'correction_requested', 'correction_reason' => 'Waist looks wrong.',
        ]);
        MeasurementSubmission::where('order_item_id', $this->cap->id)->update(['status' => 'accepted']);

        $created = $this->submit([$this->shirt->id => $this->shirtPayload('34')]);

        $this->assertCount(1, $created);
        $this->assertSame(2, $created->first()->revision);
        $this->assertSame(2, MeasurementSubmission::where('order_item_id', $this->shirt->id)->count());
        $this->assertSame(1, MeasurementSubmission::where('order_item_id', $this->cap->id)->count());
        $this->assertSame('correction_requested', MeasurementSubmission::where('order_item_id', $this->shirt->id)->where('revision', 1)->value('status'));
    }

    public function test_revoked_link_and_production_plan_block_submission(): void
    {
        app(RevokeMeasurementLink::class)->execute($this->admin, $this->link, ['reason' => 'Revoked for this test.']);

        try {
            $this->submit($this->all());
            $this->fail('Expected validation failure.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('link', $e->errors());
        }
        $this->assertSame(0, MeasurementSubmission::count());
    }

    public function test_a_production_plan_blocks_submission(): void
    {
        $this->order->update(['status' => Order::STATUS_IN_PRODUCTION]);
        ProductionPlan::create(['order_id' => $this->order->id, 'coordinator_id' => $this->admin->id, 'created_by' => $this->admin->id]);

        $this->expectException(ValidationException::class);
        $this->submit($this->all());
    }
}
