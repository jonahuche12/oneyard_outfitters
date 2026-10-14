<?php

namespace Tests\Feature\Earnings;

use App\Actions\Earnings\UpdateEarningsActivityWeight;
use App\Models\EarningsActivityWeight;
use App\Models\EarningsWeightAudit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateEarningsActivityWeightTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissions, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);

        $role = Role::factory()->create([
            'name' => 'Earnings Test Role ' . uniqid(),
            'slug' => 'earnings-test-' . uniqid(),
        ]);

        $role->permissions()->createMany(
            collect($permissions)
                ->map(fn (string $slug) => ['name' => $slug, 'slug' => $slug])
                ->all()
        );

        $user->roles()->attach($role);

        return $user;
    }

    private ?User $manager = null;

    private function manager(): User
    {
        return $this->manager ??= $this->userWithPermissions(['earnings.weights.manage']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'activity_key' => 'order_coordination',
            'label' => 'Order coordination',
            'points' => '2.5',
            'is_enabled' => true,
            'reason' => 'Initial weight set by management.',
        ], $overrides);
    }

    public function test_authorized_actor_creates_weight_and_audit_record(): void
    {
        $actor = $this->manager();

        $weight = app(UpdateEarningsActivityWeight::class)
            ->execute($actor, $this->payload());

        $this->assertSame('order_coordination', $weight->activity_key);
        $this->assertSame('2.5000', $weight->points);
        $this->assertTrue($weight->is_enabled);
        $this->assertSame($actor->id, $weight->updated_by);

        $audit = EarningsWeightAudit::query()->sole();
        $this->assertSame($weight->id, $audit->activity_weight_id);
        $this->assertNull($audit->old_points);
        $this->assertNull($audit->old_enabled);
        $this->assertSame('2.5000', $audit->new_points);
        $this->assertTrue($audit->new_enabled);
        $this->assertSame($actor->id, $audit->changed_by);
        $this->assertSame('Initial weight set by management.', $audit->reason);
        $this->assertSame('created', $audit->metadata['operation']);
    }

    public function test_update_changes_weight_and_preserves_audit_history(): void
    {
        $actor = $this->manager();
        $action = app(UpdateEarningsActivityWeight::class);

        $action->execute($actor, $this->payload());
        $weight = $action->execute($actor, $this->payload([
            'label' => 'Order coordination and follow-up',
            'points' => '4',
            'reason' => 'Revised after management review.',
        ]));

        $this->assertSame(1, EarningsActivityWeight::query()->count());
        $this->assertSame('4.0000', $weight->points);
        $this->assertSame('Order coordination and follow-up', $weight->label);

        $audits = EarningsWeightAudit::query()->orderBy('id')->get();
        $this->assertCount(2, $audits);

        $this->assertSame('created', $audits[0]->metadata['operation']);
        $this->assertSame('2.5000', $audits[0]->new_points);

        $this->assertSame('updated', $audits[1]->metadata['operation']);
        $this->assertSame('2.5000', $audits[1]->old_points);
        $this->assertSame('4.0000', $audits[1]->new_points);
        $this->assertSame('Revised after management review.', $audits[1]->reason);
    }

    public function test_disabling_a_weight_is_audited(): void
    {
        $actor = $this->manager();
        $action = app(UpdateEarningsActivityWeight::class);

        $action->execute($actor, $this->payload());
        $weight = $action->execute($actor, $this->payload([
            'is_enabled' => false,
            'reason' => 'Activity suspended pending policy review.',
        ]));

        $this->assertFalse($weight->is_enabled);

        $audit = EarningsWeightAudit::query()->orderByDesc('id')->first();
        $this->assertTrue($audit->old_enabled);
        $this->assertFalse($audit->new_enabled);
    }

    public function test_actor_without_permission_is_denied_and_nothing_is_written(): void
    {
        $actor = $this->userWithPermissions(['orders.view']);

        try {
            app(UpdateEarningsActivityWeight::class)->execute($actor, $this->payload());
            $this->fail('Expected AuthorizationException.');
        } catch (AuthorizationException $e) {
            $this->assertDatabaseCount('earnings_activity_weights', 0);
            $this->assertDatabaseCount('earnings_weight_audits', 0);
        }
    }

    public function test_inactive_actor_with_permission_is_denied(): void
    {
        $actor = $this->userWithPermissions(['earnings.weights.manage'], false);

        $this->expectException(AuthorizationException::class);

        app(UpdateEarningsActivityWeight::class)->execute($actor, $this->payload());
    }

    public function test_invalid_points_are_rejected(): void
    {
        foreach (['-1', 'abc', '1.23456', '1000000', ''] as $points) {
            $this->assertRejected(['points' => $points], 'points', "points value [{$points}]");
        }
    }

    public function test_zero_points_are_accepted(): void
    {
        $weight = app(UpdateEarningsActivityWeight::class)
            ->execute($this->manager(), $this->payload(['points' => '0']));

        $this->assertSame('0.0000', $weight->points);
    }

    public function test_missing_reason_is_rejected(): void
    {
        $this->assertRejected(['reason' => ''], 'reason');
    }

    public function test_too_short_reason_is_rejected(): void
    {
        $this->assertRejected(['reason' => 'short'], 'reason');
    }

    public function test_whitespace_only_reason_is_rejected(): void
    {
        $this->assertRejected(['reason' => '            '], 'reason');
    }

    public function test_invalid_activity_key_is_rejected(): void
    {
        $this->assertRejected(['activity_key' => 'Order Coordination'], 'activity_key');
    }

    private function assertRejected(array $overrides, string $field, string $context = ''): void
    {
        try {
            app(UpdateEarningsActivityWeight::class)
                ->execute($this->manager(), $this->payload($overrides));
            $this->fail('Expected ValidationException. ' . $context);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors(), $context);
            $this->assertDatabaseCount('earnings_activity_weights', 0);
            $this->assertDatabaseCount('earnings_weight_audits', 0);
        }
    }
}
