<?php

namespace Tests\Feature;

use App\Actions\Measurements\EnsureOrderMeasurementsReadyForProduction;
use App\Models\Contact;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MeasurementGateTest extends TestCase
{
    use RefreshDatabase;

    private function createApprovedOrder(): Order
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(),
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'created_by' => $user->id,
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
            'status' => Order::STATUS_APPROVED,
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
        ]);
    }

    private function addItem(Order $order, ?string $eligibility = null, string $name = 'Test uniform'): OrderItem
    {
        $attributes = [
            'order_id' => $order->id,
            'item_name' => $name,
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

    private function addRevision(OrderItem $item, int $revision, string $status): MeasurementSubmission
    {
        return MeasurementSubmission::create([
            'order_item_id' => $item->id,
            'revision' => $revision,
            'measurement_method' => 'individual',
            'input_source' => 'manual',
            'status' => $status,
        ]);
    }

    private function assertBlocked(Order $order, string $messagePart): void
    {
        try {
            app(EnsureOrderMeasurementsReadyForProduction::class)->handle($order);
            $this->fail('Expected the production gate to block this order.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('measurements', $e->errors());
            $this->assertStringContainsString($messagePart, $e->errors()['measurements'][0]);
        }
    }

    private function assertAllowed(Order $order): void
    {
        app(EnsureOrderMeasurementsReadyForProduction::class)->handle($order);
        $this->assertTrue(true);
    }

    public function test_pending_item_blocks_production(): void
    {
        $order = $this->createApprovedOrder();
        $this->addItem($order);

        $this->assertBlocked($order, 'has not been resolved');
    }

    public function test_not_required_item_does_not_block(): void
    {
        $order = $this->createApprovedOrder();
        $this->addItem($order, 'not_required');

        $this->assertAllowed($order);
    }

    public function test_required_item_without_revision_blocks(): void
    {
        $order = $this->createApprovedOrder();
        $this->addItem($order, 'required');

        $this->assertBlocked($order, 'accepted latest measurement revision');
    }

    public function test_required_item_with_submitted_latest_revision_blocks(): void
    {
        $order = $this->createApprovedOrder();
        $item = $this->addItem($order, 'required');
        $this->addRevision($item, 1, 'submitted');

        $this->assertBlocked($order, 'accepted latest measurement revision');
    }

    public function test_required_item_with_accepted_latest_revision_passes(): void
    {
        $order = $this->createApprovedOrder();
        $item = $this->addItem($order, 'required');
        $this->addRevision($item, 1, 'accepted');

        $this->assertAllowed($order);
    }

    public function test_only_the_latest_revision_counts(): void
    {
        $order = $this->createApprovedOrder();
        $item = $this->addItem($order, 'required');
        $this->addRevision($item, 1, 'accepted');
        $this->addRevision($item, 2, 'draft');

        $this->assertBlocked($order, 'accepted latest measurement revision');
    }

    public function test_one_unresolved_item_blocks_an_order_with_resolved_items(): void
    {
        $order = $this->createApprovedOrder();
        $this->addItem($order, 'not_required', 'Resolved item');
        $this->addItem($order, null, 'Undecided item');

        $this->assertBlocked($order, 'Undecided item');
    }

    public function test_order_without_items_is_not_blocked(): void
    {
        $this->assertAllowed($this->createApprovedOrder());
    }
}
