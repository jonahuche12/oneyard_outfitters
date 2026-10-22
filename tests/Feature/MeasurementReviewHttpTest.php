<?php

namespace Tests\Feature;

use App\Jobs\SendMeasurementLinkEmail;
use App\Models\Contact;
use App\Models\MeasurementLink;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MeasurementReviewHttpTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;
    private Contact $contact;
    private MeasurementSubmission $sub;

    private function userWith(string $roleSlug, array $perms): User
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

    private function admin(): User
    {
        return $this->userWith('admin', ['measurements.review', 'measurements.links.manage', 'orders.view']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::factory()->create();
        $this->contact = Contact::factory()->create([
            'organization_id' => $org->id, 'is_active' => true, 'email' => 'contact@example.test',
        ]);
        $q = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(), 'organization_id' => $org->id, 'contact_id' => $this->contact->id,
            'created_by' => User::factory()->create()->id, 'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(), 'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_ACCEPTED, 'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);
        $this->order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'organization_id' => $org->id, 'quotation_id' => $q->id,
            'contact_id' => $this->contact->id, 'order_date' => now()->toDateString(), 'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(), 'status' => Order::STATUS_APPROVED,
            'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);
        $item = OrderItem::create([
            'order_id' => $this->order->id, 'unit_price' => 100, 'line_total' => 100,
            'measurement_eligibility' => 'required', 'item_name' => 'Cap', 'quantity' => 4,
            'unit' => 'piece', 'product_type' => 'other', 'sort_order' => 0,
        ]);
        $this->sub = MeasurementSubmission::query()->forceCreate([
            'order_item_id' => $item->id, 'revision' => 1, 'measurement_method' => 'size_quantity',
            'input_source' => 'manual', 'status' => 'submitted', 'submitted_at' => now(),
        ]);
    }

    private function oldLink(): MeasurementLink
    {
        $link = MeasurementLink::query()->forceCreate([
            'order_id' => $this->order->id, 'contact_id' => $this->contact->id,
            'token_hash' => hash('sha256', 'old-token'), 'expires_at' => now()->addDay(),
            'created_by' => User::factory()->create()->id, 'is_active_slot' => 1,
        ]);
        $this->sub->forceFill(['measurement_link_id' => $link->id])->save();

        return $link;
    }

    private function correctionUrl(): string
    {
        return route('orders.measurements.correction', [$this->order, $this->sub]);
    }

    public function test_admin_accepts_via_http(): void
    {
        $this->actingAs($this->admin())
            ->post(route('orders.measurements.accept', [$this->order, $this->sub]))
            ->assertRedirect();

        $this->assertSame('accepted', $this->sub->fresh()->status);
    }

    public function test_short_reason_is_refused_and_nothing_is_queued(): void
    {
        Queue::fake();
        $this->oldLink();

        $this->actingAs($this->admin())
            ->post($this->correctionUrl(), ['reason' => 'too short'])
            ->assertSessionHasErrors('reason');

        $this->assertSame('submitted', $this->sub->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_correction_issues_a_fresh_link_per_selected_contact_listing_the_reason(): void
    {
        Queue::fake();
        $old = $this->oldLink();
        $second = Contact::factory()->create([
            'organization_id' => $this->order->organization_id, 'is_active' => true, 'email' => 'second@example.test',
        ]);
        $reason = 'Chest sizes look too small, please re-check.';

        $this->actingAs($this->admin())
            ->post($this->correctionUrl(), ['reason' => $reason, 'contact_ids' => [$this->contact->id, $second->id]])
            ->assertRedirect();

        $fresh = $this->sub->fresh();
        $this->assertSame('correction_requested', $fresh->status);
        $this->assertSame($reason, $fresh->correction_reason);
        $this->assertNotNull($old->fresh()->revoked_at);
        $this->assertSame(2, MeasurementLink::where('order_id', $this->order->id)->where('is_active_slot', 1)->count());

        Queue::assertPushed(SendMeasurementLinkEmail::class, 2);
        Queue::assertPushed(SendMeasurementLinkEmail::class, fn ($job) => str_contains($job->correctionReason, 'Cap: ' . $reason));
    }

    public function test_correction_without_contacts_is_refused_and_not_committed(): void
    {
        Queue::fake();

        $this->actingAs($this->admin())
            ->post($this->correctionUrl(), ['reason' => 'Please re-measure all caps.'])
            ->assertSessionHasErrors('contact_ids');

        $this->assertSame('submitted', $this->sub->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_correction_rejects_contacts_without_email_inactive_or_from_another_organization(): void
    {
        Queue::fake();
        $orgId = $this->order->organization_id;
        $bad = [
            Contact::factory()->create(['organization_id' => $orgId, 'is_active' => true, 'email' => '']),
            Contact::factory()->create(['organization_id' => $orgId, 'is_active' => false, 'email' => 'off@example.test']),
            Contact::factory()->create(['is_active' => true, 'email' => 'other@example.test']),
        ];

        foreach ($bad as $contact) {
            $this->actingAs($this->admin())
                ->post($this->correctionUrl(), ['reason' => 'Please re-measure all caps.', 'contact_ids' => [$contact->id]])
                ->assertSessionHasErrors('contact_ids');
        }

        $this->assertSame('submitted', $this->sub->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_the_new_revision_starts_blank_and_the_rejected_one_is_kept(): void
    {
        $sizes = config('measurements.sizes');
        ['token' => $token] = app(\App\Actions\Measurements\IssueMeasurementLink::class)
            ->execute($this->admin(), $this->order, $this->contact);

        \App\Models\MeasurementSizeQuantity::query()->forceCreate([
            'measurement_submission_id' => $this->sub->id, 'size_label' => $sizes[0], 'quantity' => 3,
        ]);
        $this->sub->forceFill(['status' => 'correction_requested', 'correction_reason' => 'Redo the sizes please.'])->save();

        $draft = app(\App\Actions\Measurements\SaveMeasurementDraft::class)
            ->saveSizes($token, $this->sub->orderItem->id, [$sizes[1] => '2']);

        $this->assertSame(2, $draft->revision);
        $this->assertSame('draft', $draft->status);
        $this->assertSame([$sizes[1]], $draft->sizeQuantities->pluck('size_label')->all());
        $this->assertSame(1, \App\Models\MeasurementSizeQuantity::where('measurement_submission_id', $this->sub->id)->count());
    }

    public function test_correction_modal_lists_contacts_and_preticks_those_with_a_link(): void
    {
        $this->oldLink();
        $other = Contact::factory()->create([
            'organization_id' => $this->order->organization_id, 'is_active' => true, 'email' => 'other-modal@example.test',
        ]);
        $noEmail = Contact::factory()->create([
            'organization_id' => $this->order->organization_id, 'is_active' => true, 'email' => '',
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('orders.show', $this->order))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-correction-submit', $html);
        $this->assertMatchesRegularExpression('/value="' . $this->contact->id . '"\s+data-correction-contact[^>]*checked/s', $html);
        $this->assertDoesNotMatchRegularExpression('/value="' . $other->id . '"\s+data-correction-contact[^>]*checked/s', $html);
        $this->assertMatchesRegularExpression('/value="' . $noEmail->id . '"\s+data-correction-contact[^>]*disabled/s', $html);
    }

    public function test_user_without_permission_is_denied(): void
    {
        Queue::fake();
        $sales = $this->userWith('sales', ['orders.view']);

        $this->actingAs($sales)
            ->post($this->correctionUrl(), ['reason' => 'Please re-measure all caps.', 'contact_ids' => [$this->contact->id]])
            ->assertForbidden();

        $this->actingAs($sales)
            ->post(route('orders.measurements.accept', [$this->order, $this->sub]))
            ->assertForbidden();

        $this->assertSame('submitted', $this->sub->fresh()->status);
        Queue::assertNothingPushed();
    }
}
