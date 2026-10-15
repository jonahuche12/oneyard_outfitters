<?php

namespace Tests\Feature\Earnings;

use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderEarningsAllocation;
use App\Models\OrderEarningsContribution;
use App\Models\OrderEarningsPool;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\QuotationRecipient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarningsPoolScreenTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::factory()->create([
            'name' => 'Pool Viewer ' . uniqid(),
            'slug' => 'pool-viewer-' . uniqid(),
        ]);
        $role->permissions()->create(['name' => 'earnings.pools.view', 'slug' => 'earnings.pools.view']);
        $user->roles()->attach($role);

        return $user;
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
            'notes' => 'Screen test quotation.',
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
            'notes' => 'Screen test order.',
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

    private function pay(OrderEarningsPool $pool, string $amount): void
    {
        $order = Order::query()->findOrFail($pool->order_id);

        $recipient = QuotationRecipient::query()->firstOrCreate(
            ['quotation_id' => $order->quotation_id, 'contact_id' => $order->contact_id],
            [
                'email' => 'screen-test@example.com',
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

    private function contribute(OrderEarningsPool $pool, User $user, string $activity, string $points): void
    {
        OrderEarningsContribution::query()->create([
            'order_earnings_pool_id' => $pool->id,
            'user_id' => $user->id,
            'activity_key' => $activity,
            'points' => $points,
            'source_type' => OrderEarningsContribution::SOURCE_WORKFLOW,
            'source_reference' => $activity . ':' . $user->id,
            'activity_at' => now()->subHours(2),
            'user_active_at_activity' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('earnings.pools.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $pool = $this->pool();

        $this->actingAs($user)->get(route('earnings.pools.index'))->assertForbidden();
        $this->actingAs($user)->get(route('earnings.pools.show', $pool))->assertForbidden();
    }

    public function test_list_shows_pools_with_status_and_payment_state(): void
    {
        $viewer = $this->viewer();
        $paid = $this->pool();
        $this->pay($paid, '100000.00');
        $unpaid = $this->pool(OrderEarningsPool::STATUS_HELD);

        $this->actingAs($viewer)
            ->get(route('earnings.pools.index'))
            ->assertOk()
            ->assertSee($paid->order->order_number)
            ->assertSee($unpaid->order->order_number)
            ->assertSee('Paid')
            ->assertSee('Outstanding')
            ->assertSee('Held');
    }

    public function test_list_filters_by_status_and_searches_by_order_number(): void
    {
        $viewer = $this->viewer();
        $pending = $this->pool();
        $held = $this->pool(OrderEarningsPool::STATUS_HELD);

        $this->actingAs($viewer)
            ->get(route('earnings.pools.index', ['status' => 'held']))
            ->assertOk()
            ->assertSee($held->order->order_number)
            ->assertDontSee($pending->order->order_number);

        $this->actingAs($viewer)
            ->get(route('earnings.pools.index', ['q' => $pending->order->order_number]))
            ->assertOk()
            ->assertSee($pending->order->order_number)
            ->assertDontSee($held->order->order_number);
    }

    public function test_pending_pool_shows_contributions_and_allocation_preview(): void
    {
        $viewer = $this->viewer();
        $pool = $this->pool();
        $this->pay($pool, '100000.00');
        $staff = User::factory()->create(['is_active' => true, 'name' => 'Ada Contributor']);
        $this->contribute($pool, $staff, 'production', '2.0000');

        $this->actingAs($viewer)
            ->get(route('earnings.pools.show', $pool))
            ->assertOk()
            ->assertSee('Ada Contributor')
            ->assertSee('Allocation preview')
            ->assertSee('₦5,000.00')
            ->assertDontSee('Cannot finalize');
    }

    public function test_pool_without_contributions_previews_a_hold(): void
    {
        $viewer = $this->viewer();
        $pool = $this->pool();

        $this->actingAs($viewer)
            ->get(route('earnings.pools.show', $pool))
            ->assertOk()
            ->assertSee('would be held')
            ->assertSee('no contributions');
    }

    public function test_outstanding_balance_shows_a_finalize_warning(): void
    {
        $viewer = $this->viewer();
        $pool = $this->pool();
        $this->pay($pool, '66720.00');

        $this->actingAs($viewer)
            ->get(route('earnings.pools.show', $pool))
            ->assertOk()
            ->assertSee('Cannot finalize')
            ->assertSee('₦33,280.00');
    }

    public function test_finalized_pool_shows_allocations_not_a_preview(): void
    {
        $viewer = $this->viewer();
        $pool = $this->pool(OrderEarningsPool::STATUS_FINALIZED);
        $staff = User::factory()->create(['is_active' => true, 'name' => 'Bola Earner']);

        OrderEarningsAllocation::query()->create([
            'order_earnings_pool_id' => $pool->id,
            'user_id' => $staff->id,
            'amount' => '5000.00',
            'weight' => 20000,
            'weight_scale' => 10000,
            'effective_points' => '2.0000',
        ]);

        $this->actingAs($viewer)
            ->get(route('earnings.pools.show', $pool))
            ->assertOk()
            ->assertSee('Finalized allocations')
            ->assertSee('Bola Earner')
            ->assertDontSee('Allocation preview');
    }

    public function test_nav_link_is_visible_only_with_permission(): void
    {
        $viewer = $this->viewer();
        $plain = User::factory()->create(['is_active' => true]);

        $this->actingAs($viewer)->get(route('dashboard'))
            ->assertOk()->assertSee(route('earnings.pools.index'), false);

        $this->actingAs($plain)->get(route('dashboard'))
            ->assertOk()->assertDontSee(route('earnings.pools.index'), false);
    }
}
