<?php

namespace Tests\Feature\Stores;

use App\Models\Store;
use App\Models\StoreExpense;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_warehouse_takes_the_general_warehouse_slot_and_a_store_does_not(): void
    {
        $warehouse = $this->makeStore(['type' => Store::TYPE_WAREHOUSE]);
        $branch = $this->makeStore(['type' => Store::TYPE_STORE]);

        $this->assertEquals(1, $warehouse->fresh()->general_warehouse_slot);
        $this->assertNull($branch->fresh()->general_warehouse_slot);
        $this->assertTrue($warehouse->isWarehouse());
        $this->assertFalse($branch->isWarehouse());
    }

    public function test_store_relationships_expose_only_active_staff(): void
    {
        $store = $this->makeStore();

        $coordinator = $this->assign($store, User::factory()->create(), StoreStaff::ROLE_COORDINATOR);
        $assistant = $this->assign($store, User::factory()->create(), StoreStaff::ROLE_ASSISTANT);
        $this->assign($store, User::factory()->create(), StoreStaff::ROLE_ASSISTANT, [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'ended_reason' => 'Left the business',
        ]);

        $this->assertSame(3, $store->staffAssignments()->count());
        $this->assertSame(2, $store->activeStaff()->count());
        $this->assertTrue($store->coordinatorAssignment->is($coordinator));
        $this->assertSame([$assistant->id], $store->assistantAssignments()->pluck('id')->all());
    }

    public function test_assignment_slots_follow_role_and_end_date(): void
    {
        $store = $this->makeStore();

        $coordinator = $this->assign($store, User::factory()->create(), StoreStaff::ROLE_COORDINATOR);
        $assistant = $this->assign($store, User::factory()->create(), StoreStaff::ROLE_ASSISTANT);

        $this->assertEquals(1, $coordinator->fresh()->active_user_slot);
        $this->assertEquals(1, $coordinator->fresh()->coordinator_slot);
        $this->assertEquals(1, $assistant->fresh()->active_user_slot);
        $this->assertNull($assistant->fresh()->coordinator_slot);

        $coordinator->update([
            'ends_at' => now(),
            'ended_reason' => 'Moved to another store',
        ]);

        $this->assertNull($coordinator->fresh()->active_user_slot);
        $this->assertNull($coordinator->fresh()->coordinator_slot);

        $assistant->update(['role' => StoreStaff::ROLE_COORDINATOR]);

        $this->assertEquals(1, $assistant->fresh()->coordinator_slot);
    }

    public function test_user_exposes_assignment_history_and_current_assignment(): void
    {
        $user = User::factory()->create();

        $this->assign($this->makeStore(), $user, StoreStaff::ROLE_ASSISTANT, [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'ended_reason' => 'Moved to another store',
        ]);
        $current = $this->assign($this->makeStore(), $user, StoreStaff::ROLE_ASSISTANT);

        $this->assertSame(2, $user->storeAssignments()->count());
        $this->assertTrue($user->activeStoreAssignment->is($current));
    }

    public function test_store_exposes_daily_cash_records_with_money_and_date_casts(): void
    {
        $store = $this->makeStore();
        $user = User::factory()->create();

        $record = $store->dailyCashRecords()->create([
            'business_date' => '2026-11-20',
            'opening_cash' => '1250.5',
            'opening_bank' => '9000.00',
            'opened_by' => $user->id,
        ]);

        $this->assertSame(1, $store->dailyCashRecords()->count());
        $this->assertSame('2026-11-20', $record->fresh()->business_date->toDateString());
        $this->assertSame('1250.50', $record->fresh()->opening_cash);
        $this->assertSame('9000.00', $record->fresh()->opening_bank);
        $this->assertNull($record->fresh()->closing_cash);
        $this->assertTrue($record->fresh()->openedBy->is($user));
        $this->assertNull($record->fresh()->closedBy);
    }

    public function test_store_expenses_belong_to_the_store_daily_cash_record_and_recorder(): void
    {
        $store = $this->makeStore();
        $user = User::factory()->create();

        $dailyCash = $store->dailyCashRecords()->create([
            'business_date' => '2026-11-20',
            'opening_cash' => '1000.00',
            'opening_bank' => '5000.00',
            'opened_by' => $user->id,
        ]);

        $expense = $store->expenses()->create([
            'store_daily_cash_record_id' => $dailyCash->id,
            'amount' => '1250.5',
            'category' => StoreExpense::CATEGORY_TRANSPORTATION,
            'payment_method' => StoreExpense::PAYMENT_CASH,
            'description' => 'Transport for stock delivery',
            'recorded_by' => $user->id,
        ]);

        $this->assertSame(1, $store->expenses()->count());
        $this->assertSame('1250.50', $expense->fresh()->amount);
        $this->assertTrue($expense->fresh()->store->is($store));
        $this->assertTrue($expense->fresh()->dailyCashRecord->is($dailyCash));
        $this->assertTrue($expense->fresh()->recordedBy->is($user));
        $this->assertTrue($dailyCash->expenses()->first()->is($expense));
        $this->assertTrue($expense->countsTowardDailyThreshold());
    }

    public function test_goods_purchase_and_debt_repayment_are_excluded_from_the_daily_threshold(): void
    {
        $goodsPurchase = new StoreExpense([
            'category' => StoreExpense::CATEGORY_GOODS_PURCHASE,
        ]);

        $debtRepayment = new StoreExpense([
            'category' => StoreExpense::CATEGORY_DEBT_REPAYMENT,
        ]);

        $transportation = new StoreExpense([
            'category' => StoreExpense::CATEGORY_TRANSPORTATION,
        ]);

        $this->assertFalse($goodsPurchase->countsTowardDailyThreshold());
        $this->assertFalse($debtRepayment->countsTowardDailyThreshold());
        $this->assertTrue($transportation->countsTowardDailyThreshold());

        $this->assertSame([
            'transportation',
            'loading_offloading',
            'packaging',
            'utilities',
            'cleaning_maintenance',
            'store_supplies',
            'staff_expenses',
            'goods_purchase',
            'debt_repayment',
            'other',
        ], StoreExpense::CATEGORIES);

        $this->assertSame(['cash', 'pos', 'transfer'], StoreExpense::PAYMENT_METHODS);
    }

    private function makeStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
        ], $attributes));
    }

    private function assign(Store $store, User $user, string $role, array $attributes = []): StoreStaff
    {
        return StoreStaff::query()->create(array_merge([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now(),
        ], $attributes));
    }
}
