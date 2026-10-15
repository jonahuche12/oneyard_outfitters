<?php

namespace Tests\Feature\Earnings;

use App\Actions\Earnings\DeriveOrderEarningsContributions;
use App\Models\Contact;
use App\Models\EarningsActivityWeight;
use App\Models\Order;
use App\Models\OrderEarningsContribution;
use App\Models\OrderEarningsPool;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeriveOrderEarningsContributionsTest extends TestCase
{
    use RefreshDatabase;

    private function user(bool $active = true): User
    {
        return User::factory()->create(['is_active' => $active]);
    }

    private function weights(array $skip = []): void
    {
        foreach ([
            'order_coordination' => '3.0000',
            'quality_control' => '1.0000',
            'delivery' => '2.0000',
            'procurement' => '2.0000',
        ] as $key => $points) {
            if (in_array($key, $skip, true)) {
                continue;
            }

            EarningsActivityWeight::query()->create([
                'activity_key' => $key,
                'label' => ucfirst($key),
                'points' => $points,
                'is_enabled' => true,
            ]);
        }
    }

    private function order(string $status = Order::STATUS_READY): Order
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'is_primary' => true,
            'is_active' => true,
        ]);
        $creator = $this->user();

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
            'notes' => 'Derive test quotation.',
        ]);

        return Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'order_date' => now()->toDateString(),
            'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(),
            'status' => $status,
            'subtotal' => 100000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 100000,
            'terms' => null,
            'notes' => 'Derive test order.',
        ]);
    }

    private function pool(Order $order, string $status = OrderEarningsPool::STATUS_PENDING): OrderEarningsPool
    {
        return OrderEarningsPool::query()->create([
            'order_id' => $order->id,
            'quotation_id' => $order->quotation_id,
            'quotation_number' => 'Q',
            'source_amount' => '100000.00',
            'rate_percent' => '5.00',
            'pool_amount' => '5000.00',
            'source_snapshot' => [],
            'delivered_at' => now()->subHour(),
            'status' => $status,
        ]);
    }

    private function assign(Order $order, User $user, bool $ended = false): int
    {
        return DB::table('order_assignments')->insertGetId([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'assigned_by' => $user->id,
            'assigned_at' => now()->subHours(5),
            'ended_at' => $ended ? now()->subHours(4) : null,
        ]);
    }

    private function inspection(Order $order, ?User $inspector, ?User $approver, string $status = 'completed', string $result = 'pass'): void
    {
        DB::table('quality_control_inspections')->insert([
            'order_id' => $order->id,
            'inspected_by' => $inspector?->id,
            'approved_by' => $approver?->id,
            'status' => $status,
            'result' => $result,
            'inspected_at' => now()->subHours(3),
            'approved_at' => now()->subHours(3),
        ]);
    }

    private function delivery(Order $order, User $creator, ?User $confirmer, $confirmedAt = null): void
    {
        DB::table('deliveries')->insert([
            'order_id' => $order->id,
            'created_by' => $creator->id,
            'confirmed_by' => $confirmer?->id,
            'status' => $confirmer ? 'confirmed' : 'pending',
            'confirmed_at' => $confirmer ? ($confirmedAt ?? now()->subHours(2)) : null,
            'created_at' => now()->subHours(4),
            'updated_at' => now()->subHours(2),
        ]);
    }

    private function run_(OrderEarningsPool $pool): array
    {
        return app(DeriveOrderEarningsContributions::class)->execute($pool);
    }

    private function rows(OrderEarningsPool $pool)
    {
        return OrderEarningsContribution::query()->where('order_earnings_pool_id', $pool->id)->get();
    }

    public function test_every_coordinator_in_the_assignment_history_is_credited(): void
    {
        $this->weights();
        $order = $this->order();
        $first = $this->user();
        $second = $this->user();
        $this->assign($order, $first, true);
        $this->assign($order, $second);
        $pool = $this->pool($order);

        $report = $this->run_($pool);

        $this->assertCount(2, $report['credited']);
        $this->assertSame([], $report['skipped']);
        $rows = $this->rows($pool);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $rows->pluck('user_id')->all());
        $this->assertSame('3', rtrim(rtrim((string) $rows->first()->points, '0'), '.'));
        $this->assertSame('order_coordination', $rows->first()->activity_key);
        $this->assertSame('workflow', $rows->first()->source_type);
    }

    public function test_inspector_and_approver_of_completed_inspections_are_credited_any_result(): void
    {
        $this->weights();
        $order = $this->order();
        $inspector = $this->user();
        $approver = $this->user();
        $ignored = $this->user();
        $this->inspection($order, $inspector, $approver, 'completed', 'fail');
        $this->inspection($order, $ignored, $ignored, 'in_progress', 'pass');
        $pool = $this->pool($order);

        $this->run_($pool);

        $rows = $this->rows($pool)->where('activity_key', 'quality_control');
        $this->assertEqualsCanonicalizing([$inspector->id, $approver->id], $rows->pluck('user_id')->all());
    }

    public function test_delivery_creator_and_confirmer_are_both_credited(): void
    {
        $this->weights();
        $order = $this->order();
        $creator = $this->user();
        $confirmer = $this->user();
        $this->delivery($order, $creator, $confirmer);
        $pool = $this->pool($order);

        $this->run_($pool);

        $rows = $this->rows($pool)->where('activity_key', 'delivery');
        $this->assertEqualsCanonicalizing([$creator->id, $confirmer->id], $rows->pluck('user_id')->all());
    }

    public function test_same_person_in_one_activity_is_credited_once_with_all_proof(): void
    {
        $this->weights();
        $order = $this->order();
        $person = $this->user();
        $this->delivery($order, $person, $person);
        $pool = $this->pool($order);

        $this->run_($pool);

        $rows = $this->rows($pool);
        $this->assertCount(1, $rows);
        $this->assertCount(2, $rows->first()->metadata['records']);
    }

    public function test_one_person_accumulates_across_activities(): void
    {
        $this->weights();
        $order = $this->order();
        $person = $this->user();
        $this->assign($order, $person);
        $this->inspection($order, $person, null);
        $pool = $this->pool($order);

        $this->run_($pool);

        $this->assertEqualsCanonicalizing(
            ['order_coordination', 'quality_control'],
            $this->rows($pool)->pluck('activity_key')->all()
        );
    }

    public function test_derivation_is_idempotent_and_records_proof_metadata(): void
    {
        $this->weights();
        $order = $this->order();
        $coordinator = $this->user();
        $recordId = $this->assign($order, $coordinator);
        $pool = $this->pool($order);

        $this->run_($pool);
        $second = $this->run_($pool);

        $this->assertCount(1, $this->rows($pool));
        $this->assertCount(1, $second['credited']);
        $this->assertSame('order_assignment:' . $recordId, $this->rows($pool)->first()->source_reference);
        $this->assertSame($recordId, $this->rows($pool)->first()->metadata['records'][0]['id']);
    }

    public function test_inactive_staff_are_skipped_and_reported_others_still_credited(): void
    {
        $this->weights();
        $order = $this->order();
        $inactive = $this->user(false);
        $active = $this->user();
        $this->assign($order, $inactive, true);
        $this->assign($order, $active);
        $pool = $this->pool($order);

        $report = $this->run_($pool);

        $this->assertCount(1, $report['credited']);
        $this->assertCount(1, $report['skipped']);
        $this->assertSame($inactive->id, $report['skipped'][0]['user_id']);
        $this->assertStringContainsString('active', $report['skipped'][0]['reason']);
        $this->assertSame([$active->id], $this->rows($pool)->pluck('user_id')->all());
    }

    public function test_activity_without_an_enabled_weight_is_skipped_and_reported(): void
    {
        $this->weights(['order_coordination']);
        $order = $this->order();
        $this->assign($order, $this->user());
        $pool = $this->pool($order);

        $report = $this->run_($pool);

        $this->assertCount(0, $this->rows($pool));
        $this->assertCount(1, $report['skipped']);
        $this->assertStringContainsString('weight', $report['skipped'][0]['reason']);
    }

    public function test_proof_dated_after_delivery_is_skipped_and_reported(): void
    {
        $this->weights();
        $order = $this->order();
        $this->delivery($order, $this->user(), $this->user(), now()->subMinutes(10));
        $pool = $this->pool($order);

        $report = $this->run_($pool);

        $this->assertCount(1, $this->rows($pool));
        $this->assertCount(1, $report['skipped']);
        $this->assertStringContainsString('delivered', $report['skipped'][0]['reason']);
    }

    public function test_existing_manual_contribution_is_reported_not_overwritten(): void
    {
        $this->weights();
        $order = $this->order();
        $person = $this->user();
        $this->assign($order, $person);
        $pool = $this->pool($order);

        OrderEarningsContribution::query()->create([
            'order_earnings_pool_id' => $pool->id,
            'user_id' => $person->id,
            'activity_key' => 'order_coordination',
            'points' => '3.0000',
            'source_type' => OrderEarningsContribution::SOURCE_MANUAL,
            'reason' => 'Entered by finance earlier.',
            'activity_at' => now()->subHours(6),
            'user_active_at_activity' => true,
        ]);

        $report = $this->run_($pool);

        $this->assertCount(1, $this->rows($pool));
        $this->assertSame('manual', $this->rows($pool)->first()->source_type);
        $this->assertCount(1, $report['skipped']);
    }

    public function test_held_pool_accepts_derived_contributions(): void
    {
        $this->weights();
        $order = $this->order();
        $this->assign($order, $this->user());
        $pool = $this->pool($order, OrderEarningsPool::STATUS_HELD);

        $report = $this->run_($pool);

        $this->assertCount(1, $report['credited']);
        $this->assertCount(1, $this->rows($pool));
    }

    public function test_finalized_pool_is_rejected(): void
    {
        $this->weights();
        $order = $this->order();
        $pool = $this->pool($order, OrderEarningsPool::STATUS_FINALIZED);

        $this->expectException(ValidationException::class);

        $this->run_($pool);
    }

    public function test_marking_an_order_delivered_derives_contributions_automatically(): void
    {
        $this->weights();
        $order = $this->order();
        $coordinator = $this->user();
        $this->assign($order, $coordinator);

        $order->update(['status' => Order::STATUS_DELIVERED]);

        $pool = OrderEarningsPool::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame([$coordinator->id], $this->rows($pool)->pluck('user_id')->all());
    }

    private function procurement(?Order $order, bool $deleted = false): int
    {
        return DB::table('procurements')->insertGetId([
            'order_id' => $order?->id,
            'created_by' => $this->user()->id,
            'item_name' => 'Fabric',
            'quantity' => '10.00',
            'unit' => 'yard',
            'maximum_unit_price' => '1000.00',
            'commission_per_unit' => '0',
            'status' => 'ready',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
            'deleted_at' => $deleted ? now()->subDay() : null,
        ]);
    }

    private function offer(
        int $procurementId,
        User $user,
        string $status = 'accepted',
        ?string $deliveredQty = '10.00',
        $deliveredAt = null
    ): int {
        return DB::table('procurement_offers')->insertGetId([
            'procurement_id' => $procurementId,
            'user_id' => $user->id,
            'quantity' => '10.00',
            'unit_price' => '900.00',
            'total_price' => '9000.00',
            'status' => $status,
            'submitted_at' => now()->subDays(2),
            'delivered_quantity' => $deliveredQty,
            'delivered_by' => $deliveredQty ? $user->id : null,
            'delivered_at' => $deliveredQty ? ($deliveredAt ?? now()->subHours(3)) : null,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
    }

    public function test_delivery_confirmed_accepted_offer_credits_the_sourcing_staff(): void
    {
        $this->weights();
        $order = $this->order();
        $sourcer = $this->user();
        $offerId = $this->offer($this->procurement($order), $sourcer);
        $pool = $this->pool($order);

        $this->run_($pool);

        $rows = $this->rows($pool)->where('activity_key', 'procurement');
        $this->assertSame([$sourcer->id], $rows->pluck('user_id')->all());
        $this->assertSame('procurement_offer:' . $offerId, $rows->first()->source_reference);
    }

    public function test_several_offers_from_one_person_collapse_to_one_contribution(): void
    {
        $this->weights();
        $order = $this->order();
        $sourcer = $this->user();
        $this->offer($this->procurement($order), $sourcer);
        $this->offer($this->procurement($order), $sourcer);
        $pool = $this->pool($order);

        $this->run_($pool);

        $rows = $this->rows($pool)->where('activity_key', 'procurement');
        $this->assertCount(1, $rows);
        $this->assertCount(2, $rows->first()->metadata['records']);
    }

    public function test_offers_that_are_not_delivery_confirmed_or_not_accepted_do_not_count(): void
    {
        $this->weights();
        $order = $this->order();
        $procurementId = $this->procurement($order);
        $this->offer($procurementId, $this->user(), 'accepted', null);
        $this->offer($procurementId, $this->user(), 'withdrawn');
        $this->offer($procurementId, $this->user(), 'rejected');
        $this->offer($procurementId, $this->user(), 'submitted');
        $this->offer($procurementId, $this->user(), 'accepted', '0.00');
        $pool = $this->pool($order);

        $report = $this->run_($pool);

        $this->assertCount(0, $this->rows($pool));
        $this->assertSame([], $report['credited']);
    }

    public function test_independent_and_deleted_procurements_do_not_count(): void
    {
        $this->weights();
        $order = $this->order();
        $this->offer($this->procurement(null), $this->user());
        $this->offer($this->procurement($order, true), $this->user());
        $other = $this->order();
        $this->offer($this->procurement($other), $this->user());
        $pool = $this->pool($order);

        $this->run_($pool);

        $this->assertCount(0, $this->rows($pool));
    }

    public function test_procurement_delivered_after_the_order_is_skipped_and_reported(): void
    {
        $this->weights();
        $order = $this->order();
        $late = $this->user();
        $this->offer($this->procurement($order), $late, 'accepted', '10.00', now()->subMinutes(5));
        $pool = $this->pool($order);

        $report = $this->run_($pool);

        $this->assertCount(0, $this->rows($pool));
        $this->assertCount(1, $report['skipped']);
        $this->assertSame($late->id, $report['skipped'][0]['user_id']);
        $this->assertStringContainsString('delivered', $report['skipped'][0]['reason']);
    }
}
