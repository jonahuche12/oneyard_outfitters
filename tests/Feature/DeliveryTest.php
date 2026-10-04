<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\Quotation;
use App\Models\Contact;
use App\Models\Order;
use App\Models\QuotationRecipient;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(
        array $permissions
    ): User {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $role = Role::factory()->create([
            'name' => 'Delivery Test Role ' . uniqid(),
            'slug' => 'delivery-test-' . uniqid(),
        ]);

        $role->permissions()->createMany(
            collect($permissions)
                ->map(fn (string $slug) => [
                    'name' => $slug,
                    'slug' => $slug,
                ])
                ->all()
        );

        $user->roles()->attach($role);

        return $user;
    }

    private function readyOrder(): Order
    {
        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $quotationCreator = User::factory()->create([
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(),
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'created_by' => $quotationCreator->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 100000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 100000,
            'terms' => null,
            'notes' => 'Delivery test quotation.',
        ]);

        return Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'order_date' => now()->toDateString(),
            'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(),
            'status' => Order::STATUS_READY,
            'subtotal' => 100000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 100000,
            'terms' => null,
            'notes' => 'Delivery test order.',
        ]);
    }

    public function test_delivery_staff_can_move_ready_order_into_delivery(): void
    {
        $user = $this->userWithPermissions([
            'deliveries.view',
            'deliveries.create',
        ]);

        $order = $this->readyOrder();

        $response = $this->actingAs($user)
            ->post(route('orders.delivery.store', $order), [
                'notes' => 'Ready for delivery.',
                'delivery_date' => now()->addDay()->toDateString(),
            ]);

        $response
            ->assertRedirect();

        $this->assertDatabaseHas('deliveries', [
            'order_id' => $order->id,
            'created_by' => $user->id,
            'status' => Delivery::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_READY,
        ]);
    }

    public function test_user_without_delivery_create_permission_cannot_start_delivery(): void
    {
        $user = $this->userWithPermissions([
            'deliveries.view',
        ]);

        $order = $this->readyOrder();

        $this->actingAs($user)
            ->post(route('orders.delivery.store', $order))
            ->assertForbidden();

        $this->assertDatabaseCount('deliveries', 0);
    }

    public function test_delivery_cannot_be_started_before_quality_control_passes(): void
    {
        $user = $this->userWithPermissions([
            'deliveries.create',
        ]);

        $order = $this->readyOrder();
        $order->update([
            'status' => Order::STATUS_IN_PRODUCTION,
        ]);

        $this->actingAs($user)
            ->post(route('orders.delivery.store', $order))
            ->assertForbidden();

        $this->assertDatabaseCount('deliveries', 0);
    }

    public function test_delivery_can_be_confirmed_and_order_becomes_delivered(): void
    {
        $user = $this->userWithPermissions([
            'deliveries.create',
            'deliveries.confirm',
        ]);

        $order = $this->readyOrder();

        $this->actingAs($user)
            ->post(route('orders.delivery.store', $order))
            ->assertRedirect();

        $delivery = Delivery::query()
            ->where('order_id', $order->id)
            ->firstOrFail();

        $recipient = QuotationRecipient::create([
            'quotation_id' => $order->quotation_id,
            'contact_id' => $order->contact_id,
            'email' => 'delivery-test@example.com',
            'access_token' => bin2hex(random_bytes(32)),
            'response_status' => 'accepted',
            'payment_percentage' => 100,
            'payment_amount' => $order->total,
            'amount_paid' => 0,
        ]);

        $delivery->update([
            'payment_arrangement' => Delivery::PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY,
            'activated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(
                route('deliveries.confirm', $delivery),
                [
                    'confirmation' => '1',
                    'notes' => 'Order delivered and confirmed.',
                ]
            )
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('payments', [
            'organization_id' => $order->organization_id,
            'quotation_id' => $order->quotation_id,
            'quotation_recipient_id' => $recipient->id,
            'amount' => '100000.00',
            'payment_method' => Delivery::PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('deliveries', [
            'id' => $delivery->id,
            'status' => Delivery::STATUS_CONFIRMED,
            'confirmed_by' => $user->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_DELIVERED,
        ]);

        $totalPaid = (float) $order->quotation
            ->payments()
            ->where('status', 'completed')
            ->sum('amount');

        $this->assertEquals(
            (float) $order->total,
            $totalPaid
        );
    }
}
