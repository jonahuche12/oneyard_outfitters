<?php

namespace Tests\Feature\Earnings;

use App\Actions\Earnings\CalculateOrderEarningsAllocation;
use App\Models\Contact;
use App\Models\EarningsActivityWeight;
use App\Models\Order;
use App\Models\OrderEarningsContribution;
use App\Models\OrderEarningsPool;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class CalculateOrderEarningsAllocationTest extends TestCase
{
    use RefreshDatabase;

    private ?Role $superAdminRole = null;

    private function staff(bool $active = true): User
    {
        return User::factory()->create(['is_active' => $active]);
    }

    private function superAdmin(bool $active = true): User
    {
        $this->superAdminRole ??= Role::factory()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
        ]);

        $user = $this->staff($active);
        $user->roles()->attach($this->superAdminRole);

        return $user;
    }

    private function weight(string $key, string $points, bool $enabled = true): void
    {
        EarningsActivityWeight::query()->create([
            'activity_key' => $key,
            'label' => ucfirst($key),
            'points' => $points,
            'is_enabled' => $enabled,
        ]);
    }

    private function pool(
        string $poolAmount = '5000.00',
        string $status = OrderEarningsPool::STATUS_PENDING
    ): OrderEarningsPool {
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
            'notes' => 'Allocation test quotation.',
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
            'notes' => 'Allocation test order.',
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
            'status' => $status,
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

    private function calculate(OrderEarningsPool $pool): array
    {
        return app(CalculateOrderEarningsAllocation::class)->execute($pool);
    }

    private function amounts(array $result): array
    {
        return collect($result['allocations'])
            ->mapWithKeys(fn ($a) => [$a['user_id'] => $a['amount']])
            ->all();
    }

    public function test_single_contributor_receives_the_whole_pool(): void
    {
        $pool = $this->pool('5000.00');
        $staff = $this->staff();
        $this->contribute($pool, $staff, 'production', '2.0000');

        $result = $this->calculate($pool);

        $this->assertSame('allocatable', $result['verdict']);
        $this->assertSame([$staff->id => '5000.00'], $this->amounts($result));
    }

    public function test_different_activities_are_proportional_and_sum_exactly(): void
    {
        $pool = $this->pool('5000.00');
        $a = $this->staff();
        $b = $this->staff();
        $this->contribute($pool, $a, 'production', '2.0000');
        $this->contribute($pool, $b, 'quality_control', '1.0000');

        $amounts = $this->amounts($this->calculate($pool));

        // 500000 cents: floors 333333 / 166666, the leftover cent goes to
        // the larger remainder (b).
        $this->assertSame('3333.33', $amounts[$a->id]);
        $this->assertSame('1666.67', $amounts[$b->id]);
    }

    public function test_staff_sharing_an_activity_split_its_points_equally(): void
    {
        $pool = $this->pool('5000.00');
        $a = $this->staff();
        $b = $this->staff();
        $c = $this->staff();
        $this->contribute($pool, $a, 'production', '2.0000');
        $this->contribute($pool, $b, 'production', '2.0000');
        $this->contribute($pool, $c, 'quality_control', '2.0000');

        $amounts = $this->amounts($this->calculate($pool));

        // Effective points: a = 1, b = 1, c = 2 (total 4).
        $this->assertSame('1250.00', $amounts[$a->id]);
        $this->assertSame('1250.00', $amounts[$b->id]);
        $this->assertSame('2500.00', $amounts[$c->id]);
    }

    public function test_one_staff_member_accumulates_points_across_activities(): void
    {
        $pool = $this->pool('4000.00');
        $a = $this->staff();
        $b = $this->staff();
        $this->contribute($pool, $a, 'production', '1.0000');
        $this->contribute($pool, $a, 'quality_control', '1.0000');
        $this->contribute($pool, $b, 'delivery', '2.0000');

        $result = $this->calculate($pool);
        $amounts = $this->amounts($result);

        $this->assertCount(2, $result['allocations']);
        $this->assertSame('2000.00', $amounts[$a->id]);
        $this->assertSame('2000.00', $amounts[$b->id]);
    }

    public function test_super_admin_receives_management_oversight(): void
    {
        $this->weight('management_oversight', '1.0000');
        $pool = $this->pool('4000.00');
        $admin = $this->superAdmin();
        $staff = $this->staff();
        $this->contribute($pool, $staff, 'production', '3.0000');

        $amounts = $this->amounts($this->calculate($pool));

        $this->assertSame('3000.00', $amounts[$staff->id]);
        $this->assertSame('1000.00', $amounts[$admin->id]);
    }

    public function test_oversight_is_split_equally_among_active_super_admins(): void
    {
        $this->weight('management_oversight', '2.0000');
        $pool = $this->pool('4000.00');
        $adminA = $this->superAdmin();
        $adminB = $this->superAdmin();
        $staff = $this->staff();
        $this->contribute($pool, $staff, 'production', '2.0000');

        $amounts = $this->amounts($this->calculate($pool));

        // Oversight 2 points split in two: 1 each; staff 2; total 4.
        $this->assertSame('1000.00', $amounts[$adminA->id]);
        $this->assertSame('1000.00', $amounts[$adminB->id]);
        $this->assertSame('2000.00', $amounts[$staff->id]);
    }

    public function test_inactive_super_admin_receives_no_oversight(): void
    {
        $this->weight('management_oversight', '1.0000');
        $pool = $this->pool('4000.00');
        $inactiveAdmin = $this->superAdmin(false);
        $staff = $this->staff();
        $this->contribute($pool, $staff, 'production', '3.0000');

        $amounts = $this->amounts($this->calculate($pool));

        $this->assertArrayNotHasKey($inactiveAdmin->id, $amounts);
        $this->assertSame('4000.00', $amounts[$staff->id]);
    }

    public function test_missing_or_disabled_oversight_weight_gives_no_oversight(): void
    {
        $admin = $this->superAdmin();
        $staff = $this->staff();

        $pool = $this->pool('4000.00');
        $this->contribute($pool, $staff, 'production', '3.0000');

        $this->assertArrayNotHasKey($admin->id, $this->amounts($this->calculate($pool)));

        $this->weight('management_oversight', '1.0000', false);

        $this->assertArrayNotHasKey($admin->id, $this->amounts($this->calculate($pool)));
    }

    public function test_super_admin_who_also_contributed_is_aggregated_once(): void
    {
        $this->weight('management_oversight', '1.0000');
        $pool = $this->pool('4000.00');
        $admin = $this->superAdmin();
        $this->contribute($pool, $admin, 'production', '3.0000');

        $result = $this->calculate($pool);

        $this->assertCount(1, $result['allocations']);
        $this->assertSame('4000.00', $this->amounts($result)[$admin->id]);
    }

    public function test_pool_without_contributions_is_held_even_with_a_super_admin(): void
    {
        $this->weight('management_oversight', '1.0000');
        $this->superAdmin();
        $pool = $this->pool();

        $result = $this->calculate($pool);

        $this->assertSame('held', $result['verdict']);
        $this->assertSame(['no_contributions'], $result['hold_reasons']);
        $this->assertSame([], $result['allocations']);
    }

    public function test_pool_is_held_when_a_contributor_is_now_inactive(): void
    {
        $pool = $this->pool();
        $active = $this->staff();
        $leaver = $this->staff();
        $this->contribute($pool, $active, 'production', '2.0000');
        $this->contribute($pool, $leaver, 'quality_control', '1.0000');

        $leaver->update(['is_active' => false]);

        $result = $this->calculate($pool);

        $this->assertSame('held', $result['verdict']);
        $this->assertSame(['inactive_contributors'], $result['hold_reasons']);
        $this->assertSame([$leaver->id], $result['inactive_user_ids']);
        $this->assertSame([], $result['allocations']);
    }

    public function test_pool_is_held_when_all_points_are_zero(): void
    {
        $pool = $this->pool();
        $staff = $this->staff();
        $this->contribute($pool, $staff, 'production', '0.0000');

        $result = $this->calculate($pool);

        $this->assertSame('held', $result['verdict']);
        $this->assertSame(['zero_points'], $result['hold_reasons']);
    }

    public function test_leftover_cents_go_to_the_lowest_user_ids_on_ties(): void
    {
        $pool = $this->pool('33.33');
        $staff = collect(range(1, 7))->map(fn () => $this->staff())->sortBy('id')->values();

        foreach ($staff as $member) {
            $this->contribute($pool, $member, 'production', '1.0000');
        }

        $amounts = $this->amounts($this->calculate($pool));

        // 3333 cents / 7 = 476 remainder 1: the single leftover cent goes
        // to the lowest user id.
        $this->assertSame('4.77', $amounts[$staff[0]->id]);

        foreach ($staff->slice(1) as $member) {
            $this->assertSame('4.76', $amounts[$member->id]);
        }

        $this->assertSame(
            3333,
            (int) collect($amounts)->sum(fn ($a) => (int) str_replace('.', '', $a))
        );
    }

    public function test_allocations_always_sum_to_the_pool(): void
    {
        $this->weight('management_oversight', '1.5000');
        $this->superAdmin();
        $this->superAdmin();

        foreach (['0.01', '0.07', '1234.56', '99999.99'] as $poolAmount) {
            $pool = $this->pool($poolAmount);

            $this->contribute($pool, $this->staff(), 'production', '2.3333');
            $this->contribute($pool, $this->staff(), 'production', '2.3333');
            $this->contribute($pool, $this->staff(), 'quality_control', '1.7777');
            $this->contribute($pool, $this->staff(), 'delivery', '0.3333');

            $result = $this->calculate($pool);

            $this->assertSame('allocatable', $result['verdict']);
            $this->assertSame(
                (int) str_replace('.', '', $poolAmount),
                (int) collect($result['allocations'])
                    ->sum(fn ($a) => (int) str_replace('.', '', $a['amount'])),
                "Allocations for pool {$poolAmount}"
            );
        }
    }

    public function test_zero_value_pool_allocates_zero_to_everyone(): void
    {
        $pool = $this->pool('0.00');
        $staff = $this->staff();
        $this->contribute($pool, $staff, 'production', '2.0000');

        $result = $this->calculate($pool);

        $this->assertSame('allocatable', $result['verdict']);
        $this->assertSame([$staff->id => '0.00'], $this->amounts($result));
    }

    public function test_calculation_is_deterministic_and_writes_nothing(): void
    {
        $this->weight('management_oversight', '1.0000');
        $this->superAdmin();
        $pool = $this->pool('1234.57');
        $this->contribute($pool, $this->staff(), 'production', '2.0000');
        $this->contribute($pool, $this->staff(), 'delivery', '3.0000');

        $contributionsBefore = OrderEarningsContribution::count();

        $first = $this->calculate($pool);
        $second = $this->calculate($pool);

        $this->assertSame($first, $second);
        $this->assertSame($contributionsBefore, OrderEarningsContribution::count());
        $this->assertSame(
            OrderEarningsPool::STATUS_PENDING,
            $pool->fresh()->status
        );
    }

    public function test_finalized_pool_cannot_be_recalculated(): void
    {
        $pool = $this->pool('5000.00', OrderEarningsPool::STATUS_FINALIZED);
        $this->contribute($pool, $this->staff(), 'production', '2.0000');

        $this->expectException(LogicException::class);

        $this->calculate($pool);
    }

    public function test_held_pool_can_be_recalculated(): void
    {
        $pool = $this->pool('5000.00', OrderEarningsPool::STATUS_HELD);
        $staff = $this->staff();
        $this->contribute($pool, $staff, 'production', '2.0000');

        $result = $this->calculate($pool);

        $this->assertSame('allocatable', $result['verdict']);
        $this->assertSame([$staff->id => '5000.00'], $this->amounts($result));
    }
}
