<?php

namespace Tests\Feature\Stores;

use App\Actions\Stores\ReviewStoreRemittance;
use App\Actions\Stores\SubmitStoreRemittance;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreRemittance;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class StoreRemittanceReviewTest extends TestCase
{
    use RefreshDatabase;

    // Closing balance used by every closed day below: 3000 cash + 7000 bank = 10000.

    public function test_reviewer_can_confirm_a_pending_remission(): void
    {
        [$remittance] = $this->pendingRemittance();
        $reviewer = $this->reviewer();

        $reviewed = $this->review($reviewer, $remittance, 'confirm');

        $this->assertSame(StoreRemittance::STATUS_CONFIRMED, $reviewed->status);
        $this->assertSame($reviewer->id, $reviewed->reviewed_by);
        $this->assertNotNull($reviewed->reviewed_at);
        $this->assertNull($reviewed->review_reason);
        $this->assertSame('2000.00', $reviewed->amount_cash);
    }

    public function test_confirmation_can_carry_a_note(): void
    {
        [$remittance] = $this->pendingRemittance();

        $reviewed = $this->review($this->reviewer(), $remittance, 'confirm', '  Counted and banked.  ');

        $this->assertSame('Counted and banked.', $reviewed->review_reason);
    }

    public function test_reviewer_can_reject_with_a_reason(): void
    {
        [$remittance] = $this->pendingRemittance();
        $reviewer = $this->reviewer();

        $reviewed = $this->review($reviewer, $remittance, 'reject', 'Amount does not match the handover.');

        $this->assertSame(StoreRemittance::STATUS_REJECTED, $reviewed->status);
        $this->assertSame($reviewer->id, $reviewed->reviewed_by);
        $this->assertSame('Amount does not match the handover.', $reviewed->review_reason);
    }

    public function test_rejection_needs_a_reason_of_ten_to_one_thousand_characters(): void
    {
        [$remittance] = $this->pendingRemittance();
        $reviewer = $this->reviewer();

        foreach ([null, '', '   ', 'too short', str_repeat('a', 1001)] as $reason) {
            $this->assertInvalid(
                fn () => $this->review($reviewer, $remittance, 'reject', $reason),
                'review_reason'
            );
        }

        $this->assertSame(StoreRemittance::STATUS_PENDING, $remittance->fresh()->status);

        $reviewed = $this->review($reviewer, $remittance, 'reject', str_repeat('a', 1000));
        $this->assertSame(StoreRemittance::STATUS_REJECTED, $reviewed->status);
    }

    public function test_a_confirmation_note_over_one_thousand_characters_is_refused(): void
    {
        [$remittance] = $this->pendingRemittance();

        $this->assertInvalid(
            fn () => $this->review($this->reviewer(), $remittance, 'confirm', str_repeat('a', 1001)),
            'review_reason'
        );
    }

    public function test_unknown_decision_is_refused(): void
    {
        [$remittance] = $this->pendingRemittance();

        $this->assertInvalid(
            fn () => $this->review($this->reviewer(), $remittance, 'approve'),
            'decision'
        );
    }

    public function test_user_without_the_permission_is_refused(): void
    {
        [$remittance] = $this->pendingRemittance();
        $plain = User::factory()->create(['is_active' => true]);

        try {
            $this->review($plain, $remittance, 'confirm');
            $this->fail('Expected an authorization failure.');
        } catch (AuthorizationException) {
            $this->assertSame(StoreRemittance::STATUS_PENDING, $remittance->fresh()->status);
        }
    }

    public function test_inactive_user_with_the_permission_is_refused(): void
    {
        [$remittance] = $this->pendingRemittance();
        $inactive = $this->reviewer(['is_active' => false]);

        $this->expectException(AuthorizationException::class);
        $this->review($inactive, $remittance, 'confirm');
    }

    public function test_submitter_cannot_review_their_own_remission(): void
    {
        [$remittance, , $coordinator] = $this->pendingRemittance();
        $this->grantReviewPermission($coordinator);

        try {
            $this->review($coordinator, $remittance, 'confirm');
            $this->fail('Expected an authorization failure.');
        } catch (AuthorizationException) {
            $this->assertSame(StoreRemittance::STATUS_PENDING, $remittance->fresh()->status);
        }
    }

    public function test_an_already_reviewed_remission_cannot_be_reviewed_again(): void
    {
        [$remittance] = $this->pendingRemittance();
        $this->review($this->reviewer(), $remittance, 'confirm');

        $this->assertInvalid(
            fn () => $this->review($this->reviewer(), $remittance, 'reject', 'Changed my mind later on.'),
            'status'
        );

        $this->assertSame(StoreRemittance::STATUS_CONFIRMED, $remittance->fresh()->status);
    }

    public function test_confirmation_is_refused_when_pending_cash_rows_exceed_the_cash_balance(): void
    {
        [, $store, $coordinator, $day] = $this->pendingRemittance();

        // Rows that reached the table without going through the submit action.
        $first = $this->rawPending($store, $day, $coordinator, '2000.00');
        $this->rawPending($store, $day, $coordinator, '1500.00');

        $this->assertInvalid(
            fn () => $this->review($this->reviewer(), $first, 'confirm'),
            'status'
        );

        $this->assertSame(StoreRemittance::STATUS_PENDING, $first->fresh()->status);
    }

    public function test_confirmation_is_refused_when_pending_bank_rows_exceed_the_bank_balance(): void
    {
        [, $store, $coordinator, $day] = $this->pendingRemittance();

        $first = $this->rawPending($store, $day, $coordinator, '0.00', '4000.00');
        $this->rawPending($store, $day, $coordinator, '0.00', '3500.00');

        $this->assertInvalid(
            fn () => $this->review($this->reviewer(), $first, 'confirm'),
            'status'
        );

        $this->assertSame(StoreRemittance::STATUS_PENDING, $first->fresh()->status);
    }

    public function test_a_reviewed_remission_cannot_be_edited_afterwards(): void
    {
        [$remittance] = $this->pendingRemittance();
        $reviewed = $this->review($this->reviewer(), $remittance, 'confirm');

        $reviewed->status = StoreRemittance::STATUS_REJECTED;

        $this->expectException(LogicException::class);
        $reviewed->save();
    }

    public function test_a_rejection_frees_the_amount_so_the_store_can_submit_again(): void
    {
        [$remittance, $store, $coordinator, $day] = $this->pendingRemittance(['amount_cash' => '3000.00']);

        $this->review($this->reviewer(), $remittance, 'reject', 'Handover was short on the day.');

        $second = app(SubmitStoreRemittance::class)->execute($coordinator, $store, [
            'store_daily_cash_record_id' => $day->id,
            'amount_cash' => '3000.00',
            'amount_bank' => '0',
            'method' => StoreRemittance::METHOD_CASH_HANDOVER,
            'resubmits_id' => $remittance->id,
        ]);

        $this->assertSame(StoreRemittance::STATUS_PENDING, $second->status);
        $this->assertSame($remittance->id, $second->resubmits_id);
    }

    // ---------------------------------------------------------------- helpers

    private function review(User $actor, StoreRemittance $remittance, string $decision, ?string $reason = null): StoreRemittance
    {
        return app(ReviewStoreRemittance::class)->execute($actor, $remittance, $decision, $reason);
    }

    private function assertInvalid(callable $callback, string $key): void
    {
        try {
            $callback();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());

            return;
        }

        $this->fail("Expected a validation error on [{$key}].");
    }

    /**
     * @return array{0: StoreRemittance, 1: Store, 2: User, 3: StoreDailyCashRecord}
     */
    private function pendingRemittance(array $overrides = []): array
    {
        $store = $this->makeStore();
        $coordinator = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        $day = $this->closeDay($store, $coordinator, today()->toDateString());

        $remittance = app(SubmitStoreRemittance::class)->execute($coordinator, $store, array_merge([
            'store_daily_cash_record_id' => $day->id,
            'amount_cash' => '2000.00',
            'amount_bank' => '0',
            'method' => StoreRemittance::METHOD_CASH_HANDOVER,
        ], $overrides));

        return [$remittance, $store, $coordinator, $day];
    }

    private function rawPending(
        Store $store,
        StoreDailyCashRecord $day,
        User $submitter,
        string $cash,
        string $bank = '0.00'
    ): StoreRemittance {
        return StoreRemittance::create([
            'store_id' => $store->id,
            'store_daily_cash_record_id' => $day->id,
            'business_date' => $day->business_date,
            'amount_cash' => $cash,
            'amount_bank' => $bank,
            'method' => StoreRemittance::METHOD_CASH_HANDOVER,
            'status' => StoreRemittance::STATUS_PENDING,
            'submitted_by' => $submitter->id,
            'submitted_at' => now(),
        ]);
    }

    private function reviewer(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['is_active' => true], $attributes));
        $this->grantReviewPermission($user);

        return $user;
    }

    /**
     * Permission slugs are unique, so the permission is created once and
     * attached to a fresh role for every user that needs it.
     */
    private function grantReviewPermission(User $user): void
    {
        $permission = Permission::query()->firstOrCreate(
            ['slug' => ReviewStoreRemittance::PERMISSION],
            ['name' => ReviewStoreRemittance::PERMISSION]
        );

        $role = Role::factory()->create([
            'name' => 'Remittance Reviewer '.uniqid(),
            'slug' => 'remittance-reviewer-'.uniqid(),
        ]);

        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $user->roles()->attach($role);
    }

    private function closeDay(Store $store, User $user, string $date): StoreDailyCashRecord
    {
        return $store->dailyCashRecords()->create([
            'business_date' => $date,
            'opening_cash' => '1000.00',
            'opening_bank' => '5000.00',
            'closing_cash' => '3000.00',
            'closing_bank' => '7000.00',
            'opened_by' => $user->id,
            'closed_by' => $user->id,
        ]);
    }

    private function makeStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'name' => 'Remittance Review Store '.str()->random(8),
            'code' => strtoupper(fake()->unique()->bothify('RV###??')),
            'is_active' => true,
        ], $attributes));
    }

    private function assign(Store $store, string $role, array $userAttributes = []): User
    {
        $user = User::factory()->create(array_merge(['is_active' => true], $userAttributes));

        StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now()->subMinute(),
        ]);

        return $user;
    }
}
