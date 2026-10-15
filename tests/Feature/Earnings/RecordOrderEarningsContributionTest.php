<?php

namespace Tests\Feature\Earnings;

use App\Actions\Earnings\RecordOrderEarningsContribution;
use App\Models\Contact;
use App\Models\EarningsActivityWeight;
use App\Models\Order;
use App\Models\OrderEarningsContribution;
use App\Models\OrderEarningsPool;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Throwable;

class RecordOrderEarningsContributionTest extends TestCase
{
    use RefreshDatabase;

    private ?User $assigner = null;

    private function userWithPermissions(array $permissions, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);

        $role = Role::factory()->create([
            'name' => 'Contribution Test Role ' . uniqid(),
            'slug' => 'contribution-test-' . uniqid(),
        ]);

        $role->permissions()->createMany(
            collect($permissions)
                ->map(fn (string $slug) => ['name' => $slug, 'slug' => $slug])
                ->all()
        );

        $user->roles()->attach($role);

        return $user;
    }

    private function assigner(): User
    {
        return $this->assigner ??= $this->userWithPermissions([
            'earnings.contributions.assign',
        ]);
    }

    private function staff(bool $active = true): User
    {
        return User::factory()->create(['is_active' => $active]);
    }

    private function weight(
        string $key = 'production',
        string $points = '2.5',
        bool $enabled = true
    ): EarningsActivityWeight {
        return EarningsActivityWeight::query()->create([
            'activity_key' => $key,
            'label' => ucfirst($key),
            'points' => $points,
            'is_enabled' => $enabled,
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
            'notes' => 'Contribution test quotation.',
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
            'notes' => 'Contribution test order.',
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

    private function manualPayload(User $staff, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $staff->id,
            'activity_key' => 'production',
            'source_type' => OrderEarningsContribution::SOURCE_MANUAL,
            'activity_at' => now()->subHours(2)->toDateTimeString(),
            'reason' => 'Performed the production work but was not assigned.',
        ], $overrides);
    }

    private function workflowPayload(User $staff, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $staff->id,
            'activity_key' => 'production',
            'source_type' => OrderEarningsContribution::SOURCE_WORKFLOW,
            'activity_at' => now()->subHours(2)->toDateTimeString(),
            'source_reference' => 'production_plan:1',
        ], $overrides);
    }

    private function action(): RecordOrderEarningsContribution
    {
        return app(RecordOrderEarningsContribution::class);
    }

    private function assertRejected(string $exception, callable $call): void
    {
        try {
            $call();
        } catch (Throwable $e) {
            $this->assertInstanceOf($exception, $e);

            return;
        }

        $this->fail("Expected {$exception} was not thrown.");
    }

    public function test_authorized_actor_records_manual_contribution(): void
    {
        $this->weight('production', '2.5');
        $pool = $this->pool();
        $staff = $this->staff();
        $actor = $this->assigner();

        $contribution = $this->action()->execute(
            $pool,
            $this->manualPayload($staff),
            $actor
        );

        $this->assertSame($pool->id, $contribution->order_earnings_pool_id);
        $this->assertSame($staff->id, $contribution->user_id);
        $this->assertSame('production', $contribution->activity_key);
        $this->assertSame('2.5000', (string) $contribution->points);
        $this->assertSame(OrderEarningsContribution::SOURCE_MANUAL, $contribution->source_type);
        $this->assertSame($actor->id, $contribution->assigned_by);
        $this->assertSame(
            'Performed the production work but was not assigned.',
            $contribution->reason
        );
        $this->assertTrue($contribution->user_active_at_activity);
        $this->assertSame(1, OrderEarningsContribution::count());
    }

    public function test_workflow_contribution_is_recorded_without_actor(): void
    {
        $this->weight('production', '2.5');
        $pool = $this->pool();
        $staff = $this->staff();

        $contribution = $this->action()->execute($pool, $this->workflowPayload($staff));

        $this->assertSame(OrderEarningsContribution::SOURCE_WORKFLOW, $contribution->source_type);
        $this->assertSame('production_plan:1', $contribution->source_reference);
        $this->assertNull($contribution->assigned_by);
        $this->assertNull($contribution->reason);
        $this->assertSame('2.5000', (string) $contribution->points);
    }

    public function test_points_are_a_snapshot_of_the_weight_at_recording_time(): void
    {
        $weight = $this->weight('production', '2.5');
        $pool = $this->pool();
        $staff = $this->staff();

        $contribution = $this->action()->execute($pool, $this->workflowPayload($staff));

        $weight->update(['points' => '9.0000']);

        $this->assertSame('2.5000', (string) $contribution->fresh()->points);
    }

    public function test_workflow_contribution_is_idempotent(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff();

        $first = $this->action()->execute($pool, $this->workflowPayload($staff));
        $second = $this->action()->execute($pool, $this->workflowPayload($staff));

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, OrderEarningsContribution::count());
    }

    public function test_manual_duplicate_is_rejected(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff();
        $actor = $this->assigner();

        $this->action()->execute($pool, $this->manualPayload($staff), $actor);

        $this->assertRejected(ValidationException::class, fn () => $this->action()->execute(
            $pool,
            $this->manualPayload($staff),
            $actor
        ));

        $this->assertSame(1, OrderEarningsContribution::count());
    }

    public function test_manual_assignment_cannot_overwrite_an_existing_workflow_contribution(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff();

        $this->action()->execute($pool, $this->workflowPayload($staff));

        $this->assertRejected(ValidationException::class, fn () => $this->action()->execute(
            $pool,
            $this->manualPayload($staff),
            $this->assigner()
        ));

        $this->assertSame(1, OrderEarningsContribution::count());
        $this->assertSame(
            OrderEarningsContribution::SOURCE_WORKFLOW,
            OrderEarningsContribution::first()->source_type
        );
    }

    public function test_same_staff_may_hold_different_activities_and_different_staff_the_same_activity(): void
    {
        $this->weight('production', '2');
        $this->weight('quality_control', '1');
        $pool = $this->pool();
        $staffA = $this->staff();
        $staffB = $this->staff();

        $this->action()->execute($pool, $this->workflowPayload($staffA));
        $this->action()->execute($pool, $this->workflowPayload($staffA, [
            'activity_key' => 'quality_control',
            'source_reference' => 'qc:1',
        ]));
        $this->action()->execute($pool, $this->workflowPayload($staffB, [
            'source_reference' => 'production_plan:2',
        ]));

        $this->assertSame(3, OrderEarningsContribution::count());
    }

    public function test_manual_assignment_without_permission_is_denied(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff();
        $actor = $this->userWithPermissions(['orders.view']);

        $this->assertRejected(AuthorizationException::class, fn () => $this->action()->execute(
            $pool,
            $this->manualPayload($staff),
            $actor
        ));

        $this->assertSame(0, OrderEarningsContribution::count());
    }

    public function test_manual_assignment_without_actor_is_denied(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff();

        $this->assertRejected(AuthorizationException::class, fn () => $this->action()->execute(
            $pool,
            $this->manualPayload($staff)
        ));

        $this->assertSame(0, OrderEarningsContribution::count());
    }

    public function test_inactive_actor_with_permission_is_denied(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff();
        $actor = $this->userWithPermissions(['earnings.contributions.assign'], false);

        $this->assertRejected(AuthorizationException::class, fn () => $this->action()->execute(
            $pool,
            $this->manualPayload($staff),
            $actor
        ));

        $this->assertSame(0, OrderEarningsContribution::count());
    }

    public function test_inactive_staff_member_cannot_earn(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff(false);

        $this->assertRejected(ValidationException::class, fn () => $this->action()->execute(
            $pool,
            $this->workflowPayload($staff)
        ));

        $this->assertSame(0, OrderEarningsContribution::count());
    }

    public function test_activity_without_a_weight_is_rejected(): void
    {
        $pool = $this->pool();
        $staff = $this->staff();

        $this->assertRejected(ValidationException::class, fn () => $this->action()->execute(
            $pool,
            $this->workflowPayload($staff)
        ));

        $this->assertSame(0, OrderEarningsContribution::count());
    }

    public function test_disabled_weight_is_rejected(): void
    {
        $this->weight('production', '2.5', false);
        $pool = $this->pool();
        $staff = $this->staff();

        $this->assertRejected(ValidationException::class, fn () => $this->action()->execute(
            $pool,
            $this->workflowPayload($staff)
        ));

        $this->assertSame(0, OrderEarningsContribution::count());
    }

    public function test_finalized_pools_are_rejected(): void
    {
        $this->weight();
        $staff = $this->staff();
        $pool = $this->pool(OrderEarningsPool::STATUS_FINALIZED);

        $this->assertRejected(ValidationException::class, fn () => $this->action()->execute(
            $pool,
            $this->workflowPayload($staff)
        ));

        $this->assertSame(0, OrderEarningsContribution::count());
    }

    public function test_held_pools_accept_contributions(): void
    {
        $this->weight();
        $staff = $this->staff();
        $pool = $this->pool(OrderEarningsPool::STATUS_HELD);

        $this->action()->execute($pool, $this->workflowPayload($staff));

        $this->assertSame(1, OrderEarningsContribution::count());
    }

    public function test_future_activity_time_is_rejected(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff();

        $this->assertRejected(ValidationException::class, fn () => $this->action()->execute(
            $pool,
            $this->workflowPayload($staff, [
                'activity_at' => now()->addHour()->toDateTimeString(),
            ])
        ));

        $this->assertSame(0, OrderEarningsContribution::count());
    }

    public function test_activity_time_after_delivery_is_rejected(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff();

        $this->assertRejected(ValidationException::class, fn () => $this->action()->execute(
            $pool,
            $this->workflowPayload($staff, [
                'activity_at' => now()->subMinutes(10)->toDateTimeString(),
            ])
        ));

        $this->assertSame(0, OrderEarningsContribution::count());
    }

    public function test_manual_reason_must_be_meaningful(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff();
        $actor = $this->assigner();

        foreach (['', 'short', '         '] as $reason) {
            $this->assertRejected(ValidationException::class, fn () => $this->action()->execute(
                $pool,
                $this->manualPayload($staff, ['reason' => $reason]),
                $actor
            ));
        }

        $this->assertSame(0, OrderEarningsContribution::count());
    }

    public function test_workflow_contribution_requires_a_source_reference(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff();

        $this->assertRejected(ValidationException::class, fn () => $this->action()->execute(
            $pool,
            $this->workflowPayload($staff, ['source_reference' => null])
        ));

        $this->assertSame(0, OrderEarningsContribution::count());
    }

    public function test_unsupported_source_type_is_rejected(): void
    {
        $this->weight();
        $pool = $this->pool();
        $staff = $this->staff();

        $this->assertRejected(ValidationException::class, fn () => $this->action()->execute(
            $pool,
            $this->workflowPayload($staff, [
                'source_type' => OrderEarningsContribution::SOURCE_OVERSIGHT,
            ])
        ));

        $this->assertSame(0, OrderEarningsContribution::count());
    }
}
