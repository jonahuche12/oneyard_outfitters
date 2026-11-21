<?php

namespace Tests\Feature\Stores;

use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreExpense;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreExpensesHttpTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    private function makeStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
        ], $attributes));
    }

    private function assign(Store $store, User $user, string $role): StoreStaff
    {
        return StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now(),
        ]);
    }

    private function openBusinessDay(Store $store, User $user): StoreDailyCashRecord
    {
        return StoreDailyCashRecord::query()->create([
            'store_id' => $store->id,
            'business_date' => today()->toDateString(),
            'opening_cash' => '10000.00',
            'opening_bank' => '20000.00',
            'opened_by' => $user->id,
        ]);
    }

    public function test_assigned_coordinator_can_view_expenses_and_record_an_expense(): void
    {
        $store = $this->makeStore();
        $user = $this->staffUser();

        $this->assign($store, $user, StoreStaff::ROLE_COORDINATOR);
        $this->openBusinessDay($store, $user);

        $this->actingAs($user)
            ->get(route('stores.expenses.index', $store))
            ->assertOk()
            ->assertSee('Record an expense')
            ->assertSee('Expense history');

        $this->actingAs($user)
            ->post(route('stores.expenses.store', $store), [
                'amount' => '1250.00',
                'category' => StoreExpense::CATEGORY_TRANSPORTATION,
                'payment_method' => StoreExpense::PAYMENT_CASH,
                'description' => 'Transport for store supplies',
            ])
            ->assertRedirect(route('stores.expenses.index', $store))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('store_expenses', [
            'store_id' => $store->id,
            'amount' => '1250.00',
            'category' => StoreExpense::CATEGORY_TRANSPORTATION,
            'payment_method' => StoreExpense::PAYMENT_CASH,
            'description' => 'Transport for store supplies',
            'recorded_by' => $user->id,
        ]);
    }

    public function test_assistant_can_record_expense_for_assigned_store(): void
    {
        $store = $this->makeStore();
        $user = $this->staffUser();

        $this->assign($store, $user, StoreStaff::ROLE_ASSISTANT);
        $this->openBusinessDay($store, $user);

        $this->actingAs($user)
            ->post(route('stores.expenses.store', $store), [
                'amount' => '500.00',
                'category' => StoreExpense::CATEGORY_PACKAGING,
                'payment_method' => StoreExpense::PAYMENT_POS,
                'description' => 'Packaging materials',
            ])
            ->assertRedirect(route('stores.expenses.index', $store));

        $this->assertDatabaseHas('store_expenses', [
            'store_id' => $store->id,
            'recorded_by' => $user->id,
            'description' => 'Packaging materials',
        ]);
    }

    public function test_expense_history_is_scoped_to_the_requested_store(): void
    {
        $store = $this->makeStore();
        $otherStore = $this->makeStore();
        $user = $this->staffUser();

        $this->assign($store, $user, StoreStaff::ROLE_COORDINATOR);

        $localDay = $this->openBusinessDay($store, $user);
        $otherDay = $this->openBusinessDay($otherStore, $user);

        StoreExpense::query()->create([
            'store_id' => $store->id,
            'store_daily_cash_record_id' => $localDay->id,
            'amount' => '300.00',
            'category' => StoreExpense::CATEGORY_UTILITIES,
            'payment_method' => StoreExpense::PAYMENT_TRANSFER,
            'description' => 'Local store electricity',
            'recorded_by' => $user->id,
        ]);

        StoreExpense::query()->create([
            'store_id' => $otherStore->id,
            'store_daily_cash_record_id' => $otherDay->id,
            'amount' => '900.00',
            'category' => StoreExpense::CATEGORY_UTILITIES,
            'payment_method' => StoreExpense::PAYMENT_CASH,
            'description' => 'Other store electricity',
            'recorded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('stores.expenses.index', $store))
            ->assertOk()
            ->assertSee('Local store electricity')
            ->assertDontSee('Other store electricity');
    }

    public function test_expense_recording_is_unavailable_after_business_day_is_closed(): void
    {
        $store = $this->makeStore();
        $user = $this->staffUser();

        $this->assign($store, $user, StoreStaff::ROLE_COORDINATOR);

        $day = $this->openBusinessDay($store, $user);
        $day->forceFill([
            'closing_cash' => '8000.00',
            'closing_bank' => '20000.00',
            'closed_by' => $user->id,
        ])->save();

        $this->actingAs($user)
            ->get(route('stores.expenses.index', $store))
            ->assertOk()
            ->assertSee('Business day closed')
            ->assertDontSee('Record an expense');

        $this->actingAs($user)
            ->post(route('stores.expenses.store', $store), [
                'amount' => '100.00',
                'category' => StoreExpense::CATEGORY_OTHER,
                'payment_method' => StoreExpense::PAYMENT_CASH,
                'description' => 'Attempt after closing',
            ])
            ->assertSessionHasErrors('business_day');

        $this->assertDatabaseMissing('store_expenses', [
            'store_id' => $store->id,
            'description' => 'Attempt after closing',
        ]);
    }

    public function test_expense_recording_is_rejected_before_opening_balances_are_recorded(): void
    {
        $store = $this->makeStore();
        $user = $this->staffUser();

        $this->assign($store, $user, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($user)
            ->get(route('stores.expenses.index', $store))
            ->assertOk()
            ->assertSee('Opening balances not recorded')
            ->assertDontSee('Record an expense');

        $this->actingAs($user)
            ->post(route('stores.expenses.store', $store), [
                'amount' => '100.00',
                'category' => StoreExpense::CATEGORY_OTHER,
                'payment_method' => StoreExpense::PAYMENT_CASH,
                'description' => 'Attempt before opening',
            ])
            ->assertSessionHasErrors('business_day');

        $this->assertDatabaseMissing('store_expenses', [
            'store_id' => $store->id,
            'description' => 'Attempt before opening',
        ]);
    }

    public function test_unassigned_user_cannot_access_store_expenses(): void
    {
        $store = $this->makeStore();
        $user = $this->staffUser();

        $this->actingAs($user)
            ->get(route('stores.expenses.index', $store))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('stores.expenses.store', $store), [])
            ->assertForbidden();
    }

    public function test_inactive_store_cannot_be_used_for_expense_management(): void
    {
        $store = $this->makeStore(['is_active' => false]);
        $user = $this->staffUser();

        $this->assign($store, $user, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($user)
            ->get(route('stores.expenses.index', $store))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $store = $this->makeStore();

        $this->get(route('stores.expenses.index', $store))
            ->assertRedirect(route('login'));
    }
}
