<?php

namespace Tests\Feature\Stores;

use App\Actions\Stores\RecordStoreExpense;
use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreExpense;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_coordinator_can_record_an_expense_for_the_open_business_day(): void
    {
        [$store, $coordinator, $day] = $this->openStoreDay();

        $result = app(RecordStoreExpense::class)->execute(
            $coordinator,
            $store,
            $this->expenseData()
        );

        $expense = $result['expense'];

        $this->assertInstanceOf(StoreExpense::class, $expense);
        $this->assertSame($store->id, $expense->store_id);
        $this->assertSame($day->id, $expense->store_daily_cash_record_id);
        $this->assertSame($coordinator->id, $expense->recorded_by);
        $this->assertSame('1500.00', $expense->amount);
        $this->assertSame('transportation', $expense->category);
        $this->assertSame('cash', $expense->payment_method);
        $this->assertSame('Transport for stock collection', $expense->description);
        $this->assertSame('1500.00', $result['qualifying_total']);
        $this->assertFalse($result['threshold_exceeded']);

        $this->assertDatabaseCount('store_expenses', 1);
    }

    public function test_assigned_assistant_can_record_an_expense(): void
    {
        [$store, $coordinator] = $this->openStoreDay();
        $assistant = $this->assign($store, StoreStaff::ROLE_ASSISTANT);

        $result = app(RecordStoreExpense::class)->execute(
            $assistant,
            $store,
            $this->expenseData([
                'amount' => '250.00',
                'payment_method' => 'transfer',
            ])
        );

        $this->assertSame($assistant->id, $result['expense']->recorded_by);
        $this->assertSame('250.00', $result['qualifying_total']);
        $this->assertFalse($result['threshold_exceeded']);
        $this->assertNotSame($coordinator->id, $assistant->id);
    }

    public function test_threshold_warning_starts_only_when_qualifying_total_exceeds_five_thousand(): void
    {
        [$store, $coordinator] = $this->openStoreDay();
        $action = app(RecordStoreExpense::class);

        $first = $action->execute($coordinator, $store, $this->expenseData([
            'amount' => '4999.99',
        ]));

        $this->assertSame('4999.99', $first['qualifying_total']);
        $this->assertFalse($first['threshold_exceeded']);

        $second = $action->execute($coordinator, $store, $this->expenseData([
            'amount' => '0.01',
            'description' => 'Second expense',
        ]));

        $this->assertSame('5000.00', $second['qualifying_total']);
        $this->assertFalse($second['threshold_exceeded']);

        $third = $action->execute($coordinator, $store, $this->expenseData([
            'amount' => '0.01',
            'description' => 'Threshold crossing expense',
        ]));

        $this->assertSame('5000.01', $third['qualifying_total']);
        $this->assertTrue($third['threshold_exceeded']);

        $fourth = $action->execute($coordinator, $store, $this->expenseData([
            'amount' => '10.00',
            'description' => 'Another qualifying expense',
        ]));

        $this->assertSame('5010.01', $fourth['qualifying_total']);
        $this->assertTrue($fourth['threshold_exceeded']);
        $this->assertDatabaseCount('store_expenses', 4);
    }

    public function test_goods_purchase_and_debt_repayment_are_saved_but_excluded_from_threshold(): void
    {
        [$store, $coordinator] = $this->openStoreDay();
        $action = app(RecordStoreExpense::class);

        $qualifying = $action->execute($coordinator, $store, $this->expenseData([
            'amount' => '5001.00',
        ]));

        $this->assertTrue($qualifying['threshold_exceeded']);

        foreach ([
            StoreExpense::CATEGORY_GOODS_PURCHASE,
            StoreExpense::CATEGORY_DEBT_REPAYMENT,
        ] as $category) {
            $result = $action->execute($coordinator, $store, $this->expenseData([
                'amount' => '2000.00',
                'category' => $category,
                'description' => 'Excluded category expense',
            ]));

            $this->assertSame($category, $result['expense']->category);
            $this->assertSame('5001.00', $result['qualifying_total']);
            $this->assertFalse($result['threshold_exceeded']);
        }

        $this->assertDatabaseCount('store_expenses', 3);
    }

    public function test_expense_cannot_be_recorded_before_opening_or_after_closing(): void
    {
        $store = $this->makeStore();
        $coordinator = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        $action = app(RecordStoreExpense::class);

        try {
            $action->execute($coordinator, $store, $this->expenseData());
            $this->fail('An expense must not be recorded before opening the day.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('business_day', $exception->errors());
        }

        $day = $this->createOpenDay($store, $coordinator);
        $day->update([
            'closing_cash' => '100.00',
            'closing_bank' => '200.00',
            'closed_by' => $coordinator->id,
        ]);

        try {
            $action->execute($coordinator, $store, $this->expenseData());
            $this->fail('An expense must not be recorded after closing the day.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('business_day', $exception->errors());
        }

        $this->assertDatabaseCount('store_expenses', 0);
    }

    public function test_unassigned_user_cannot_record_an_expense(): void
    {
        [$store] = $this->openStoreDay();
        $outsider = User::factory()->create(['is_active' => true]);

        $this->expectException(AuthorizationException::class);

        app(RecordStoreExpense::class)->execute(
            $outsider,
            $store,
            $this->expenseData()
        );
    }

    public function test_inactive_user_and_inactive_store_cannot_record_expenses(): void
    {
        $store = $this->makeStore();
        $inactiveUser = $this->assign(
            $store,
            StoreStaff::ROLE_COORDINATOR,
            ['is_active' => false]
        );

        $this->createOpenDay($store, $inactiveUser);

        try {
            app(RecordStoreExpense::class)->execute(
                $inactiveUser,
                $store,
                $this->expenseData()
            );
            $this->fail('An inactive user must not record expenses.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('store_expenses', 0);
        }

        $activeStore = $this->makeStore(['is_active' => false]);
        $activeUser = $this->assign(
            $activeStore,
            StoreStaff::ROLE_COORDINATOR
        );
        $this->createOpenDay($activeStore, $activeUser);

        try {
            app(RecordStoreExpense::class)->execute(
                $activeUser,
                $activeStore,
                $this->expenseData()
            );
            $this->fail('An inactive store must not accept expenses.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('store_expenses', 0);
        }
    }

    public function test_invalid_expense_fields_are_rejected_without_creating_records(): void
    {
        [$store, $coordinator] = $this->openStoreDay();
        $action = app(RecordStoreExpense::class);

        $invalidCases = [
            ['amount' => '0'],
            ['amount' => '-10.00'],
            ['amount' => '1.999'],
            ['amount' => '10000000000000.00'],
            ['category' => 'unapproved_category'],
            ['payment_method' => 'cheque'],
            ['description' => ''],
        ];

        foreach ($invalidCases as $overrides) {
            try {
                $action->execute(
                    $coordinator,
                    $store,
                    $this->expenseData($overrides)
                );

                $this->fail('Invalid expense data should have been rejected.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('store_expenses', 0);
            }
        }
    }

    private function openStoreDay(): array
    {
        $store = $this->makeStore();
        $coordinator = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        $day = $this->createOpenDay($store, $coordinator);

        return [$store, $coordinator, $day];
    }

    private function makeStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'name' => 'Expense Test Store '.str()->random(8),
            'code' => strtoupper(fake()->unique()->bothify('EX###??')),
            'is_active' => true,
        ], $attributes));
    }

    private function assign(
        Store $store,
        string $role,
        array $userAttributes = []
    ): User {
        $user = User::factory()->create(array_merge([
            'is_active' => true,
        ], $userAttributes));

        StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now()->subMinute(),
        ]);

        return $user;
    }

    private function createOpenDay(
        Store $store,
        User $opener
    ): StoreDailyCashRecord {
        return $store->dailyCashRecords()->create([
            'business_date' => today()->toDateString(),
            'opening_cash' => '1000.00',
            'opening_bank' => '5000.00',
            'opened_by' => $opener->id,
        ]);
    }

    private function expenseData(array $overrides = []): array
    {
        return array_merge([
            'amount' => '1500.00',
            'category' => StoreExpense::CATEGORY_TRANSPORTATION,
            'payment_method' => StoreExpense::PAYMENT_CASH,
            'description' => 'Transport for stock collection',
        ], $overrides);
    }
}
