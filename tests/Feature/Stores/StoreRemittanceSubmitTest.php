<?php

namespace Tests\Feature\Stores;

use App\Actions\Stores\SubmitStoreRemittance;
use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreRemittance;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreRemittanceSubmitTest extends TestCase
{
    use RefreshDatabase;

    // Closing balance used by every closed day below: 3000 cash + 7000 bank = 10000.

    public function test_coordinator_can_submit_a_remission_for_a_closed_day(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();

        $remittance = $this->submit($coordinator, $store, $day, [
            'amount_cash' => '3000.00',
            'amount_bank' => '7000.00',
            'reference' => 'REF-001',
            'note' => 'Full closing balance',
        ]);

        $this->assertSame($store->id, $remittance->store_id);
        $this->assertSame($day->id, $remittance->store_daily_cash_record_id);
        $this->assertSame($day->business_date->toDateString(), $remittance->business_date->toDateString());
        $this->assertSame('3000.00', $remittance->amount_cash);
        $this->assertSame('7000.00', $remittance->amount_bank);
        $this->assertSame(StoreRemittance::STATUS_PENDING, $remittance->status);
        $this->assertSame($coordinator->id, $remittance->submitted_by);
        $this->assertNotNull($remittance->submitted_at);
        $this->assertNull($remittance->reviewed_by);
        $this->assertSame('REF-001', $remittance->reference);
        $this->assertDatabaseCount('store_remittances', 1);
    }

    public function test_assistant_can_submit_a_remission(): void
    {
        [$store, , $day] = $this->closedStoreDay();
        $assistant = $this->assign($store, StoreStaff::ROLE_ASSISTANT);

        $remittance = $this->submit($assistant, $store, $day, ['amount_cash' => '500.00']);

        $this->assertSame($assistant->id, $remittance->submitted_by);
    }

    public function test_a_blank_amount_is_treated_as_zero(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();

        $remittance = $this->submit($coordinator, $store, $day, [
            'amount_cash' => '',
            'amount_bank' => '1200.00',
            'method' => StoreRemittance::METHOD_BANK_TRANSFER,
        ]);

        $this->assertSame('0.00', $remittance->amount_cash);
        $this->assertSame('1200.00', $remittance->amount_bank);
    }

    public function test_user_not_assigned_to_the_store_is_refused(): void
    {
        [$store, , $day] = $this->closedStoreDay();
        $outsider = User::factory()->create(['is_active' => true]);

        $this->expectException(AuthorizationException::class);
        $this->submit($outsider, $store, $day);
    }

    public function test_coordinator_of_another_store_is_refused(): void
    {
        [$store, , $day] = $this->closedStoreDay();
        $otherStore = $this->makeStore();
        $otherCoordinator = $this->assign($otherStore, StoreStaff::ROLE_COORDINATOR);

        $this->expectException(AuthorizationException::class);
        $this->submit($otherCoordinator, $store, $day);
    }

    public function test_inactive_user_is_refused(): void
    {
        [$store, , $day] = $this->closedStoreDay();
        $inactive = $this->assign($store, StoreStaff::ROLE_ASSISTANT, ['is_active' => false]);

        $this->expectException(AuthorizationException::class);
        $this->submit($inactive, $store, $day);
    }

    public function test_inactive_store_is_refused(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();
        $store->forceFill(['is_active' => false])->save();

        $this->expectException(AuthorizationException::class);
        $this->submit($coordinator, $store->fresh(), $day);
    }

    public function test_ended_assignment_is_refused(): void
    {
        [$store, , $day] = $this->closedStoreDay();
        $assistant = $this->assign($store, StoreStaff::ROLE_ASSISTANT);

        StoreStaff::query()
            ->where('user_id', $assistant->id)
            ->first()
            ->forceFill(['ends_at' => now()])
            ->save();

        $this->expectException(AuthorizationException::class);
        $this->submit($assistant, $store, $day);
    }

    public function test_an_open_day_cannot_be_remitted(): void
    {
        $store = $this->makeStore();
        $coordinator = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        $openDay = $store->dailyCashRecords()->create([
            'business_date' => today()->toDateString(),
            'opening_cash' => '1000.00',
            'opening_bank' => '5000.00',
            'opened_by' => $coordinator->id,
        ]);

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $openDay), 'store_daily_cash_record_id');
        $this->assertDatabaseCount('store_remittances', 0);
    }

    public function test_a_day_belonging_to_another_store_is_refused(): void
    {
        [$store, $coordinator] = $this->closedStoreDay();
        [, , $otherDay] = $this->closedStoreDay();

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $otherDay), 'store_daily_cash_record_id');
        $this->assertDatabaseCount('store_remittances', 0);
    }

    public function test_both_amounts_zero_is_refused(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $day, [
            'amount_cash' => '0',
            'amount_bank' => '0',
        ]), 'amount_cash');
    }

    public function test_negative_amount_is_refused(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $day, [
            'amount_cash' => '-5.00',
        ]), 'amount_cash');
    }

    public function test_more_than_two_decimals_is_refused(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $day, [
            'amount_cash' => '10.555',
        ]), 'amount_cash');
    }

    public function test_unknown_method_is_refused(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $day, [
            'method' => 'crypto',
        ]), 'method');
    }

    public function test_exactly_the_closing_balance_is_accepted_and_one_cent_more_is_refused(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $day, [
            'amount_cash' => '3000.00',
            'amount_bank' => '7000.01',
        ]), 'amount_cash');

        $this->submit($coordinator, $store, $day, [
            'amount_cash' => '3000.00',
            'amount_bank' => '7000.00',
        ]);

        $this->assertDatabaseCount('store_remittances', 1);
    }

    public function test_several_partial_remissions_are_allowed_until_the_balance_is_used(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();

        $this->submit($coordinator, $store, $day, ['amount_cash' => '4000.00']);
        $this->submit($coordinator, $store, $day, ['amount_cash' => '5000.00']);

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $day, [
            'amount_cash' => '1000.01',
        ]), 'amount_cash');

        $this->submit($coordinator, $store, $day, ['amount_cash' => '1000.00']);

        $this->assertDatabaseCount('store_remittances', 3);
    }

    public function test_a_rejected_remission_frees_its_amount(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();
        $first = $this->submit($coordinator, $store, $day, ['amount_cash' => '10000.00']);

        $this->reject($first);

        $second = $this->submit($coordinator, $store, $day, ['amount_cash' => '10000.00']);

        $this->assertSame(StoreRemittance::STATUS_PENDING, $second->status);
    }

    public function test_a_confirmed_remission_still_counts_toward_the_balance(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();
        $first = $this->submit($coordinator, $store, $day, ['amount_cash' => '10000.00']);

        $this->confirm($first);

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $day, [
            'amount_cash' => '1.00',
        ]), 'amount_cash');
    }

    public function test_a_rejected_remission_can_be_resubmitted(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();
        $first = $this->submit($coordinator, $store, $day, ['amount_cash' => '2000.00']);
        $this->reject($first);

        $second = $this->submit($coordinator, $store, $day, [
            'amount_cash' => '2000.00',
            'resubmits_id' => $first->id,
        ]);

        $this->assertSame($first->id, $second->resubmits_id);
        $this->assertSame(StoreRemittance::STATUS_PENDING, $second->status);
        $this->assertSame(StoreRemittance::STATUS_REJECTED, $first->fresh()->status);
    }

    public function test_a_rejected_remission_can_only_be_resubmitted_once(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();
        $first = $this->submit($coordinator, $store, $day, ['amount_cash' => '2000.00']);
        $this->reject($first);

        $this->submit($coordinator, $store, $day, [
            'amount_cash' => '2000.00',
            'resubmits_id' => $first->id,
        ]);

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $day, [
            'amount_cash' => '2000.00',
            'resubmits_id' => $first->id,
        ]), 'resubmits_id');
    }

    public function test_pending_and_confirmed_remissions_cannot_be_resubmitted(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();
        $pending = $this->submit($coordinator, $store, $day, ['amount_cash' => '1000.00']);
        $confirmed = $this->submit($coordinator, $store, $day, ['amount_cash' => '1000.00']);
        $this->confirm($confirmed);

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $day, [
            'amount_cash' => '500.00',
            'resubmits_id' => $pending->id,
        ]), 'resubmits_id');

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $day, [
            'amount_cash' => '500.00',
            'resubmits_id' => $confirmed->id,
        ]), 'resubmits_id');
    }

    public function test_resubmission_must_reference_a_rejected_remission_of_the_same_day(): void
    {
        [$store, $coordinator, $day] = $this->closedStoreDay();
        $earlierDay = $this->closeDay($store, $coordinator, today()->subDay()->toDateString());

        $earlier = $this->submit($coordinator, $store, $earlierDay, ['amount_cash' => '1000.00']);
        $this->reject($earlier);

        $this->assertInvalid(fn () => $this->submit($coordinator, $store, $day, [
            'amount_cash' => '1000.00',
            'resubmits_id' => $earlier->id,
        ]), 'resubmits_id');
    }

    // ---------------------------------------------------------------- helpers

    private function submit(User $actor, Store $store, StoreDailyCashRecord $day, array $overrides = []): StoreRemittance
    {
        return app(SubmitStoreRemittance::class)->execute($actor, $store, array_merge([
            'store_daily_cash_record_id' => $day->id,
            'amount_cash' => '1000.00',
            'amount_bank' => '0',
            'method' => StoreRemittance::METHOD_CASH_HANDOVER,
        ], $overrides));
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

    private function reject(StoreRemittance $remittance): void
    {
        $remittance->forceFill([
            'status' => StoreRemittance::STATUS_REJECTED,
            'reviewed_by' => User::factory()->create(['is_active' => true])->id,
            'reviewed_at' => now(),
            'review_reason' => 'Amount does not match the handover.',
        ])->save();
    }

    private function confirm(StoreRemittance $remittance): void
    {
        $remittance->forceFill([
            'status' => StoreRemittance::STATUS_CONFIRMED,
            'reviewed_by' => User::factory()->create(['is_active' => true])->id,
            'reviewed_at' => now(),
        ])->save();
    }

    private function closedStoreDay(): array
    {
        $store = $this->makeStore();
        $coordinator = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        $day = $this->closeDay($store, $coordinator, today()->toDateString());

        return [$store, $coordinator, $day];
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
            'name' => 'Remittance Test Store '.str()->random(8),
            'code' => strtoupper(fake()->unique()->bothify('RM###??')),
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
