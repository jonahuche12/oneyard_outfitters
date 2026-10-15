<?php

namespace Tests\Feature\Earnings;

use App\Models\Contact;
use App\Models\EarningsActivityWeight;
use App\Models\Order;
use App\Models\OrderEarningsContribution;
use App\Models\OrderEarningsPool;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\QuotationRecipient;
use App\Models\Role;
use App\Models\StaffWalletEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoolActionsTest extends TestCase
{
    use RefreshDatabase;

    private ?User $finance = null;

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::factory()->create([
            'name' => 'Actions Role ' . uniqid(),
            'slug' => 'actions-role-' . uniqid(),
        ]);
        $role->permissions()->createMany(
            collect($permissions)->map(fn ($s) => ['name' => $s, 'slug' => $s])->all()
        );
        $user->roles()->attach($role);

        return $user;
    }

    private function finance(): User
    {
        return $this->finance ??= $this->userWith([
            'earnings.pools.view',
            'earnings.contributions.assign',
            'earnings.pools.finalize',
        ]);
    }

    private function weight(string $key = 'production_execution', string $points = '2.0000'): void
    {
        EarningsActivityWeight::query()->create([
            'activity_key' => $key,
            'label' => ucfirst(str_replace('_', ' ', $key)),
            'points' => $points,
            'is_enabled' => true,
        ]);
    }

    private function pool(string $status = OrderEarningsPool::STATUS_PENDING): OrderEarningsPool
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'is_primary' => true,
            'is_active' => true,
        ]);
        $creator = User::factory()->create(['is_active' => true]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(),
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'created_by' => $creator->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 100000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 100000,
            'terms' => null,
            'notes' => 'Actions test quotation.',
        ]);

        $order = Order::create([
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
            'notes' => 'Actions test order.',
        ]);

        return OrderEarningsPool::query()->create([
            'order_id' => $order->id,
            'quotation_id' => $quotation->id,
            'quotation_number' => $quotation->quotation_number,
            'source_amount' => '100000.00',
            'rate_percent' => '5.00',
            'pool_amount' => '5000.00',
            'source_snapshot' => [],
            'delivered_at' => now()->subHour(),
            'status' => $status,
        ]);
    }

    private function deliverAndPay(OrderEarningsPool $pool, string $amount = '100000.00'): void
    {
        Order::withoutEvents(fn () => Order::query()->findOrFail($pool->order_id)
            ->update(['status' => Order::STATUS_DELIVERED]));

        $order = Order::query()->findOrFail($pool->order_id);

        $recipient = QuotationRecipient::query()->firstOrCreate(
            ['quotation_id' => $order->quotation_id, 'contact_id' => $order->contact_id],
            [
                'email' => 'actions-test@example.com',
                'access_token' => bin2hex(random_bytes(32)),
                'response_status' => 'accepted',
                'payment_percentage' => 100,
                'payment_amount' => $order->total,
                'amount_paid' => 0,
            ]
        );

        Payment::query()->create([
            'organization_id' => $order->organization_id,
            'quotation_id' => $order->quotation_id,
            'quotation_recipient_id' => $recipient->id,
            'payment_transaction_id' => null,
            'amount' => $amount,
            'payment_method' => 'paystack',
            'reference' => 'TEST-PAY-' . uniqid(),
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    private function contribute(OrderEarningsPool $pool, User $user): void
    {
        OrderEarningsContribution::query()->create([
            'order_earnings_pool_id' => $pool->id,
            'user_id' => $user->id,
            'activity_key' => 'production_execution',
            'points' => '2.0000',
            'source_type' => OrderEarningsContribution::SOURCE_WORKFLOW,
            'source_reference' => 'test:' . $user->id,
            'activity_at' => now()->subHours(3),
            'user_active_at_activity' => true,
        ]);
    }

    private function contributionPayload(User $staff, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $staff->id,
            'activity_key' => 'production_execution',
            'activity_at' => now()->subHours(3)->format('Y-m-d\TH:i'),
            'reason' => 'Sewed the uniforms; not captured in the plan.',
        ], $overrides);
    }

    public function test_guests_are_redirected(): void
    {
        $pool = $this->pool();

        $this->post(route('earnings.pools.contributions.store', $pool))->assertRedirect(route('login'));
        $this->post(route('earnings.pools.finalize', $pool))->assertRedirect(route('login'));
    }

    public function test_viewer_without_permissions_is_forbidden_and_sees_no_buttons(): void
    {
        $viewer = $this->userWith(['earnings.pools.view']);
        $pool = $this->pool();
        $staff = User::factory()->create(['is_active' => true]);
        $this->weight();

        $this->actingAs($viewer)
            ->post(route('earnings.pools.contributions.store', $pool), $this->contributionPayload($staff))
            ->assertForbidden();
        $this->actingAs($viewer)->post(route('earnings.pools.finalize', $pool))->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('earnings.pools.show', $pool))
            ->assertOk()
            ->assertDontSee('Add contribution')
            ->assertDontSee('Finalize pool');

        $this->assertSame(0, OrderEarningsContribution::query()->count());
    }

    public function test_finance_sees_both_buttons_and_the_staff_and_activity_pickers(): void
    {
        $this->weight();
        $this->weight('management_oversight', '1.0000');
        $pool = $this->pool();
        User::factory()->create(['is_active' => true, 'name' => 'Pickable Pat']);
        User::factory()->create(['is_active' => false, 'name' => 'Dormant Dee']);

        $this->actingAs($this->finance())
            ->get(route('earnings.pools.show', $pool))
            ->assertOk()
            ->assertSee('Add contribution')
            ->assertSee('Finalize pool')
            ->assertSee('Pickable Pat')
            ->assertDontSee('Dormant Dee')
            ->assertSee('Production execution')
            ->assertDontSee('Management oversight');
    }

    public function test_finance_records_a_manual_contribution(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = User::factory()->create(['is_active' => true]);

        $this->actingAs($this->finance())
            ->post(route('earnings.pools.contributions.store', $pool), $this->contributionPayload($staff))
            ->assertRedirect(route('earnings.pools.show', $pool))
            ->assertSessionHas('success');

        $row = OrderEarningsContribution::query()->firstOrFail();
        $this->assertSame($staff->id, $row->user_id);
        $this->assertSame('manual', $row->source_type);
        $this->assertSame($this->finance()->id, $row->assigned_by);
    }

    public function test_invalid_contributions_are_rejected_with_errors(): void
    {
        $this->weight();
        $this->weight('management_oversight', '1.0000');
        $pool = $this->pool();
        $staff = User::factory()->create(['is_active' => true]);
        $url = route('earnings.pools.contributions.store', $pool);
        $back = route('earnings.pools.show', $pool);

        $this->actingAs($this->finance())->from($back)
            ->post($url, $this->contributionPayload($staff, ['reason' => 'short']))
            ->assertRedirect($back)->assertSessionHasErrors('reason');

        $this->actingAs($this->finance())->from($back)
            ->post($url, $this->contributionPayload($staff, ['activity_key' => 'management_oversight']))
            ->assertRedirect($back)->assertSessionHasErrors('activity_key');

        $this->actingAs($this->finance())->from($back)
            ->post($url, $this->contributionPayload($staff, ['activity_at' => now()->addDay()->format('Y-m-d\TH:i')]))
            ->assertRedirect($back)->assertSessionHasErrors('activity_at');

        $this->assertSame(0, OrderEarningsContribution::query()->count());
    }

    public function test_duplicate_manual_contribution_is_rejected(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = User::factory()->create(['is_active' => true]);
        $url = route('earnings.pools.contributions.store', $pool);
        $back = route('earnings.pools.show', $pool);

        $this->actingAs($this->finance())->post($url, $this->contributionPayload($staff));
        $this->actingAs($this->finance())->from($back)
            ->post($url, $this->contributionPayload($staff))
            ->assertRedirect($back)->assertSessionHasErrors('activity_key');

        $this->assertSame(1, OrderEarningsContribution::query()->count());
    }

    public function test_finalized_pool_rejects_new_contributions_and_hides_actions(): void
    {
        $this->weight();
        $pool = $this->pool(OrderEarningsPool::STATUS_FINALIZED);
        $staff = User::factory()->create(['is_active' => true]);

        $this->actingAs($this->finance())
            ->post(route('earnings.pools.contributions.store', $pool), $this->contributionPayload($staff))
            ->assertForbidden();
        $this->actingAs($this->finance())
            ->post(route('earnings.pools.finalize', $pool))
            ->assertForbidden();

        $this->actingAs($this->finance())
            ->get(route('earnings.pools.show', $pool))
            ->assertOk()
            ->assertDontSee('Add contribution')
            ->assertDontSee('Finalize pool');
    }

    public function test_finalize_credits_wallets_and_finalizes_the_pool(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = User::factory()->create(['is_active' => true]);
        $this->contribute($pool, $staff);
        $this->deliverAndPay($pool);

        $this->actingAs($this->finance())
            ->post(route('earnings.pools.finalize', $pool))
            ->assertRedirect(route('earnings.pools.show', $pool))
            ->assertSessionHas('success');

        $this->assertSame(OrderEarningsPool::STATUS_FINALIZED, $pool->fresh()->status);
        $this->assertSame('5000.00', StaffWalletEntry::balanceFor($staff));
    }

    public function test_finalize_is_refused_with_a_message_when_unpaid(): void
    {
        $this->weight();
        $pool = $this->pool();
        $this->contribute($pool, User::factory()->create(['is_active' => true]));
        $this->deliverAndPay($pool, '66720.00');
        $back = route('earnings.pools.show', $pool);

        $this->actingAs($this->finance())->from($back)
            ->post(route('earnings.pools.finalize', $pool))
            ->assertRedirect($back)
            ->assertSessionHasErrors('payment');

        $this->assertSame(OrderEarningsPool::STATUS_PENDING, $pool->fresh()->status);
        $this->assertSame(0, StaffWalletEntry::query()->count());
    }

    public function test_finalize_holds_a_pool_with_no_contributions_and_explains_why(): void
    {
        $pool = $this->pool();
        $this->deliverAndPay($pool);

        $response = $this->actingAs($this->finance())
            ->post(route('earnings.pools.finalize', $pool))
            ->assertRedirect(route('earnings.pools.show', $pool));

        $this->assertSame(OrderEarningsPool::STATUS_HELD, $pool->fresh()->status);
        $this->assertStringContainsString('no contributions', session('pool_held'));
        $this->followRedirects($response)->assertSee('The pool was held, not finalized');
    }
}
