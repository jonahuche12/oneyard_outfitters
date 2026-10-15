<?php

namespace Tests\Feature\Earnings;

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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefreshPoolContributionsTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::factory()->create([
            'name' => 'Refresh Role ' . uniqid(),
            'slug' => 'refresh-role-' . uniqid(),
        ]);
        $role->permissions()->createMany(
            collect($permissions)->map(fn ($s) => ['name' => $s, 'slug' => $s])->all()
        );
        $user->roles()->attach($role);

        return $user;
    }

    private ?User $finance = null;

    private function finance(): User
    {
        return $this->finance ??= $this->userWith(['earnings.pools.view', 'earnings.contributions.assign']);
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
            'notes' => 'Refresh test quotation.',
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
            'notes' => 'Refresh test order.',
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

    private function weight(): void
    {
        EarningsActivityWeight::query()->create([
            'activity_key' => 'order_coordination',
            'label' => 'Order coordination',
            'points' => '3.0000',
            'is_enabled' => true,
        ]);
    }

    private function assign(OrderEarningsPool $pool, User $user): void
    {
        DB::table('order_assignments')->insert([
            'order_id' => $pool->order_id,
            'user_id' => $user->id,
            'assigned_by' => $user->id,
            'assigned_at' => now()->subHours(5),
        ]);
    }

    public function test_guest_is_redirected(): void
    {
        $pool = $this->pool();

        $this->post(route('earnings.pools.refresh-contributions', $pool))
            ->assertRedirect(route('login'));
    }

    public function test_viewer_without_assign_permission_is_forbidden_and_sees_no_button(): void
    {
        $viewer = $this->userWith(['earnings.pools.view']);
        $pool = $this->pool();

        $this->actingAs($viewer)
            ->post(route('earnings.pools.refresh-contributions', $pool))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('earnings.pools.show', $pool))
            ->assertOk()
            ->assertDontSee('Refresh from workflow');

        $this->assertSame(0, OrderEarningsContribution::query()->count());
    }

    public function test_authorized_user_refreshes_and_contributions_are_recorded(): void
    {
        $this->weight();
        $pool = $this->pool();
        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($pool, $coordinator);

        $this->actingAs($this->finance())
            ->get(route('earnings.pools.show', $pool))
            ->assertOk()
            ->assertSee('Refresh from workflow');

        $this->actingAs($this->finance())
            ->post(route('earnings.pools.refresh-contributions', $pool))
            ->assertRedirect(route('earnings.pools.show', $pool))
            ->assertSessionHas('success');

        $this->assertSame(
            [$coordinator->id],
            OrderEarningsContribution::query()->pluck('user_id')->all()
        );
    }

    public function test_skipped_staff_are_reported_by_name_with_the_reason(): void
    {
        $this->weight();
        $pool = $this->pool();
        $inactive = User::factory()->create(['is_active' => false, 'name' => 'Dormant Dayo']);
        $this->assign($pool, $inactive);

        $response = $this->actingAs($this->finance())
            ->post(route('earnings.pools.refresh-contributions', $pool))
            ->assertRedirect(route('earnings.pools.show', $pool));

        $skipped = session('derivation_skipped');
        $this->assertCount(1, $skipped);
        $this->assertStringContainsString('Dormant Dayo', $skipped[0]);
        $this->assertStringContainsString('order coordination', $skipped[0]);
        $this->assertStringContainsString('active', $skipped[0]);

        $this->followRedirects($response)->assertSee('Not credited from workflow');
    }

    public function test_held_pool_can_be_refreshed(): void
    {
        $this->weight();
        $pool = $this->pool(OrderEarningsPool::STATUS_HELD);
        $this->assign($pool, User::factory()->create(['is_active' => true]));

        $this->actingAs($this->finance())
            ->post(route('earnings.pools.refresh-contributions', $pool))
            ->assertRedirect();

        $this->assertSame(1, OrderEarningsContribution::query()->count());
    }

    public function test_finalized_pool_cannot_be_refreshed_and_shows_no_button(): void
    {
        $this->weight();
        $pool = $this->pool(OrderEarningsPool::STATUS_FINALIZED);
        $this->assign($pool, User::factory()->create(['is_active' => true]));
        $finance = $this->finance();

        $this->actingAs($finance)
            ->post(route('earnings.pools.refresh-contributions', $pool))
            ->assertForbidden();

        $this->actingAs($finance)
            ->get(route('earnings.pools.show', $pool))
            ->assertOk()
            ->assertDontSee('Refresh from workflow');

        $this->assertSame(0, OrderEarningsContribution::query()->count());
    }
}
