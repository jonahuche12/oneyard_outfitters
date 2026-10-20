<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\IndividualMeasurement;
use App\Models\MeasurementSizeQuantity;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementSubmissionModelTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderItem(): OrderItem
    {
        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
        ]);

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

        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'order_date' => now()->toDateString(),
            'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(),
            'status' => Order::STATUS_PENDING,
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
        ]);

        return OrderItem::create([
            'order_id' => $order->id,
            'quotation_item_id' => null,
            'product_specification_id' => null,
            'item_name' => 'Test uniform',
            'description' => null,
            'quantity' => 10,
            'unit' => 'piece',
            'unit_price' => 3000,
            'line_total' => 30000,
            'sort_order' => 0,
        ]);
    }

    public function test_order_item_returns_measurement_submissions_in_descending_revision_order(): void
    {
        $item = $this->createOrderItem();

        $older = MeasurementSubmission::create([
            'order_item_id' => $item->id,
            'revision' => 1,
            'measurement_method' => 'individual',
            'input_source' => 'manual',
            'status' => 'draft',
        ]);

        $newer = MeasurementSubmission::create([
            'order_item_id' => $item->id,
            'revision' => 2,
            'measurement_method' => 'individual',
            'input_source' => 'manual',
            'status' => 'draft',
        ]);

        $this->assertSame(
            [$newer->id, $older->id],
            $item->measurementSubmissions->pluck('id')->all()
        );

        $this->assertSame($item->id, $newer->orderItem->id);
    }

    public function test_submission_relationships_return_ordered_children_and_reviewer(): void
    {
        $item = $this->createOrderItem();
        $reviewer = User::factory()->create();

        $submission = MeasurementSubmission::create([
            'order_item_id' => $item->id,
            'revision' => 1,
            'measurement_method' => 'individual',
            'input_source' => 'manual',
            'status' => 'draft',
            'reviewed_by' => $reviewer->id,
        ]);

        $laterPosition = IndividualMeasurement::create([
            'measurement_submission_id' => $submission->id,
            'person_label' => 'Person 2',
            'measurements' => ['chest' => 34],
            'position' => 2,
        ]);

        $earlierPosition = IndividualMeasurement::create([
            'measurement_submission_id' => $submission->id,
            'person_label' => 'Person 1',
            'measurements' => ['chest' => 32],
            'position' => 1,
        ]);

        MeasurementSizeQuantity::create([
            'measurement_submission_id' => $submission->id,
            'size_label' => 'XL',
            'quantity' => 3,
        ]);

        MeasurementSizeQuantity::create([
            'measurement_submission_id' => $submission->id,
            'size_label' => 'M',
            'quantity' => 5,
        ]);

        $this->assertSame($item->id, $submission->orderItem->id);
        $this->assertSame($reviewer->id, $submission->reviewer->id);

        $this->assertSame(
            [$earlierPosition->id, $laterPosition->id],
            $submission->individualMeasurements->pluck('id')->all()
        );

        $this->assertSame(
            ['M', 'XL'],
            $submission->sizeQuantities->pluck('size_label')->all()
        );
    }

    public function test_measurement_attributes_are_cast_to_expected_types(): void
    {
        $item = $this->createOrderItem();

        $submission = MeasurementSubmission::create([
            'order_item_id' => $item->id,
            'revision' => '3',
            'measurement_method' => 'individual',
            'input_source' => 'manual',
            'status' => 'draft',
        ]);

        $measurement = IndividualMeasurement::create([
            'measurement_submission_id' => $submission->id,
            'measurements' => ['chest' => 36, 'waist' => 30],
            'position' => '4',
        ]);

        $quantity = MeasurementSizeQuantity::create([
            'measurement_submission_id' => $submission->id,
            'size_label' => 'L',
            'quantity' => '7',
        ]);

        $this->assertIsInt($submission->revision);
        $this->assertIsArray($measurement->measurements);
        $this->assertSame(36, $measurement->measurements['chest']);
        $this->assertIsInt($measurement->position);
        $this->assertIsInt($quantity->quantity);
    }


    private function eligibilityTestActor(array $permissions, bool $active = true): \App\Models\User
    {
        $user = \App\Models\User::factory()->create(['is_active' => $active]);

        $role = \App\Models\Role::factory()->create([
            'name' => 'Measurement Eligibility Test ' . uniqid(),
            'slug' => 'measurement-eligibility-test-' . uniqid(),
        ]);

        $role->permissions()->createMany(
            collect($permissions)->map(fn (string $slug) => [
                'name' => $slug,
                'slug' => $slug,
            ])->all()
        );

        $user->roles()->attach($role);

        return $user;
    }

    public function test_measurement_eligibility_decision_is_audited(): void
    {
        $item = $this->createOrderItem();
        $actor = $this->eligibilityTestActor(['measurements.eligibility.manage']);

        $updated = app(\App\Actions\Measurements\ResolveOrderItemMeasurementEligibility::class)
            ->execute($actor, $item, [
                'eligibility' => 'required',
                'reason' => 'Individual body measurements are required for tailoring.',
            ]);

        $this->assertSame('required', $updated->measurement_eligibility);
        $this->assertSame(
            'Individual body measurements are required for tailoring.',
            $updated->measurement_eligibility_reason
        );
        $this->assertSame($actor->id, $updated->measurement_eligibility_decided_by);
        $this->assertNotNull($updated->measurement_eligibility_decided_at);

        $audit = \App\Models\OrderItemMeasurementEligibilityAudit::query()->sole();
        $this->assertSame($item->id, $audit->order_item_id);
        $this->assertSame('pending', $audit->old_eligibility);
        $this->assertSame('required', $audit->new_eligibility);
        $this->assertSame($actor->id, $audit->changed_by);
        $this->assertSame(
            'Individual body measurements are required for tailoring.',
            $audit->reason
        );
    }

    public function test_changing_measurement_eligibility_preserves_audit_history(): void
    {
        $item = $this->createOrderItem();
        $actor = $this->eligibilityTestActor(['measurements.eligibility.manage']);
        $action = app(\App\Actions\Measurements\ResolveOrderItemMeasurementEligibility::class);

        $action->execute($actor, $item, [
            'eligibility' => 'required',
            'reason' => 'Tailoring requires individual body measurements.',
        ]);

        $updated = $action->execute($actor, $item, [
            'eligibility' => 'not_required',
            'reason' => 'Management confirmed this item is supplied by standard size.',
        ]);

        $this->assertSame('not_required', $updated->measurement_eligibility);

        $audits = \App\Models\OrderItemMeasurementEligibilityAudit::query()
            ->where('order_item_id', $item->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $audits);
        $this->assertSame('pending', $audits[0]->old_eligibility);
        $this->assertSame('required', $audits[0]->new_eligibility);
        $this->assertSame('required', $audits[1]->old_eligibility);
        $this->assertSame('not_required', $audits[1]->new_eligibility);
    }

    public function test_inactive_or_unauthorized_actor_cannot_change_measurement_eligibility(): void
    {
        $item = $this->createOrderItem();
        $action = app(\App\Actions\Measurements\ResolveOrderItemMeasurementEligibility::class);
        $payload = [
            'eligibility' => 'not_required',
            'reason' => 'Management confirmed no individual measurements are needed.',
        ];

        foreach ([
            $this->eligibilityTestActor(['measurements.eligibility.manage'], false),
            $this->eligibilityTestActor(['orders.view']),
        ] as $actor) {
            try {
                $action->execute($actor, $item, $payload);
                $this->fail('Expected AuthorizationException.');
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                $this->assertSame('pending', $item->fresh()->measurement_eligibility);
            }
        }

        $this->assertDatabaseCount('order_item_measurement_eligibility_audits', 0);
    }

    public function test_invalid_measurement_eligibility_and_reason_are_rejected(): void
    {
        $item = $this->createOrderItem();
        $actor = $this->eligibilityTestActor(['measurements.eligibility.manage']);
        $action = app(\App\Actions\Measurements\ResolveOrderItemMeasurementEligibility::class);

        foreach ([
            ['eligibility' => 'pending', 'reason' => 'A valid reason for this decision.'],
            ['eligibility' => 'unknown', 'reason' => 'A valid reason for this decision.'],
            ['eligibility' => 'required', 'reason' => 'short'],
            ['eligibility' => 'required', 'reason' => '          '],
        ] as $payload) {
            try {
                $action->execute($actor, $item, $payload);
                $this->fail('Expected ValidationException.');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }

        $this->assertSame('pending', $item->fresh()->measurement_eligibility);
        $this->assertDatabaseCount('order_item_measurement_eligibility_audits', 0);
    }

}
