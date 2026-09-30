<?php

namespace Tests\Feature;

use App\Mail\OrderApproved;
use App\Mail\OrderApprovedInternal;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function createApprovalUser(): User
    {
        $permission = Permission::create([
            'name' => 'Approve Orders',
            'slug' => 'orders.approve',
            'description' => 'Approve orders.',
        ]);

        $role = Role::create([
            'name' => 'Order Approver',
            'slug' => 'order-approver',
            'description' => 'Can approve orders.',
        ]);

        $role->permissions()->attach($permission);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->roles()->attach($role);

        return $user;
    }

    private function createUpdateUser(): User
    {
        $permission = Permission::create([
            'name' => 'Update Orders',
            'slug' => 'orders.update',
            'description' => 'Update orders.',
        ]);

        $role = Role::create([
            'name' => 'Order Updater',
            'slug' => 'order-updater',
            'description' => 'Can update orders.',
        ]);

        $role->permissions()->attach($permission);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->roles()->attach($role);

        return $user;
    }


    private function createOrderWithContacts(): array
    {
        $organization = Organization::factory()->create();

        $primary = Contact::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Primary',
            'last_name' => 'Contact',
            'email' => 'primary@example.com',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $secondary = Contact::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Secondary',
            'last_name' => 'Contact',
            'email' => 'secondary@example.com',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $quotation = \App\Models\Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(),
            'organization_id' => $organization->id,
            'contact_id' => $primary->id,
            'created_by' => User::factory()->create()->id,
            'quotation_date' => '2026-09-27',
            'valid_until' => '2026-10-27',
            'expected_delivery_days' => 30,
            'status' => \App\Models\Quotation::STATUS_ACCEPTED,
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
            'terms' => null,
            'notes' => 'Order approval test quotation.',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => $primary->id,
            'order_date' => '2026-09-27',
            'expected_delivery_days' => 30,
            'expected_delivery_date' => '2026-10-27',
            'status' => Order::STATUS_PENDING,
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
            'terms' => null,
            'notes' => 'Order approval test.',
        ]);

        return [
            'organization' => $organization,
            'primary' => $primary,
            'secondary' => $secondary,
            'order' => $order,
        ];
    }

    public function test_authorized_user_can_approve_order_and_create_notification_recipients(): void
    {
        Mail::fake();

        $user = $this->createApprovalUser();
        $data = $this->createOrderWithContacts();

        $response = $this
            ->actingAs($user)
            ->post(route('orders.approve', $data['order']), [
                'contact_ids' => [
                    $data['primary']->id,
                    $data['secondary']->id,
                ],
            ]);

        $response
            ->assertRedirect(route('orders.show', $data['order']))
            ->assertSessionHas(
                'status',
                'Order approved successfully and selected organization contacts have been notified.'
            );

        $data['order']->refresh();

        $this->assertSame(Order::STATUS_APPROVED, $data['order']->status);

        $this->assertDatabaseCount('order_notification_recipients', 2);

        $this->assertDatabaseHas('order_notification_recipients', [
            'order_id' => $data['order']->id,
            'contact_id' => $data['primary']->id,
            'email' => 'primary@example.com',
        ]);

        $this->assertDatabaseHas('order_notification_recipients', [
            'order_id' => $data['order']->id,
            'contact_id' => $data['secondary']->id,
            'email' => 'secondary@example.com',
        ]);

        $recipients = $data['order']
            ->notificationRecipients()
            ->get();

        $this->assertCount(2, $recipients);

        foreach ($recipients as $recipient) {
            $this->assertSame(64, strlen($recipient->access_token));
            $this->assertNotNull($recipient->notified_at);
        }
    }

    public function test_primary_contact_can_be_selected_for_approval_notification(): void
    {
        Mail::fake();

        $user = $this->createApprovalUser();
        $data = $this->createOrderWithContacts();

        $this
            ->actingAs($user)
            ->post(route('orders.approve', $data['order']), [
                'contact_ids' => [$data['primary']->id],
            ])
            ->assertRedirect(route('orders.show', $data['order']));

        $this->assertDatabaseHas('order_notification_recipients', [
            'order_id' => $data['order']->id,
            'contact_id' => $data['primary']->id,
        ]);

        Mail::assertQueued(
            OrderApproved::class,
            fn (OrderApproved $mail): bool =>
                $mail->hasTo('primary@example.com')
        );
    }

    public function test_approval_requires_at_least_one_selected_contact(): void
    {
        $user = $this->createApprovalUser();
        $data = $this->createOrderWithContacts();

        $response = $this
            ->actingAs($user)
            ->post(route('orders.approve', $data['order']), [
                'contact_ids' => [],
            ]);

        $response
            ->assertSessionHasErrors('contact_ids');

        $this->assertDatabaseHas('orders', [
            'id' => $data['order']->id,
            'status' => Order::STATUS_PENDING,
        ]);

        $this->assertDatabaseCount('order_notification_recipients', 0);
    }

    public function test_contact_from_another_organization_cannot_be_selected(): void
    {
        $user = $this->createApprovalUser();
        $data = $this->createOrderWithContacts();

        $otherOrganization = Organization::factory()->create();

        $otherContact = Contact::factory()->create([
            'organization_id' => $otherOrganization->id,
            'email' => 'other@example.com',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('orders.approve', $data['order']), [
                'contact_ids' => [$otherContact->id],
            ]);

        $response
            ->assertSessionHasErrors('contact_ids');

        $this->assertDatabaseHas('orders', [
            'id' => $data['order']->id,
            'status' => Order::STATUS_PENDING,
        ]);

        $this->assertDatabaseCount('order_notification_recipients', 0);
    }

    public function test_inactive_contact_cannot_be_selected(): void
    {
        $user = $this->createApprovalUser();
        $data = $this->createOrderWithContacts();

        $inactiveContact = Contact::factory()->create([
            'organization_id' => $data['organization']->id,
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('orders.approve', $data['order']), [
                'contact_ids' => [$inactiveContact->id],
            ]);

        $response
            ->assertSessionHasErrors('contact_ids');

        $this->assertDatabaseHas('orders', [
            'id' => $data['order']->id,
            'status' => Order::STATUS_PENDING,
        ]);

        $this->assertDatabaseCount('order_notification_recipients', 0);
    }

    public function test_user_without_approve_permission_cannot_approve_order(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $data = $this->createOrderWithContacts();

        $this
            ->actingAs($user)
            ->post(route('orders.approve', $data['order']), [
                'contact_ids' => [$data['primary']->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $data['order']->id,
            'status' => Order::STATUS_PENDING,
        ]);

        $this->assertDatabaseCount('order_notification_recipients', 0);
    }

    public function test_approval_notifies_selected_contacts_and_all_active_super_admins(): void
    {
        Mail::fake();

        $user = $this->createApprovalUser();

        $superAdminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Super administrator.',
        ]);

        $superAdminOne = User::factory()->create([
            'email' => 'superadmin-one@example.com',
            'is_active' => true,
        ]);

        $superAdminTwo = User::factory()->create([
            'email' => 'superadmin-two@example.com',
            'is_active' => true,
        ]);

        $inactiveSuperAdmin = User::factory()->create([
            'email' => 'inactive-superadmin@example.com',
            'is_active' => false,
        ]);

        $superAdminOne->roles()->attach($superAdminRole);
        $superAdminTwo->roles()->attach($superAdminRole);
        $inactiveSuperAdmin->roles()->attach($superAdminRole);

        $data = $this->createOrderWithContacts();

        $this
            ->actingAs($user)
            ->post(route('orders.approve', $data['order']), [
                'contact_ids' => [$data['primary']->id],
            ])
            ->assertRedirect(route('orders.show', $data['order']));

        Mail::assertQueued(OrderApproved::class, 1);

        Mail::assertQueued(
            OrderApproved::class,
            fn (OrderApproved $mail): bool =>
                $mail->hasTo('primary@example.com')
        );

        Mail::assertQueued(OrderApprovedInternal::class, 2);

        Mail::assertQueued(
            OrderApprovedInternal::class,
            fn (OrderApprovedInternal $mail): bool =>
                $mail->hasTo('superadmin-one@example.com')
        );

        Mail::assertQueued(
            OrderApprovedInternal::class,
            fn (OrderApprovedInternal $mail): bool =>
                $mail->hasTo('superadmin-two@example.com')
        );

        Mail::assertNotQueued(
            OrderApprovedInternal::class,
            fn (OrderApprovedInternal $mail): bool =>
                $mail->hasTo('inactive-superadmin@example.com')
        );
    }

    public function test_approval_generates_unique_tracking_tokens(): void
    {
        Mail::fake();

        $user = $this->createApprovalUser();
        $data = $this->createOrderWithContacts();

        $this
            ->actingAs($user)
            ->post(route('orders.approve', $data['order']), [
                'contact_ids' => [
                    $data['primary']->id,
                    $data['secondary']->id,
                ],
            ])
            ->assertRedirect();

        $tokens = $data['order']
            ->notificationRecipients()
            ->pluck('access_token')
            ->all();

        $this->assertCount(2, $tokens);
        $this->assertCount(2, array_unique($tokens));

        foreach ($tokens as $token) {
            $this->assertSame(64, strlen($token));
        }
    }

    public function test_approved_order_cannot_be_approved_again(): void
    {
        Mail::fake();

        $user = $this->createApprovalUser();
        $data = $this->createOrderWithContacts();

        $this
            ->actingAs($user)
            ->post(route('orders.approve', $data['order']), [
                'contact_ids' => [$data['primary']->id],
            ])
            ->assertRedirect();

        $this
            ->actingAs($user)
            ->post(route('orders.approve', $data['order']), [
                'contact_ids' => [$data['secondary']->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('order_notification_recipients', 1);
    }

    public function test_public_order_tracking_page_rejects_invalid_notification_token(): void
    {
        $response = $this->get(
            route('public.orders.show', 'invalid-token-that-does-not-exist')
        );

        $response->assertNotFound();
    }

    public function test_notification_token_only_grants_access_to_its_specific_order(): void
    {
        Mail::fake();

        $user = $this->createApprovalUser();

        $first = $this->createOrderWithContacts();
        $second = $this->createOrderWithContacts();

        $this
            ->actingAs($user)
            ->post(route('orders.approve', $first['order']), [
                'contact_ids' => [$first['primary']->id],
            ])
            ->assertRedirect();

        $this
            ->actingAs($user)
            ->post(route('orders.approve', $second['order']), [
                'contact_ids' => [$second['primary']->id],
            ])
            ->assertRedirect();

        $firstRecipient = $first['order']
            ->notificationRecipients()
            ->firstOrFail();

        $secondRecipient = $second['order']
            ->notificationRecipients()
            ->firstOrFail();

        $this->assertNotSame(
            $firstRecipient->access_token,
            $secondRecipient->access_token
        );

        $this->assertStringContainsString(
            $firstRecipient->access_token,
            route('public.orders.show', $firstRecipient->access_token)
        );

        $this->assertStringNotContainsString(
            '/' . $first['order']->id,
            route('public.orders.show', $firstRecipient->access_token)
        );

        $this
            ->get(route('public.orders.show', $firstRecipient->access_token))
            ->assertOk()
            ->assertSee($first['order']->order_number)
            ->assertDontSee($second['order']->order_number);

        $this
            ->get(route('public.orders.show', $secondRecipient->access_token))
            ->assertOk()
            ->assertSee($second['order']->order_number)
            ->assertDontSee($first['order']->order_number);
    }

    public function test_public_order_tracking_page_is_available_using_notification_token(): void
    {
        Mail::fake();

        $user = $this->createApprovalUser();
        $data = $this->createOrderWithContacts();

        $this
            ->actingAs($user)
            ->post(route('orders.approve', $data['order']), [
                'contact_ids' => [$data['primary']->id],
            ])
            ->assertRedirect();

        $recipient = $data['order']
            ->notificationRecipients()
            ->firstOrFail();

        $response = $this->get(
            route('public.orders.show', $recipient->access_token)
        );

        $response
            ->assertOk()
            ->assertSee($data['order']->order_number)
            ->assertSee($data['organization']->name)
            ->assertSee('Primary Contact');
    }


    private function createTestOrder(User $createdBy, array $overrides = []): Order
    {
        $organization = Organization::create([
            'organization_code' => 'ORG-' . str_pad((string) (Organization::max('id') + 1), 6, '0', STR_PAD_LEFT),
            'name' => 'Test Organization',
            'type' => 'school',
            'ownership' => 'private',
            'phone' => '08000000000',
            'email' => 'test@example.com',
            'address' => 'Test Address',
            'city' => 'Aba',
            'area' => 'Aba',
            'lga' => 'Aba South',
            'state' => 'Abia',
            'country' => 'Nigeria',
            'active' => true,
        ]);

        $contact = Contact::create([
            'organization_id' => $organization->id,
            'first_name' => 'Test',
            'last_name' => 'Contact',
            'phone' => '08000000001',
            'email' => 'contact@example.com',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $quotation = \App\Models\Quotation::create([
            'quotation_number' => 'QT-' . str_pad((string) (\App\Models\Quotation::max('id') + 1), 6, '0', STR_PAD_LEFT),
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'created_by' => $createdBy->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30,
            'status' => 'draft',
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
            'terms' => null,
            'notes' => null,
        ]);

        return Order::create(array_merge([
            'order_number' => 'ORD-' . str_pad((string) (Order::max('id') + 1), 6, '0', STR_PAD_LEFT),
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'order_date' => now()->toDateString(),
            'status' => Order::STATUS_PENDING,
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
            'terms' => null,
            'notes' => null,
        ], $overrides));
    }

    public function test_authorized_user_can_update_order_delivery_estimate(): void
    {
        $user = $this->createUpdateUser();

        $order = $this->createTestOrder($user, [
            'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->actingAs($user)
            ->put(route('orders.update', $order), [
                'expected_delivery_days' => 45,
                'expected_delivery_date' => now()->addDays(45)->toDateString(),
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas(
                'status',
                'Order delivery estimate updated successfully.'
            );

        $order->refresh();

        $this->assertSame(45, $order->expected_delivery_days);
        $this->assertSame(
            now()->addDays(45)->toDateString(),
            $order->expected_delivery_date->toDateString()
        );
    }

    public function test_user_without_update_permission_cannot_update_order_delivery_estimate(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $order = $this->createTestOrder($user, [
            'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->actingAs($user)
            ->put(route('orders.update', $order), [
                'expected_delivery_days' => 45,
                'expected_delivery_date' => now()->addDays(45)->toDateString(),
            ])
            ->assertForbidden();

        $order->refresh();

        $this->assertSame(30, $order->expected_delivery_days);
        $this->assertSame(
            now()->addDays(30)->toDateString(),
            $order->expected_delivery_date->toDateString()
        );
    }

    public function test_delivery_estimate_update_validates_number_of_days(): void
    {
        $user = $this->createUpdateUser();

        $order = $this->createTestOrder($user, [
            'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->actingAs($user)
            ->put(route('orders.update', $order), [
                'expected_delivery_days' => 0,
                'expected_delivery_date' => now()->addDays(30)->toDateString(),
            ])
            ->assertSessionHasErrors('expected_delivery_days');

        $this->actingAs($user)
            ->put(route('orders.update', $order), [
                'expected_delivery_days' => 366,
                'expected_delivery_date' => now()->addDays(30)->toDateString(),
            ])
            ->assertSessionHasErrors('expected_delivery_days');
    }

    public function test_delivery_estimate_update_validates_expected_delivery_date(): void
    {
        $user = $this->createUpdateUser();

        $order = $this->createTestOrder($user);

        $this->actingAs($user)
            ->put(route('orders.update', $order), [
                'expected_delivery_days' => 30,
                'expected_delivery_date' => 'not-a-date',
            ])
            ->assertSessionHasErrors('expected_delivery_date');
    }

    public function test_delivery_estimate_update_preserves_the_rest_of_the_order(): void
    {
        $user = $this->createUpdateUser();

        $order = $this->createTestOrder($user, [
            'status' => Order::STATUS_PENDING,
            'subtotal' => 100000,
            'discount' => 5000,
            'additional_charges' => 2000,
            'total' => 97000,
            'terms' => 'Original terms',
            'notes' => 'Original notes',
            'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->actingAs($user)
            ->put(route('orders.update', $order), [
                'expected_delivery_days' => 60,
                'expected_delivery_date' => now()->addDays(60)->toDateString(),
            ])
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();

        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame(100000.0, (float) $order->subtotal);
        $this->assertSame(5000.0, (float) $order->discount);
        $this->assertSame(2000.0, (float) $order->additional_charges);
        $this->assertSame(97000.0, (float) $order->total);
        $this->assertSame('Original terms', $order->terms);
        $this->assertSame('Original notes', $order->notes);
        $this->assertSame(60, $order->expected_delivery_days);
    }

}
