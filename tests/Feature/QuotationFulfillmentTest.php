<?php

namespace Tests\Feature;

use App\Actions\Payments\FulfillVerifiedQuotationPayment;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\ProductSpecification;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;
use App\Mail\OrderCreated;
use App\Mail\PaymentConfirmed;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class QuotationFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private function createFulfillableTransaction(
        array $transactionOverrides = [],
        array $recipientOverrides = [],
        array $quotationOverrides = []
    ): PaymentTransaction {
        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'fulfillment-test@example.com',
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(),
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'created_by' => \App\Models\User::factory()->create()->id,
            'quotation_date' => '2026-09-27',
            'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 350000,
            'discount' => 5000,
            'additional_charges' => 10000,
            'total' => 355000,
            'terms' => 'Valid for 30 days.',
            'notes' => 'Fulfillment test quotation.',
            ...$quotationOverrides,
        ]);

        $specification = ProductSpecification::factory()->create([
            'organization_id' => $organization->id,
            'item_name' => 'School Shirt',
            'description' => 'White short-sleeve school shirt.',
            'unit' => 'piece',
            'unit_price' => 700,
            'status' => 'draft',
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_specification_id' => $specification->id,
            'item_name' => 'School Shirt',
            'description' => 'White short-sleeve school shirt.',
            'quantity' => 500,
            'unit' => 'piece',
            'unit_price' => 700,
            'line_total' => 350000,
            'sort_order' => 0,
        ]);

        $recipient = QuotationRecipient::create([
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'access_token' => 'test-token-' . uniqid(),
            'response_status' => 'accepted',
            'payment_percentage' => 80,
            'payment_amount' => 284000,
            'amount_paid' => 284000,
            'responded_at' => now(),
            ...$recipientOverrides,
        ]);

        return PaymentTransaction::create([
            'quotation_recipient_id' => $recipient->id,
            'reference' => 'OY-' . uniqid(),
            'amount' => 284000,
            'currency' => 'NGN',
            'gateway' => 'paystack',
            'status' => 'paid',
            'gateway_transaction_id' => '123456',
            'paid_at' => now(),
            ...$transactionOverrides,
        ]);
    }

    public function test_verified_payment_creates_business_payment_and_order(): void
    {
        $transaction = $this->createFulfillableTransaction();

        $order = app(FulfillVerifiedQuotationPayment::class)
            ->execute($transaction);

        $this->assertSame(
            'ORD-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            $order->order_number
        );

        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame('355000.00', $order->total);

        $this->assertDatabaseHas('payments', [
            'quotation_id' => $transaction->quotationRecipient->quotation_id,
            'quotation_recipient_id' => $transaction->quotation_recipient_id,
            'payment_transaction_id' => $transaction->id,
            'reference' => $transaction->reference,
            'amount' => '284000.00',
            'status' => 'completed',
        ]);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_order_items_snapshot_quotation_items(): void
    {
        $transaction = $this->createFulfillableTransaction();

        $order = app(FulfillVerifiedQuotationPayment::class)
            ->execute($transaction);

        $item = $order->items()->first();

        $this->assertNotNull($item);
        $this->assertSame('School Shirt', $item->item_name);
        $this->assertSame(
            'White short-sleeve school shirt.',
            $item->description
        );
        $this->assertSame('500.00', $item->quantity);
        $this->assertSame('700.00', $item->unit_price);
        $this->assertSame('350000.00', $item->line_total);
    }

    public function test_unpaid_transaction_cannot_be_fulfilled(): void
    {
        $transaction = $this->createFulfillableTransaction([
            'status' => 'initialized',
        ]);

        $this->expectException(RuntimeException::class);

        app(FulfillVerifiedQuotationPayment::class)->execute($transaction);

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_unaccepted_quotation_cannot_be_fulfilled(): void
    {
        $transaction = $this->createFulfillableTransaction(
            [],
            [],
            ['status' => Quotation::STATUS_SENT]
        );

        $this->expectException(RuntimeException::class);

        app(FulfillVerifiedQuotationPayment::class)->execute($transaction);

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_payment_amount_must_match_recipient_payment_amount(): void
    {
        $transaction = $this->createFulfillableTransaction(
            ['amount' => 283000]
        );

        $this->expectException(RuntimeException::class);

        app(FulfillVerifiedQuotationPayment::class)->execute($transaction);

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_repeated_fulfillment_does_not_create_duplicate_payment_or_order(): void
    {
        $transaction = $this->createFulfillableTransaction();

        $action = app(FulfillVerifiedQuotationPayment::class);

        $firstOrder = $action->execute($transaction);
        $secondOrder = $action->execute($transaction);

        $this->assertSame($firstOrder->id, $secondOrder->id);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_verified_payment_sends_customer_and_authorized_staff_notifications(): void
    {
        Mail::fake();

        $permission = Permission::create([
            'name' => 'View Orders',
            'slug' => 'orders.view',
            'description' => 'View orders.',
        ]);

        $role = Role::create([
            'name' => 'Order Viewer',
            'slug' => 'order-viewer',
            'description' => 'Can view orders.',
        ]);

        $role->permissions()->attach($permission);

        $authorizedStaff = User::factory()->create([
            'email' => 'orders@example.com',
            'is_active' => true,
        ]);

        $authorizedStaff->roles()->attach($role);

        User::factory()->create([
            'email' => 'no-orders-permission@example.com',
            'is_active' => true,
        ]);

        User::factory()->create([
            'email' => 'inactive-orders@example.com',
            'is_active' => false,
        ]);

        $transaction = $this->createFulfillableTransaction();

        $order = app(FulfillVerifiedQuotationPayment::class)
            ->execute($transaction);

        Mail::assertQueued(
            PaymentConfirmed::class,
            fn (PaymentConfirmed $mail): bool =>
                $mail->payment->id === Payment::query()->firstOrFail()->id
        );

        Mail::assertQueued(
            OrderCreated::class,
            fn (OrderCreated $mail): bool =>
                $mail->order->id === $order->id
        );

        Mail::assertQueued(
            PaymentConfirmed::class,
            1
        );

        Mail::assertQueued(
            OrderCreated::class,
            1
        );

        Mail::assertQueued(
            PaymentConfirmed::class,
            fn (PaymentConfirmed $mail): bool =>
                $mail->hasTo('fulfillment-test@example.com')
        );

        Mail::assertQueued(
            OrderCreated::class,
            fn (OrderCreated $mail): bool =>
                $mail->hasTo('orders@example.com')
        );

        Mail::assertNotQueued(
            OrderCreated::class,
            fn (OrderCreated $mail): bool =>
                $mail->hasTo('no-orders-permission@example.com')
        );

        Mail::assertNotQueued(
            OrderCreated::class,
            fn (OrderCreated $mail): bool =>
                $mail->hasTo('inactive-orders@example.com')
        );
    }

    public function test_repeated_fulfillment_does_not_send_duplicate_notifications(): void
    {
        Mail::fake();

        $transaction = $this->createFulfillableTransaction();

        $action = app(FulfillVerifiedQuotationPayment::class);

        $action->execute($transaction);
        $action->execute($transaction);

        Mail::assertQueued(
            PaymentConfirmed::class,
            1
        );

        Mail::assertQueued(
            OrderCreated::class,
            0
        );
    }

}
