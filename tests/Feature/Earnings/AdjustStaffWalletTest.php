<?php

namespace Tests\Feature\Earnings;

use App\Actions\Earnings\AdjustStaffWallet;
use App\Models\Role;
use App\Models\StaffWalletEntry;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdjustStaffWalletTest extends TestCase
{
    use RefreshDatabase;

    private ?User $adjuster = null;

    private function userWithPermissions(array $permissions, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);

        $role = Role::factory()->create([
            'name' => 'Adjust Test Role ' . uniqid(),
            'slug' => 'adjust-test-' . uniqid(),
        ]);

        $role->permissions()->createMany(
            collect($permissions)
                ->map(fn (string $slug) => ['name' => $slug, 'slug' => $slug])
                ->all()
        );

        $user->roles()->attach($role);

        return $user;
    }

    private function adjuster(): User
    {
        return $this->adjuster ??= $this->userWithPermissions([AdjustStaffWallet::PERMISSION]);
    }

    private function earning(User $user, string $amount = '3333.33'): StaffWalletEntry
    {
        return StaffWalletEntry::query()->create([
            'user_id' => $user->id,
            'type' => StaffWalletEntry::TYPE_EARNING,
            'amount' => $amount,
        ]);
    }

    private function action(): AdjustStaffWallet
    {
        return app(AdjustStaffWallet::class);
    }

    private function assertValidationKey(callable $fn, string $key): void
    {
        try {
            $fn();
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());
        }
    }

    public function test_authorized_actor_records_a_negative_adjustment(): void
    {
        $staff = User::factory()->create();
        $this->earning($staff, '1000.00');
        $actor = $this->adjuster();

        $entry = $this->action()->adjust($staff, '-250.50', 'Refund recovery for disputed order.', $actor);

        $this->assertSame(StaffWalletEntry::TYPE_ADJUSTMENT, $entry->type);
        $this->assertSame('-250.50', $entry->amount);
        $this->assertSame($actor->id, $entry->created_by);
        $this->assertSame('Refund recovery for disputed order.', $entry->reason);
        $this->assertSame('749.50', StaffWalletEntry::balanceFor($staff));
    }

    public function test_positive_adjustment_increases_the_balance(): void
    {
        $staff = User::factory()->create();

        $this->action()->adjust($staff, '500', 'Correction for missed contribution.', $this->adjuster());

        $this->assertSame('500.00', StaffWalletEntry::balanceFor($staff));
    }

    public function test_adjustment_may_take_the_balance_below_zero(): void
    {
        $staff = User::factory()->create();

        $this->action()->adjust($staff, '-100.00', 'Recovery from future earnings.', $this->adjuster());

        $this->assertSame('-100.00', StaffWalletEntry::balanceFor($staff));
    }

    public function test_actor_without_permission_is_denied(): void
    {
        $staff = User::factory()->create();
        $actor = $this->userWithPermissions(['orders.view']);

        try {
            $this->action()->adjust($staff, '10.00', 'Valid reason given here.', $actor);
            $this->fail('Expected AuthorizationException.');
        } catch (AuthorizationException) {
            // expected
        }

        $this->assertSame(0, StaffWalletEntry::query()->count());
    }

    public function test_inactive_actor_with_permission_is_denied(): void
    {
        $staff = User::factory()->create();
        $actor = $this->userWithPermissions([AdjustStaffWallet::PERMISSION], false);

        $this->expectException(AuthorizationException::class);

        $this->action()->adjust($staff, '10.00', 'Valid reason given here.', $actor);
    }

    public function test_short_or_blank_reason_is_rejected(): void
    {
        $staff = User::factory()->create();

        $this->assertValidationKey(
            fn () => $this->action()->adjust($staff, '10.00', 'too short', $this->adjuster()),
            'reason'
        );
        $this->assertValidationKey(
            fn () => $this->action()->adjust($staff, '10.00', '           ', $this->adjuster()),
            'reason'
        );

        $this->assertSame(0, StaffWalletEntry::query()->count());
    }

    public function test_zero_and_malformed_amounts_are_rejected(): void
    {
        $staff = User::factory()->create();

        foreach (['0', '0.00', '-0.00', '10.123', 'abc', '', '1e5'] as $bad) {
            $this->assertValidationKey(
                fn () => $this->action()->adjust($staff, $bad, 'Valid reason given here.', $this->adjuster()),
                'amount'
            );
        }

        $this->assertSame(0, StaffWalletEntry::query()->count());
    }

    public function test_earning_can_be_reversed_with_an_equal_opposite_entry(): void
    {
        $staff = User::factory()->create();
        $earning = $this->earning($staff, '3333.33');
        $actor = $this->adjuster();

        $reversal = $this->action()->reverse($earning, 'Order refunded after delivery.', $actor);

        $this->assertSame(StaffWalletEntry::TYPE_REVERSAL, $reversal->type);
        $this->assertSame('-3333.33', $reversal->amount);
        $this->assertSame($earning->id, $reversal->reverses_entry_id);
        $this->assertSame($staff->id, $reversal->user_id);
        $this->assertSame($actor->id, $reversal->created_by);
        $this->assertSame('0.00', StaffWalletEntry::balanceFor($staff));
        $this->assertSame('3333.33', $earning->fresh()->amount);
    }

    public function test_an_entry_cannot_be_reversed_twice(): void
    {
        $staff = User::factory()->create();
        $earning = $this->earning($staff);

        $this->action()->reverse($earning, 'Order refunded after delivery.', $this->adjuster());

        $this->assertValidationKey(
            fn () => $this->action()->reverse($earning, 'Second attempt to reverse.', $this->adjuster()),
            'entry'
        );

        $this->assertSame(2, StaffWalletEntry::query()->count());
    }

    public function test_only_earning_entries_can_be_reversed(): void
    {
        $staff = User::factory()->create();
        $adjustment = $this->action()->adjust($staff, '-50.00', 'Recovery from future earnings.', $this->adjuster());
        $earning = $this->earning($staff);
        $reversal = $this->action()->reverse($earning, 'Order refunded after delivery.', $this->adjuster());

        $this->assertValidationKey(
            fn () => $this->action()->reverse($adjustment, 'Trying to reverse adjustment.', $this->adjuster()),
            'entry'
        );
        $this->assertValidationKey(
            fn () => $this->action()->reverse($reversal, 'Trying to reverse a reversal.', $this->adjuster()),
            'entry'
        );
    }

    public function test_reversal_requires_permission_and_reason(): void
    {
        $staff = User::factory()->create();
        $earning = $this->earning($staff);

        try {
            $this->action()->reverse($earning, 'Valid reason given here.', $this->userWithPermissions(['orders.view']));
            $this->fail('Expected AuthorizationException.');
        } catch (AuthorizationException) {
            // expected
        }

        $this->assertValidationKey(
            fn () => $this->action()->reverse($earning, 'short', $this->adjuster()),
            'reason'
        );

        $this->assertSame(1, StaffWalletEntry::query()->count());
    }
}
