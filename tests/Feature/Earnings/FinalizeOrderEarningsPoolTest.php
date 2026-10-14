<?php

namespace Tests\Feature\Earnings;

use App\Actions\Earnings\FinalizeOrderEarningsPool;
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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class FinalizeOrderEarningsPoolTest extends TestCase
{
    use RefreshDatabase;

    private ?User $finalizer = null;

    private function userWithPermissions(array $permissions, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);

        $role = Role::factory()->create([
            'name' => 'Finalize Test Role ' . uniqid(),
            'slug' => 'finalize-test-' . uniqid(),
        ]);

        $role->permissions()->createMany(
            collect($permissions)
                ->map(fn (string $slug) => ['name' => $slug, 'slug' => $slug])
                ->all()
        );

        $user->roles()->attach($role);

        return $user;
    }

    private function finalizer(): User
    {
        return $this->finalizer ??= $this->userWithPermissions(['earnings.pools.finalize']);
    }

    private function staff(bool $active = true): User
    {
        return User::factory()->create(['is_active' => $active]);
    }

    /**
     * Pool for a ready order with total 100000. Use deliver() to mark delivered.
     */
    private function pool(string $poolAmount = '5000.00'): OrderEarningsPool
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
            'notes' => 'Finalize test quotation.',
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
            'notes' => 'Finalize test order.',
        ]);

        return OrderEarningsPool::query()->create([
            'order_id' => $order->id,
            'quotation_id' => $quotation->id,
            'quotation_number' => $quotation->quotation_number,
            'source_amount' => '100000.00',
            'rate_percent' => '5.00',
            'pool_amount' => $poolAmount,
            'source_snapshot' => [],
            'delivered_at' => now()->subHour(),
            'status' => OrderEarningsPool::STATUS_PENDING,
        ]);
    }

    private function deliver(OrderEarningsPool $pool): void
    {
        Order::withoutEvents(function () use ($pool) {
            Order::query()->findOrFail($pool->order_id)
                ->update(['status' => Order::STATUS_DELIVERED]);
        });
    }

    private function pay(OrderEarningsPool $pool, string $amount, string $status = 'completed'): void
    {
        $order = Order::query()->findOrFail($pool->order_id);

        $recipient = QuotationRecipient::query()->firstOrCreate(
            [
                'quotation_id' => $order->quotation_id,
                'contact_id' => $order->contact_id,
            ],
            [
                'email' => 'finalize-test@example.com',
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
            'status' => $status,
            'paid_at' => now(),
        ]);
    }

    private function contribute(
        OrderEarningsPool $pool,
        User $user,
        string $activity,
        string $points
    ): OrderEarningsContribution {
        return OrderEarningsContribution::query()->create([
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

    private function finalize(OrderEarningsPool $pool, ?User $actor = null): OrderEarningsPool
    {
        return app(FinalizeOrderEarningsPool::class)
            ->execute($pool, $actor ?? $this->finalizer());
    }

    /** Delivered, fully paid pool with two contributors (2 and 1 points). */
    private function readyPool(): array
    {
        $pool = $this->pool('5000.00');
        $a = $this->staff();
        $b = $this->staff();
        $this->contribute($pool, $a, 'production', '2.0000');
        $this->contribute($pool, $b, 'coordination', '1.0000');
        $this->deliver($pool);
        $this->pay($pool, '100000.00');

        return [$pool, $a, $b];
    }

    public function test_finalization_creates_allocations_that_sum_to_the_pool(): void
    {
        [$pool, $a, $b] = $this->readyPool();
        $actor = $this->finalizer();

        $result = $this->finalize($pool, $actor);

        $this->assertSame(OrderEarningsPool::STATUS_FINALIZED, $result->status);
        $this->assertSame($actor->id, $result->finalized_by);
        $this->assertNotNull($result->finalized_at);
        $this->assertNull($result->held_at);

        $amounts = OrderEarningsAllocation::query()
            ->where('order_earnings_pool_id', $pool->id)
            ->pluck('amount', 'user_id')
            ->all();

        $this->assertSame('3333.33', $amounts[$a->id]);
        $this->assertSame('1666.67', $amounts[$b->id]);
        $this->assertEqualsWithDelta(5000.00, array_sum(array_map('floatval', $amounts)), 0.001);
    }

    public function test_snapshot_records_payment_check_and_previous_status(): void
    {
        [$pool] = $this->readyPool();

        $result = $this->finalize($pool);
        $snapshot = $result->finalization_snapshot;

        $this->assertSame('pending', $snapshot['previous_status']);
        $this->assertSame('100000.00', $snapshot['payment_check']['order_total']);
        $this->assertSame('100000.00', $snapshot['payment_check']['paid_total']);
        $this->assertCount(1, $snapshot['payment_check']['payment_ids']);
        $this->assertFalse($snapshot['finalizer_is_beneficiary']);
        $this->assertSame('allocatable', $snapshot['calculation']['verdict']);
    }

    public function test_snapshot_flags_finalizer_who_is_a_beneficiary(): void
    {
        $pool = $this->pool('5000.00');
        $actor = $this->finalizer();
        $this->contribute($pool, $actor, 'production', '2.0000');
        $this->deliver($pool);
        $this->pay($pool, '100000.00');

        $result = $this->finalize($pool, $actor);

        $this->assertSame(OrderEarningsPool::STATUS_FINALIZED, $result->status);
        $this->assertTrue($result->finalization_snapshot['finalizer_is_beneficiary']);
    }

    public function test_actor_without_permission_is_denied_and_nothing_is_written(): void
    {
        [$pool] = $this->readyPool();
        $actor = $this->userWithPermissions(['orders.view']);

        try {
            $this->finalize($pool, $actor);
            $this->fail('Expected AuthorizationException.');
        } catch (AuthorizationException) {
            // expected
        }

        $this->assertSame(OrderEarningsPool::STATUS_PENDING, $pool->fresh()->status);
        $this->assertSame(0, OrderEarningsAllocation::query()->count());
    }

    public function test_inactive_actor_with_permission_is_denied(): void
    {
        [$pool] = $this->readyPool();
        $actor = $this->userWithPermissions(['earnings.pools.finalize'], false);

        $this->expectException(AuthorizationException::class);

        $this->finalize($pool, $actor);
    }

    public function test_already_finalized_pool_cannot_be_finalized_again(): void
    {
        [$pool] = $this->readyPool();
        $this->finalize($pool);

        try {
            $this->finalize($pool);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('pool', $e->errors());
        }

        $this->assertSame(2, OrderEarningsAllocation::query()->count());
    }

    public function test_order_must_be_delivered(): void
    {
        $pool = $this->pool();
        $this->contribute($pool, $this->staff(), 'production', '2.0000');
        $this->pay($pool, '100000.00');

        try {
            $this->finalize($pool);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('order', $e->errors());
        }

        $this->assertSame(OrderEarningsPool::STATUS_PENDING, $pool->fresh()->status);
    }

    public function test_underpaid_order_cannot_be_finalized(): void
    {
        $pool = $this->pool();
        $this->contribute($pool, $this->staff(), 'production', '2.0000');
        $this->deliver($pool);
        $this->pay($pool, '66720.00');

        try {
            $this->finalize($pool);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payment', $e->errors());
        }

        $this->assertSame(0, OrderEarningsAllocation::query()->count());
    }

    public function test_payments_that_are_not_completed_do_not_count(): void
    {
        $pool = $this->pool();
        $this->contribute($pool, $this->staff(), 'production', '2.0000');
        $this->deliver($pool);
        $this->pay($pool, '100000.00', 'pending');

        $this->expectException(ValidationException::class);

        $this->finalize($pool);
    }

    public function test_payment_within_one_cent_is_accepted(): void
    {
        $pool = $this->pool();
        $this->contribute($pool, $this->staff(), 'production', '2.0000');
        $this->deliver($pool);
        $this->pay($pool, '99999.99');

        $result = $this->finalize($pool);

        $this->assertSame(OrderEarningsPool::STATUS_FINALIZED, $result->status);
    }

    public function test_pool_without_contributions_is_held(): void
    {
        $pool = $this->pool();
        $this->deliver($pool);
        $this->pay($pool, '100000.00');

        $result = $this->finalize($pool);

        $this->assertSame(OrderEarningsPool::STATUS_HELD, $result->status);
        $this->assertNotNull($result->held_at);
        $this->assertContains('no_contributions', $result->hold_reasons['reasons']);
        $this->assertSame($this->finalizer()->id, $result->hold_reasons['checked_by']);
        $this->assertNull($result->finalized_at);
        $this->assertSame(0, OrderEarningsAllocation::query()->count());
    }

    public function test_pool_with_inactive_contributor_is_held(): void
    {
        [$pool, $a] = $this->readyPool();
        $a->update(['is_active' => false]);

        $result = $this->finalize($pool);

        $this->assertSame(OrderEarningsPool::STATUS_HELD, $result->status);
        $this->assertContains('inactive_contributors', $result->hold_reasons['reasons']);
        $this->assertContains($a->id, $result->hold_reasons['inactive_user_ids']);
        $this->assertSame(0, OrderEarningsAllocation::query()->count());
    }

    public function test_held_pool_can_be_finalized_once_the_cause_is_resolved(): void
    {
        $pool = $this->pool('5000.00');
        $this->deliver($pool);
        $this->pay($pool, '100000.00');

        $held = $this->finalize($pool);
        $this->assertSame(OrderEarningsPool::STATUS_HELD, $held->status);

        $this->contribute($pool, $this->staff(), 'production', '2.0000');

        $result = $this->finalize($pool);

        $this->assertSame(OrderEarningsPool::STATUS_FINALIZED, $result->status);
        $this->assertNull($result->held_at);
        $this->assertNull($result->hold_reasons);
        $this->assertSame('held', $result->finalization_snapshot['previous_status']);
        $this->assertSame(1, OrderEarningsAllocation::query()->count());
    }

    public function test_finalized_allocations_cannot_be_modified_or_deleted(): void
    {
        [$pool] = $this->readyPool();
        $this->finalize($pool);

        $allocation = OrderEarningsAllocation::query()->firstOrFail();

        try {
            $allocation->update(['amount' => '1.00']);
            $this->fail('Expected LogicException on update.');
        } catch (LogicException) {
            // expected
        }

        try {
            $allocation->delete();
            $this->fail('Expected LogicException on delete.');
        } catch (LogicException) {
            // expected
        }

        $this->assertSame(2, OrderEarningsAllocation::query()->count());
    }
}
