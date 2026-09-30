<?php

namespace Tests\Feature;

use App\Mail\QuotationInvitation;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\ProductSpecification;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationRecipient;
use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(string ...$permissions): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $role = Role::create([
            'name' => 'Quotation Test Role',
            'slug' => 'quotation-test-role-' . $user->id,
            'description' => 'Role created for quotation feature tests.',
        ]);

        foreach ($permissions as $permissionSlug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $permissionSlug],
                [
                    'name' => ucwords(str_replace(['.', '-'], ' ', $permissionSlug)),
                    'description' => 'Quotation feature test permission.',
                ]
            );

            $role->permissions()->attach($permission);
        }

        $user->roles()->attach($role);

        return $user;
    }

    private function createQuotation(
        User $user,
        Organization $organization,
        array $overrides = []
    ): Quotation {
        return Quotation::create([
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
            ...$overrides,
        ]);
    }

    public function test_authorized_user_can_create_a_draft_quotation(): void
    {
        $user = $this->userWithPermissions('quotations.create');

        $organization = Organization::factory()->create();

        $specification = ProductSpecification::factory()->create([
            'organization_id' => $organization->id,
            'item_name' => 'School Shirt',
            'description' => 'White short-sleeve school shirt.',
            'unit' => 'piece',
            'unit_price' => 3500,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->post(route('quotations.store'), [
            'organization_id' => $organization->id,
            'quotation_date' => '2026-09-24',
            'expected_delivery_days' => 30,
            'discount' => 1000,
            'additional_charges' => 500,
            'terms' => 'Valid for 30 days.',
            'notes' => 'Test quotation.',
            'product_specification_ids' => [$specification->id],
            'quantities' => [
                $specification->id => 500,
            ],
        ]);

        $quotation = Quotation::query()->first();

        $this->assertNotNull($quotation);

        $response->assertRedirect(route('quotations.show', $quotation));

        $this->assertSame(
            'QUO-' . str_pad((string) $quotation->id, 6, '0', STR_PAD_LEFT),
            $quotation->quotation_number
        );
        $this->assertSame(Quotation::STATUS_DRAFT, $quotation->status);
        $this->assertSame($user->id, $quotation->created_by);
        $this->assertSame($organization->id, $quotation->organization_id);

        $item = $quotation->items()->first();

        $this->assertNotNull($item);
        $this->assertSame($specification->id, $item->product_specification_id);
        $this->assertSame('School Shirt', $item->item_name);
        $this->assertSame('White short-sleeve school shirt.', $item->description);
        $this->assertSame('piece', $item->unit);
        $this->assertSame('500.00', $item->quantity);
        $this->assertSame('3500.00', $item->unit_price);
        $this->assertSame('1750000.00', $item->line_total);

        $this->assertSame('1750000.00', $quotation->subtotal);
        $this->assertSame('1000.00', $quotation->discount);
        $this->assertSame('500.00', $quotation->additional_charges);
        $this->assertSame('1749500.00', $quotation->total);
    }

    public function test_user_without_create_permission_cannot_create_quotation(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $organization = Organization::factory()->create();

        $response = $this->actingAs($user)->post(route('quotations.store'), [
            'organization_id' => $organization->id,
            'quotation_date' => '2026-09-24',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('quotations', 0);
    }

    public function test_contact_must_belong_to_selected_organization(): void
    {
        $user = $this->userWithPermissions('quotations.create');

        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $specification = ProductSpecification::factory()->create([
            'organization_id' => $organization->id,
            'unit_price' => 3500,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->post(route('quotations.store'), [
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'quotation_date' => '2026-09-24',
            'expected_delivery_days' => 30,
            'product_specification_ids' => [$specification->id],
            'quantities' => [
                $specification->id => 100,
            ],
        ]);

        $response->assertSessionHasErrors('contact_id');

        $this->assertDatabaseCount('quotations', 0);
    }

    public function test_product_specification_must_belong_to_quotation_organization(): void
    {
        $user = $this->userWithPermissions(
            'quotations.create',
            'quotations.update',
            'quotations.view'
        );

        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        $quotation = $this->createQuotation($user, $organization);

        $specification = ProductSpecification::factory()->create([
            'organization_id' => $otherOrganization->id,
            'unit_price' => 2500,
        ]);

        $response = $this->actingAs($user)->post(
            route('quotation-items.store', $quotation),
            [
                'product_specification_id' => $specification->id,
                'item_name' => 'Incorrect specification',
                'quantity' => 2,
                'unit' => 'piece',
                'unit_price' => 2500,
            ]
        );

        $response->assertSessionHasErrors('product_specification_id');

        $this->assertDatabaseCount('quotation_items', 0);
    }

    public function test_product_specification_values_are_snapshotted_and_totals_are_calculated_server_side(): void
    {
        $user = $this->userWithPermissions(
            'quotations.create',
            'quotations.update',
            'quotations.view'
        );

        $organization = Organization::factory()->create();

        $quotation = $this->createQuotation(
            $user,
            $organization,
            [
                'discount' => 1000,
                'additional_charges' => 500,
            ]
        );

        $specification = ProductSpecification::factory()->create([
            'organization_id' => $organization->id,
            'item_name' => 'School Shirt',
            'description' => 'White long-sleeve shirt',
            'unit' => 'piece',
            'unit_price' => 3500,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($user)->post(
            route('quotation-items.store', $quotation),
            [
                'product_specification_id' => $specification->id,
                'item_name' => 'Tampered Browser Value',
                'description' => 'Tampered description',
                'quantity' => 4,
                'unit' => 'dozen',
                'unit_price' => 4000,
            ]
        );

        $item = QuotationItem::query()->first();

        $response->assertRedirect(route('quotations.show', $quotation));

        $this->assertNotNull($item);
        $this->assertSame('School Shirt', $item->item_name);
        $this->assertSame('White long-sleeve shirt', $item->description);
        $this->assertSame('piece', $item->unit);
        $this->assertEquals(4000, $item->unit_price);
        $this->assertEquals(16000, $item->line_total);

        $quotation->refresh();

        $this->assertEquals(16000, $quotation->subtotal);
        $this->assertEquals(15500, $quotation->total);
    }

    public function test_inactive_organization_cannot_create_quotation(): void
    {
        $user = $this->userWithPermissions('quotations.create');

        $organization = Organization::factory()->create([
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('quotations.store'), [
                'organization_id' => $organization->id,
                'quotation_date' => '2026-09-24',
                'expected_delivery_days' => 30,
                'discount' => 0,
                'additional_charges' => 0,
            ]);

        $response->assertSessionHasErrors('organization_id');

        $this->assertDatabaseMissing('quotations', [
            'organization_id' => $organization->id,
        ]);
    }

    public function test_authorized_user_can_update_a_draft_quotation_item_and_totals_recalculate(): void
    {
        $user = $this->userWithPermissions(
            'quotations.update',
            'quotations.view'
        );

        $organization = Organization::factory()->create();
        $quotation = $this->createQuotation($user, $organization);

        $item = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'item_name' => 'School Shirt',
            'description' => 'Original description',
            'quantity' => 2,
            'unit' => 'piece',
            'unit_price' => 3000,
            'line_total' => 6000,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->patch(
            route('quotation-items.update', [$quotation, $item]),
            [
                'item_name' => 'Updated School Shirt',
                'description' => 'Updated description',
                'quantity' => 5,
                'unit' => 'piece',
                'unit_price' => 3500,
            ]
        );

        $response->assertRedirect(route('quotations.show', $quotation));

        $item->refresh();
        $quotation->refresh();

        $this->assertSame('Updated School Shirt', $item->item_name);
        $this->assertSame('Updated description', $item->description);
        $this->assertEquals(5, $item->quantity);
        $this->assertEquals(3500, $item->unit_price);
        $this->assertEquals(17500, $item->line_total);
        $this->assertEquals(17500, $quotation->subtotal);
        $this->assertEquals(17500, $quotation->total);
    }

    public function test_authorized_user_can_remove_a_draft_quotation_item_and_totals_recalculate(): void
    {
        $user = $this->userWithPermissions(
            'quotations.update',
            'quotations.view'
        );

        $organization = Organization::factory()->create();
        $quotation = $this->createQuotation($user, $organization);

        $itemToRemove = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'item_name' => 'School Shirt',
            'quantity' => 2,
            'unit' => 'piece',
            'unit_price' => 3000,
            'line_total' => 6000,
            'sort_order' => 0,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'item_name' => 'School Trouser',
            'quantity' => 3,
            'unit' => 'piece',
            'unit_price' => 4000,
            'line_total' => 12000,
            'sort_order' => 1,
        ]);

        $quotation->update([
            'subtotal' => 18000,
            'total' => 18000,
        ]);

        $response = $this->actingAs($user)->delete(
            route('quotation-items.destroy', [$quotation, $itemToRemove])
        );

        $response->assertRedirect(route('quotations.show', $quotation));

        $this->assertDatabaseMissing('quotation_items', [
            'id' => $itemToRemove->id,
        ]);

        $quotation->refresh();

        $this->assertEquals(12000, $quotation->subtotal);
        $this->assertEquals(12000, $quotation->total);
    }

    public function test_authorized_user_can_send_a_draft_quotation(): void
    {
        $user = $this->userWithPermissions('quotations.send');

        $organization = Organization::factory()->create();
        $quotation = $this->createQuotation($user, $organization);

        $response = $this->actingAs($user)->post(
            route('quotations.send', $quotation)
        );

        $response->assertRedirect(route('quotations.show', $quotation));

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => Quotation::STATUS_SENT,
        ]);
    }

    public function test_authorized_user_can_cancel_a_draft_quotation(): void
    {
        $user = $this->userWithPermissions('quotations.cancel');

        $organization = Organization::factory()->create();
        $quotation = $this->createQuotation($user, $organization);

        $response = $this->actingAs($user)->post(
            route('quotations.cancel', $quotation)
        );

        $response->assertRedirect(route('quotations.show', $quotation));

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => Quotation::STATUS_CANCELLED,
        ]);
    }

    public function test_authorized_user_can_cancel_a_sent_quotation(): void
    {
        $user = $this->userWithPermissions('quotations.cancel');

        $organization = Organization::factory()->create();

        $quotation = $this->createQuotation(
            $user,
            $organization,
            ['status' => Quotation::STATUS_SENT]
        );

        $response = $this->actingAs($user)->post(
            route('quotations.cancel', $quotation)
        );

        $response->assertRedirect(route('quotations.show', $quotation));

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => Quotation::STATUS_CANCELLED,
        ]);
    }


    public function test_sent_quotation_cannot_be_updated(): void
    {
        $user = $this->userWithPermissions('quotations.update');

        $organization = Organization::factory()->create();

        $quotation = $this->createQuotation(
            $user,
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
            ]
        );

        $response = $this->actingAs($user)->patch(
            route('quotations.update', $quotation),
            [
                'quotation_date' => '2026-09-24',
                'expected_delivery_days' => 30,
                'discount' => 0,
                'additional_charges' => 0,
            ]
        );

        $response->assertForbidden();
    }

    public function test_authorized_user_can_send_a_quotation_to_selected_contacts(): void
    {
        Mail::fake();

        $user = $this->userWithPermissions('quotations.send');

        $organization = Organization::factory()->create([
            'name' => 'Send Quotation Organization',
            'type' => 'school',
            'is_active' => true,
        ]);

        $contactOne = Contact::create([
            'organization_id' => $organization->id,
            'first_name' => 'Primary',
            'last_name' => 'Contact',
            'email' => 'primary@example.com',
            'phone' => '08000000001',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $contactTwo = Contact::create([
            'organization_id' => $organization->id,
            'first_name' => 'Secondary',
            'last_name' => 'Contact',
            'email' => 'secondary@example.com',
            'phone' => '08000000002',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $quotation = $this->createQuotation(
            $user,
            $organization,
            [
                'status' => Quotation::STATUS_DRAFT,
            ]
        );

        $response = $this->actingAs($user)->post(
            route('quotations.send-to-contacts', $quotation),
            [
                'contact_ids' => [
                    $contactOne->id,
                    $contactTwo->id,
                ],
            ]
        );

        $response
            ->assertRedirect()
            ->assertSessionHas('status');

        $quotation->refresh();

        expect($quotation->status)->toBe(Quotation::STATUS_SENT);

        $this->assertDatabaseHas('quotation_recipients', [
            'quotation_id' => $quotation->id,
            'contact_id' => $contactOne->id,
            'email' => 'primary@example.com',
            'response_status' => 'pending',
        ]);

        $this->assertDatabaseHas('quotation_recipients', [
            'quotation_id' => $quotation->id,
            'contact_id' => $contactTwo->id,
            'email' => 'secondary@example.com',
            'response_status' => 'pending',
        ]);

        $recipientOne = $quotation->recipients()
            ->where('contact_id', $contactOne->id)
            ->firstOrFail();

        $recipientTwo = $quotation->recipients()
            ->where('contact_id', $contactTwo->id)
            ->firstOrFail();

        expect($recipientOne->access_token)->toHaveLength(64);
        expect($recipientTwo->access_token)->toHaveLength(64);

        expect($recipientOne->sent_at)->not->toBeNull();
        expect($recipientTwo->sent_at)->not->toBeNull();

        Mail::assertSent(
            QuotationInvitation::class,
            2
        );
    }

    public function test_quotation_email_transport_failure_is_handled_without_marking_recipient_as_sent(): void
    {
        $user = $this->userWithPermissions('quotations.send');

        $organization = Organization::factory()->create([
            'name' => 'SMTP Failure Organization',
            'type' => 'school',
            'is_active' => true,
        ]);

        $contact = Contact::create([
            'organization_id' => $organization->id,
            'first_name' => 'SMTP',
            'last_name' => 'Failure',
            'email' => 'smtp-failure@example.com',
            'phone' => '08000000004',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $quotation = $this->createQuotation(
            $user,
            $organization,
            [
                'status' => Quotation::STATUS_DRAFT,
            ]
        );

        Mail::shouldReceive('to')
            ->once()
            ->with($contact->email)
            ->andThrow(
                new \Symfony\Component\Mailer\Exception\TransportException(
                    'SMTP connection failed.'
                )
            );

        $response = $this->actingAs($user)->post(
            route('quotations.send-to-contacts', $quotation),
            [
                'contact_ids' => [$contact->id],
            ]
        );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('quotation');

        expect($quotation->fresh()->status)
            ->toBe(Quotation::STATUS_DRAFT);

        $recipient = $quotation->recipients()
            ->where('contact_id', $contact->id)
            ->firstOrFail();

        expect($recipient->sent_at)->toBeNull();
    }

    public function test_quotation_cannot_be_sent_when_a_selected_contact_has_no_email(): void
    {
        Mail::fake();

        $user = $this->userWithPermissions('quotations.send');

        $organization = Organization::factory()->create([
            'name' => 'Missing Email Organization',
            'type' => 'school',
            'is_active' => true,
        ]);

        $contact = Contact::create([
            'organization_id' => $organization->id,
            'first_name' => 'No',
            'last_name' => 'Email',
            'email' => null,
            'phone' => '08000000003',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $quotation = $this->createQuotation(
            $user,
            $organization,
            [
                'status' => Quotation::STATUS_DRAFT,
            ]
        );

        $response = $this->actingAs($user)->post(
            route('quotations.send-to-contacts', $quotation),
            [
                'contact_ids' => [$contact->id],
            ]
        );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('contact_ids');

        expect($quotation->fresh()->status)
            ->toBe(Quotation::STATUS_DRAFT);

        $this->assertDatabaseMissing('quotation_recipients', [
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
        ]);

        Mail::assertNothingSent();
    }

    public function test_quotation_recipient_can_view_a_sent_quotation_through_a_secure_token(): void {

        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
            'is_active' => true,
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
            ]
        );

        $recipient = \App\Models\QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'pending',
        ]);

        $response = $this->get(
            route('public.quotations.show', $recipient->access_token)
        );

        $response->assertOk();

        expect(
            $recipient->fresh()->viewed_at
        )->not->toBeNull();
    }

    public function test_quotation_recipient_can_initialize_paystack_payment(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test-reference',
                    'access_code' => 'test-access-code',
                    'reference' => 'test-reference',
                ],
            ], 200),
        ]);

        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
                'total' => 100000,
            ]
        );

        $recipient = QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'pending',
        ]);

        $response = $this->post(
            route('public.quotations.accept', $recipient->access_token),
            [
                'payment_percentage' => 30,
            ]
        );

        $response->assertRedirect(
            'https://checkout.paystack.com/test-reference'
        );

        $freshRecipient = $recipient->fresh();

        expect($freshRecipient->response_status)->toBe('pending');
        expect($freshRecipient->payment_percentage)->toBe(30);
        expect((float) $freshRecipient->payment_amount)->toBe(30000.0);
        expect((float) $freshRecipient->amount_paid)->toBe(0.0);

        $transaction = PaymentTransaction::query()
            ->where('quotation_recipient_id', $recipient->id)
            ->firstOrFail();

        expect($transaction->reference)->not->toBeNull();
        expect((float) $transaction->amount)->toBe(30000.0);
        expect($transaction->status)->toBe('initialized');
        expect($transaction->authorization_url)
            ->toBe('https://checkout.paystack.com/test-reference');

        expect($quotation->fresh()->status)
            ->toBe(Quotation::STATUS_SENT);
    }

    public function test_quotation_recipient_can_select_80_percent_payment(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test-80',
                    'access_code' => 'test-access-code-80',
                    'reference' => 'test-80',
                ],
            ], 200),
        ]);

        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
                'total' => 100000,
            ]
        );

        $recipient = QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'pending',
        ]);

        $this->post(
            route('public.quotations.accept', $recipient->access_token),
            [
                'payment_percentage' => 80,
            ]
        )->assertRedirect(
            'https://checkout.paystack.com/test-80'
        );

        $freshRecipient = $recipient->fresh();

        expect($freshRecipient->response_status)->toBe('pending');
        expect($freshRecipient->payment_percentage)->toBe(80);
        expect((float) $freshRecipient->payment_amount)->toBe(80000.0);
        expect((float) $freshRecipient->amount_paid)->toBe(0.0);
    }

    public function test_quotation_recipient_cannot_select_invalid_payment_percentage(): void
    {
        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
                'total' => 100000,
            ]
        );

        $recipient = \App\Models\QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'pending',
        ]);

        $response = $this->post(
            route('public.quotations.accept', $recipient->access_token),
            [
                'payment_percentage' => 50,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('payment_percentage');

        expect($recipient->fresh()->response_status)->toBe('pending');
        expect($recipient->fresh()->payment_percentage)->toBeNull();
        expect($recipient->fresh()->payment_amount)->toBeNull();
    }

    public function test_quotation_recipient_must_provide_feedback_when_rejecting_a_quotation(): void {

        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
            'is_active' => true,
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
            ]
        );

        $recipient = \App\Models\QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'pending',
        ]);

        $response = $this->from(
            route('public.quotations.show', $recipient->access_token)
        )->post(
            route('public.quotations.reject', $recipient->access_token),
            []
        );

        $response->assertRedirect(
            route('public.quotations.show', $recipient->access_token)
        );

        $response->assertSessionHasErrors('rejection_feedback');

        expect($recipient->fresh()->response_status)->toBe('pending');
    }

    public function test_quotation_recipient_can_reject_a_quotation_with_feedback(): void {

        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
            'is_active' => true,
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
            ]
        );

        $recipient = \App\Models\QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'pending',
        ]);

        $feedback = 'The quoted price needs to be reviewed before we can proceed.';

        $response = $this->post(
            route('public.quotations.reject', $recipient->access_token),
            [
                'rejection_feedback' => $feedback,
            ]
        );

        $response->assertRedirect(
            route('public.quotations.show', $recipient->access_token)
        );

        $savedRecipient = $recipient->fresh();

        expect($savedRecipient->response_status)->toBe('rejected');
        expect($savedRecipient->rejection_feedback)->toBe($feedback);
        expect($savedRecipient->responded_at)->not->toBeNull();
        expect($quotation->fresh()->status)->toBe(Quotation::STATUS_REJECTED);
    }

    public function test_quotation_recipient_cannot_respond_twice(): void
    {
        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_ACCEPTED,
            ]
        );

        $recipient = QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'accepted',
            'responded_at' => now(),
            'payment_percentage' => 30,
            'payment_amount' => 30000,
            'amount_paid' => 30000,
        ]);

        $response = $this->post(
            route('public.quotations.reject', $recipient->access_token),
            [
                'rejection_feedback' => 'I changed my mind after accepting.',
            ]
        );

        $response->assertRedirect();

        $response->assertSessionHasErrors('quotation');

        $freshRecipient = $recipient->fresh();

        expect($freshRecipient->response_status)->toBe('accepted');
        expect($freshRecipient->rejection_feedback)->toBeNull();
    }

    public function test_successful_paystack_payment_verification_accepts_quotation(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 123456,
                    'status' => 'success',
                    'amount' => 300000000,
                    'currency' => 'NGN',
                    'reference' => 'OY-TEST-REFERENCE',
                ],
            ], 200),
        ]);

        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
                'total' => 10000000,
            ]
        );

        $recipient = QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'pending',
            'payment_percentage' => 30,
            'payment_amount' => 3000000,
            'amount_paid' => 0,
        ]);

        $transaction = PaymentTransaction::create([
            'quotation_recipient_id' => $recipient->id,
            'reference' => 'OY-TEST-REFERENCE',
            'amount' => 3000000,
            'currency' => 'NGN',
            'gateway' => 'paystack',
            'status' => 'initialized',
            'access_code' => 'test-access-code',
            'authorization_url' => 'https://checkout.paystack.com/test',
            'initialized_at' => now(),
        ]);

        $response = $this->get(
            route(
                'public.quotations.payment.callback',
                [
                    'token' => $recipient->access_token,
                    'reference' => $transaction->reference,
                ]
            )
        );

        $response->assertRedirect(
            route('public.quotations.show', $recipient->access_token)
        );

        $freshTransaction = $transaction->fresh();
        $freshRecipient = $recipient->fresh();

        expect($freshTransaction->status)->toBe('paid');
        expect($freshTransaction->gateway_transaction_id)->toBe('123456');
        expect($freshTransaction->paid_at)->not->toBeNull();

        expect($freshRecipient->response_status)->toBe('accepted');
        expect($freshRecipient->responded_at)->not->toBeNull();
        expect((float) $freshRecipient->amount_paid)->toBe(3000000.0);

        expect($quotation->fresh()->status)
            ->toBe(Quotation::STATUS_ACCEPTED);
    }

    public function test_unsuccessful_paystack_payment_does_not_accept_quotation(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 123457,
                    'status' => 'failed',
                    'amount' => 3000000,
                    'currency' => 'NGN',
                    'reference' => 'OY-FAILED-REFERENCE',
                ],
            ], 200),
        ]);

        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
                'total' => 100000,
            ]
        );

        $recipient = QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'pending',
            'payment_percentage' => 30,
            'payment_amount' => 30000,
            'amount_paid' => 0,
        ]);

        $transaction = PaymentTransaction::create([
            'quotation_recipient_id' => $recipient->id,
            'reference' => 'OY-FAILED-REFERENCE',
            'amount' => 30000,
            'currency' => 'NGN',
            'gateway' => 'paystack',
            'status' => 'initialized',
            'initialized_at' => now(),
        ]);

        $response = $this->get(
            route(
                'public.quotations.payment.callback',
                [
                    'token' => $recipient->access_token,
                    'reference' => $transaction->reference,
                ]
            )
        );

        $response->assertRedirect(
            route('public.quotations.show', $recipient->access_token)
        );

        $response->assertSessionHasErrors('quotation');

        expect($transaction->fresh()->status)->toBe('failed');
        expect($recipient->fresh()->response_status)->toBe('pending');
        expect((float) $recipient->fresh()->amount_paid)->toBe(0.0);
        expect($quotation->fresh()->status)->toBe(Quotation::STATUS_SENT);
    }

    public function test_paystack_payment_with_wrong_amount_does_not_accept_quotation(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 123458,
                    'status' => 'success',
                    'amount' => 2500000,
                    'currency' => 'NGN',
                    'reference' => 'OY-WRONG-AMOUNT',
                ],
            ], 200),
        ]);

        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
                'total' => 100000,
            ]
        );

        $recipient = QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'pending',
            'payment_percentage' => 30,
            'payment_amount' => 30000,
            'amount_paid' => 0,
        ]);

        $transaction = PaymentTransaction::create([
            'quotation_recipient_id' => $recipient->id,
            'reference' => 'OY-WRONG-AMOUNT',
            'amount' => 30000,
            'currency' => 'NGN',
            'gateway' => 'paystack',
            'status' => 'initialized',
            'initialized_at' => now(),
        ]);

        $response = $this->get(
            route(
                'public.quotations.payment.callback',
                [
                    'token' => $recipient->access_token,
                    'reference' => $transaction->reference,
                ]
            )
        );

        $response->assertRedirect(
            route('public.quotations.show', $recipient->access_token)
        );

        $response->assertSessionHasErrors('quotation');

        expect($transaction->fresh()->status)->toBe('failed');
        expect($recipient->fresh()->response_status)->toBe('pending');
        expect((float) $recipient->fresh()->amount_paid)->toBe(0.0);
        expect($quotation->fresh()->status)->toBe(Quotation::STATUS_SENT);
    }

    public function test_repeated_paystack_callback_does_not_process_payment_twice(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 123459,
                    'status' => 'success',
                    'amount' => 300000000,
                    'currency' => 'NGN',
                    'reference' => 'OY-DUPLICATE-REFERENCE',
                ],
            ], 200),
        ]);

        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
                'total' => 10000000,
            ]
        );

        $recipient = QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => bin2hex(random_bytes(32)),
            'access_code' => null,
            'response_status' => 'pending',
            'payment_percentage' => 30,
            'payment_amount' => 3000000,
            'amount_paid' => 0,
        ]);

        $transaction = PaymentTransaction::create([
            'quotation_recipient_id' => $recipient->id,
            'reference' => 'OY-DUPLICATE-REFERENCE',
            'amount' => 3000000,
            'currency' => 'NGN',
            'gateway' => 'paystack',
            'status' => 'initialized',
            'initialized_at' => now(),
        ]);

        $callback = route(
            'public.quotations.payment.callback',
            [
                'token' => $recipient->access_token,
                'reference' => $transaction->reference,
            ]
        );

        $this->get($callback)->assertRedirect(
            route('public.quotations.show', $recipient->access_token)
        );

        $paidAt = $transaction->fresh()->paid_at;
        $respondedAt = $recipient->fresh()->responded_at;

        $this->get($callback)
            ->assertRedirect(
                route('public.quotations.show', $recipient->access_token)
            )
            ->assertSessionHas('status', 'Payment has already been confirmed.');

        expect($transaction->fresh()->status)->toBe('paid');
        expect($transaction->fresh()->paid_at->equalTo($paidAt))->toBeTrue();
        expect($recipient->fresh()->response_status)->toBe('accepted');
        expect($recipient->fresh()->responded_at->equalTo($respondedAt))->toBeTrue();
        expect((float) $recipient->fresh()->amount_paid)->toBe(3000000.0);
    }

    public function test_one_verified_payment_is_enough_to_accept_a_multi_recipient_quotation(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 223456,
                    'status' => 'success',
                    'amount' => 300000000,
                    'currency' => 'NGN',
                    'reference' => 'OY-MULTI-RECIPIENT-REFERENCE',
                ],
            ], 200),
        ]);

        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $contactOne = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'one@example.com',
            'is_active' => true,
        ]);

        $contactTwo = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'two@example.com',
            'is_active' => true,
        ]);

        $quotation = $this->createQuotation(
            User::factory()->create([
                'is_active' => true,
            ]),
            $organization,
            [
                'status' => Quotation::STATUS_SENT,
                'total' => 10000000,
            ]
        );

        $recipientOne = QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contactOne->id,
            'email' => $contactOne->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'pending',
            'payment_percentage' => 30,
            'payment_amount' => 3000000,
            'amount_paid' => 0,
        ]);

        $recipientTwo = QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contactTwo->id,
            'email' => $contactTwo->email,
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'pending',
            'amount_paid' => 0,
        ]);

        $transaction = PaymentTransaction::create([
            'quotation_recipient_id' => $recipientOne->id,
            'reference' => 'OY-MULTI-RECIPIENT-REFERENCE',
            'amount' => 3000000,
            'currency' => 'NGN',
            'gateway' => 'paystack',
            'status' => 'initialized',
            'access_code' => 'test-access-code',
            'authorization_url' => 'https://checkout.paystack.com/test',
            'initialized_at' => now(),
        ]);

        $response = $this->get(
            route(
                'public.quotations.payment.callback',
                [
                    'token' => $recipientOne->access_token,
                    'reference' => $transaction->reference,
                ]
            )
        );

        $response->assertRedirect(
            route('public.quotations.show', $recipientOne->access_token)
        );

        expect($transaction->fresh()->status)->toBe('paid');
        expect($recipientOne->fresh()->response_status)->toBe('accepted');
        expect($recipientTwo->fresh()->response_status)->toBe('pending');

        expect($quotation->fresh()->status)
            ->toBe(Quotation::STATUS_ACCEPTED);

        expect(\App\Models\Payment::query()
            ->where('quotation_id', $quotation->id)
            ->count())
            ->toBe(1);

        expect(\App\Models\Order::query()
            ->where('quotation_id', $quotation->id)
            ->count())
            ->toBe(1);
    }
}
