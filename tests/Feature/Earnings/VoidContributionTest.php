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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VoidContributionTest extends TestCase
{
    use RefreshDatabase;

    private ?User $finance = null;

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::factory()->create(['name' => 'Void Role ' . uniqid(), 'slug' => 'void-role-' . uniqid()]);
        $role->permissions()->createMany(collect($permissions)->map(fn ($s) => ['name' => $s, 'slug' => $s])->all());
        $user->roles()->attach($role);

        return $user;
    }

    private function finance(): User
    {
        return $this->finance ??= $this->userWith(['earnings.pools.view', 'earnings.contributions.assign', 'earnings.pools.finalize']);
    }

    private function weights(): void
    {
        foreach (['production_execution' => '2.0000', 'order_coordination' => '3.0000'] as $k => $p) {
            EarningsActivityWeight::query()->create(['activity_key' => $k, 'label' => ucfirst($k), 'points' => $p, 'is_enabled' => true]);
        }
    }

    private function pool(string $status = OrderEarningsPool::STATUS_PENDING): OrderEarningsPool
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create(['organization_id' => $organization->id, 'is_primary' => true, 'is_active' => true]);
        $creator = User::factory()->create(['is_active' => true]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(), 'organization_id' => $organization->id, 'contact_id' => $contact->id,
            'created_by' => $creator->id, 'quotation_date' => now()->toDateString(), 'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30, 'status' => Quotation::STATUS_ACCEPTED, 'subtotal' => 100000, 'discount' => 0,
            'additional_charges' => 0, 'total' => 100000, 'terms' => null, 'notes' => 'Void test quotation.',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'organization_id' => $organization->id, 'quotation_id' => $quotation->id,
            'contact_id' => $contact->id, 'order_date' => now()->toDateString(), 'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(), 'status' => Order::STATUS_READY,
            'subtotal' => 100000, 'discount' => 0, 'additional_charges' => 0, 'total' => 100000, 'terms' => null, 'notes' => 'Void test order.',
        ]);

        return OrderEarningsPool::query()->create([
            'order_id' => $order->id, 'quotation_id' => $quotation->id, 'quotation_number' => $quotation->quotation_number,
            'source_amount' => '100000.00', 'rate_percent' => '5.00', 'pool_amount' => '5000.00', 'source_snapshot' => [],
            'delivered_at' => now()->subHour(), 'status' => $status,
        ]);
    }

    private function deliverAndPay(OrderEarningsPool $pool): void
    {
        Order::withoutEvents(fn () => Order::query()->findOrFail($pool->order_id)->update(['status' => Order::STATUS_DELIVERED]));
        $order = Order::query()->findOrFail($pool->order_id);
        $recipient = QuotationRecipient::query()->create([
            'quotation_id' => $order->quotation_id, 'contact_id' => $order->contact_id, 'email' => 'void-test@example.com',
            'access_token' => bin2hex(random_bytes(32)), 'response_status' => 'accepted', 'payment_percentage' => 100,
            'payment_amount' => $order->total, 'amount_paid' => 0,
        ]);
        Payment::query()->create([
            'organization_id' => $order->organization_id, 'quotation_id' => $order->quotation_id,
            'quotation_recipient_id' => $recipient->id, 'payment_transaction_id' => null, 'amount' => '100000.00',
            'payment_method' => 'paystack', 'reference' => 'TEST-PAY-' . uniqid(), 'status' => 'completed', 'paid_at' => now(),
        ]);
    }

    private function contribute(OrderEarningsPool $pool, User $user, string $activity = 'production_execution', string $source = 'manual'): OrderEarningsContribution
    {
        return OrderEarningsContribution::query()->create([
            'order_earnings_pool_id' => $pool->id, 'user_id' => $user->id, 'activity_key' => $activity, 'points' => '2.0000',
            'source_type' => $source, 'source_reference' => $source === 'workflow' ? 'test:' . $user->id : null,
            'reason' => $source === 'manual' ? 'Entered for the void tests.' : null,
            'activity_at' => now()->subHours(3), 'user_active_at_activity' => true,
        ]);
    }

    private function voidUrl(OrderEarningsPool $pool, OrderEarningsContribution $c): string
    {
        return route('earnings.pools.contributions.void', [$pool, $c]);
    }

    public function test_finance_voids_a_contribution_and_the_row_is_kept(): void
    {
        $pool = $this->pool();
        $c = $this->contribute($pool, User::factory()->create(['is_active' => true]));

        $this->actingAs($this->finance())
            ->post($this->voidUrl($pool, $c), ['void_reason' => 'Entered against the wrong order.'])
            ->assertRedirect(route('earnings.pools.show', $pool))
            ->assertSessionHas('success');

        $c->refresh();
        $this->assertNotNull($c->voided_at);
        $this->assertSame($this->finance()->id, $c->voided_by);
        $this->assertSame('Entered against the wrong order.', $c->void_reason);
        $this->assertNull($c->is_active_slot);
        $this->assertSame(1, OrderEarningsContribution::query()->count());
    }

    public function test_void_requires_permission_a_reason_and_the_right_pool(): void
    {
        $pool = $this->pool();
        $other = $this->pool();
        $c = $this->contribute($pool, User::factory()->create(['is_active' => true]));
        $viewer = $this->userWith(['orders.view']);

        $this->actingAs($viewer)->post($this->voidUrl($pool, $c), ['void_reason' => 'Valid reason given here.'])->assertForbidden();

        $back = route('earnings.pools.show', $pool);
        $this->actingAs($this->finance())->from($back)
            ->post($this->voidUrl($pool, $c), ['void_reason' => 'short'])
            ->assertRedirect($back)->assertSessionHasErrors('void_reason');

        $this->actingAs($this->finance())
            ->post($this->voidUrl($other, $c), ['void_reason' => 'Valid reason given here.'])
            ->assertNotFound();

        $this->assertNull($c->fresh()->voided_at);
    }

    public function test_a_contribution_cannot_be_voided_twice_or_on_a_finalized_pool(): void
    {
        $pool = $this->pool();
        $c = $this->contribute($pool, User::factory()->create(['is_active' => true]));
        $back = route('earnings.pools.show', $pool);

        $this->actingAs($this->finance())->post($this->voidUrl($pool, $c), ['void_reason' => 'Valid reason given here.']);
        $this->actingAs($this->finance())->from($back)
            ->post($this->voidUrl($pool, $c), ['void_reason' => 'Second attempt to void.'])
            ->assertRedirect($back)->assertSessionHasErrors('contribution');

        $final = $this->pool(OrderEarningsPool::STATUS_FINALIZED);
        $fc = $this->contribute($final, User::factory()->create(['is_active' => true]));
        $this->actingAs($this->finance())
            ->post($this->voidUrl($final, $fc), ['void_reason' => 'Valid reason given here.'])
            ->assertForbidden();
        $this->assertNull($fc->fresh()->voided_at);
    }

    public function test_voided_contributions_do_not_count_in_the_preview(): void
    {
        $pool = $this->pool();
        $c = $this->contribute($pool, User::factory()->create(['is_active' => true]));
        $this->actingAs($this->finance())->post($this->voidUrl($pool, $c), ['void_reason' => 'Valid reason given here.']);

        $this->actingAs($this->finance())
            ->get(route('earnings.pools.show', $pool))
            ->assertOk()
            ->assertSee('would be held')
            ->assertSee('no contributions')
            ->assertSee('data-voided-contribution', false);
    }

    public function test_the_same_person_can_be_added_again_after_a_void(): void
    {
        $this->weights();
        $pool = $this->pool();
        $staff = User::factory()->create(['is_active' => true]);
        $c = $this->contribute($pool, $staff);
        $this->actingAs($this->finance())->post($this->voidUrl($pool, $c), ['void_reason' => 'Valid reason given here.']);

        $this->actingAs($this->finance())
            ->post(route('earnings.pools.contributions.store', $pool), [
                'user_id' => $staff->id, 'activity_key' => 'production_execution',
                'activity_at' => now()->subHours(3)->format('Y-m-d\TH:i'), 'reason' => 'Re-entered with the right details.',
            ])->assertSessionHas('success');

        $this->assertSame(2, OrderEarningsContribution::query()->count());
        $this->assertSame(1, OrderEarningsContribution::query()->active()->count());
    }

    public function test_refresh_does_not_recreate_a_voided_workflow_contribution(): void
    {
        $this->weights();
        $pool = $this->pool();
        $coordinator = User::factory()->create(['is_active' => true]);
        DB::table('order_assignments')->insert([
            'order_id' => $pool->order_id, 'user_id' => $coordinator->id, 'assigned_by' => $coordinator->id, 'assigned_at' => now()->subHours(5),
        ]);

        $refresh = route('earnings.pools.refresh-contributions', $pool);
        $this->actingAs($this->finance())->post($refresh);
        $row = OrderEarningsContribution::query()->firstOrFail();
        $this->actingAs($this->finance())->post($this->voidUrl($pool, $row), ['void_reason' => 'Coordinator did not actually work on it.']);

        $this->actingAs($this->finance())->post($refresh);

        $this->assertSame(0, OrderEarningsContribution::query()->active()->count());
        $this->assertSame(1, OrderEarningsContribution::query()->count());
        $this->assertStringContainsString('voided', session('derivation_skipped')[0]);
    }

    public function test_finalization_ignores_voided_contributions(): void
    {
        $pool = $this->pool();
        $kept = User::factory()->create(['is_active' => true]);
        $dropped = User::factory()->create(['is_active' => true]);
        $this->contribute($pool, $kept);
        $voided = $this->contribute($pool, $dropped);
        $this->deliverAndPay($pool);
        $this->actingAs($this->finance())->post($this->voidUrl($pool, $voided), ['void_reason' => 'Wrong person was selected.']);

        $this->actingAs($this->finance())->post(route('earnings.pools.finalize', $pool))->assertSessionHas('success');

        $this->assertSame('5000.00', StaffWalletEntry::balanceFor($kept));
        $this->assertSame('0.00', StaffWalletEntry::balanceFor($dropped));
    }
}
