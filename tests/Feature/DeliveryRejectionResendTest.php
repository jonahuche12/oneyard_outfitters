<?php

namespace Tests\Feature;

use App\Mail\DeliveryActivationInvitation;
use App\Models\Contact;
use App\Models\Delivery;
use App\Models\DeliveryActivationRecipient;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DeliveryRejectionResendTest extends TestCase
{
    use RefreshDatabase;

    private function reviewer(): User
    {
        $role = Role::create([
            'name' => 'Delivery Reviewer',
            'slug' => 'delivery-reviewer',
            'description' => 'Test role.',
        ]);

        foreach (['deliveries.view', 'deliveries.review_offline_payment'] as $slug) {
            $role->permissions()->attach(
                Permission::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $slug, 'description' => 'Test permission.']
                )
            );
        }

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * A delivery whose customer activated it and claimed an offline payment.
     *
     * @return array{0: Delivery, 1: DeliveryActivationRecipient, 2: Contact}
     */
    private function claimedDelivery(): array
    {
        $creator = User::factory()->create();
        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'TMP-' . uniqid('', true),
            'organization_id' => $organization->id,
            'created_by' => $creator->id,
            'quotation_date' => now()->toDateString(),
            'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_DRAFT,
            'subtotal' => 0,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 0,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . uniqid('', true),
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => null,
            'order_date' => now()->toDateString(),
            'status' => Order::STATUS_READY,
            'subtotal' => 0,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 0,
        ]);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'created_by' => $creator->id,
            'status' => Delivery::STATUS_PENDING,
            'payment_arrangement' => Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE,
            'offline_payment_status' => Delivery::OFFLINE_PAYMENT_STATUS_PENDING,
            'offline_payment_claimed_at' => now(),
            'activated_at' => now(),
        ]);

        $recipient = DeliveryActivationRecipient::create([
            'delivery_id' => $delivery->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => str_repeat('a', 64),
            'status' => DeliveryActivationRecipient::STATUS_ACTIVATED,
            'notified_at' => now(),
            'activated_at' => now(),
        ]);

        return [$delivery, $recipient, $contact];
    }

    public function test_activation_form_returns_after_an_offline_claim_is_rejected(): void
    {
        [$delivery] = $this->claimedDelivery();
        $reviewer = $this->reviewer();

        $this->actingAs($reviewer)
            ->get(route('deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('Delivery Activated')
            ->assertDontSee('Send Activation Link(s)');

        $this->actingAs($reviewer)
            ->post(route('deliveries.offline-payment.reject', $delivery), [
                'notes' => 'Payment could not be verified.',
            ])
            ->assertSessionHas('success');

        $delivery->refresh();

        $this->assertNull($delivery->payment_arrangement);
        $this->assertNull($delivery->activated_at);
        $this->assertSame(
            Delivery::OFFLINE_PAYMENT_STATUS_REJECTED,
            $delivery->offline_payment_status
        );

        $this->actingAs($reviewer)
            ->get(route('deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('Send Activation Link(s)');
    }

    public function test_activation_links_can_be_sent_again_after_a_rejected_claim(): void
    {
        Mail::fake();

        [$delivery, $recipient, $contact] = $this->claimedDelivery();
        $reviewer = $this->reviewer();

        $this->actingAs($reviewer)
            ->post(route('deliveries.offline-payment.reject', $delivery), [
                'notes' => 'Payment could not be verified.',
            ]);

        $this->actingAs($reviewer)
            ->post(route('deliveries.activation-invitations.store', $delivery), [
                'contact_ids' => [$contact->id],
            ])
            ->assertRedirect(route('deliveries.show', $delivery))
            ->assertSessionHas('success');

        Mail::assertQueued(DeliveryActivationInvitation::class, 1);

        $recipient->refresh();

        $this->assertNotSame(str_repeat('a', 64), $recipient->access_token);
        $this->assertSame(
            DeliveryActivationRecipient::STATUS_PENDING,
            $recipient->status
        );
    }
}
