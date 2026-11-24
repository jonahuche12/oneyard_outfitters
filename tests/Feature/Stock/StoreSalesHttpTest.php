<?php

namespace Tests\Feature\Stock;

use Carbon\Carbon;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StockSale;
use App\Models\StockSalePayment;
use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreExpense;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreSalesHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_sales_pages(): void
    {
        $store = $this->makeStore();

        $this->get(route('stores.sales.index', $store))->assertRedirect();
        $this->get(route('stores.sales.outstanding', $store))->assertRedirect();
    }

    public function test_assigned_assistant_can_view_store_sales(): void
    {
        $store = $this->makeStore();
        $assistant = User::factory()->create(['is_active' => true]);

        $this->assign($store, $assistant, StoreStaff::ROLE_ASSISTANT);
        $sale = $this->makeSale($store, $assistant);

        $this->actingAs($assistant)
            ->get(route('stores.sales.index', $store))
            ->assertOk()
            ->assertSee($sale->sale_number);
    }

    public function test_sales_history_only_shows_sales_for_the_selected_store(): void
    {
        $storeA = $this->makeStore();
        $storeB = $this->makeStore();

        $coordinator = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);

        $this->assign($storeA, $coordinator, StoreStaff::ROLE_COORDINATOR);
        $this->assign($storeB, $otherUser, StoreStaff::ROLE_COORDINATOR);

        $localSale = $this->makeSale($storeA, $coordinator);
        $foreignSale = $this->makeSale($storeB, $otherUser);

        $this->actingAs($coordinator)
            ->get(route('stores.sales.index', $storeA))
            ->assertOk()
            ->assertSee($localSale->sale_number)
            ->assertDontSee($foreignSale->sale_number);
    }

    public function test_user_without_active_assignment_cannot_view_sales(): void
    {
        $store = $this->makeStore();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('stores.sales.index', $store))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('stores.sales.outstanding', $store))
            ->assertForbidden();
    }

    public function test_manager_without_store_assignment_cannot_view_sales(): void
    {
        $store = $this->makeStore();
        $slug = 'sales-manager-'.Str::lower(Str::random(8));

        $role = Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'description' => 'Test role.']
        );

        $permission = Permission::firstOrCreate(
            ['slug' => 'stores.manage'],
            ['name' => 'Manage Stores', 'description' => 'Test.']
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $manager = User::factory()->create(['is_active' => true]);
        $manager->roles()->attach($role);

        $this->actingAs($manager)
            ->get(route('stores.sales.index', $store))
            ->assertForbidden();
    }

    public function test_outstanding_page_only_shows_sales_with_a_remaining_balance(): void
    {
        $store = $this->makeStore();
        $coordinator = User::factory()->create(['is_active' => true]);

        $this->assign($store, $coordinator, StoreStaff::ROLE_COORDINATOR);

        $outstandingSale = $this->makeSale($store, $coordinator, 'SALE-OUTSTANDING');
        $paidSale = $this->makeSale($store, $coordinator, 'SALE-PAID');

        StockSalePayment::query()->create([
            'stock_sale_id' => $outstandingSale->id,
            'amount' => 40,
            'payment_method' => 'cash',
            'paid_at' => now(),
            'received_by' => $coordinator->id,
        ]);

        StockSalePayment::query()->create([
            'stock_sale_id' => $paidSale->id,
            'amount' => 100,
            'payment_method' => 'cash',
            'paid_at' => now(),
            'received_by' => $coordinator->id,
        ]);

        $this->actingAs($coordinator)
            ->get(route('stores.sales.outstanding', $store))
            ->assertOk()
            ->assertSee($outstandingSale->sale_number)
            ->assertDontSee($paidSale->sale_number);
    }


    public function test_assigned_staff_can_record_a_payment_from_the_sales_history(): void
    {
        $store = $this->makeStore();
        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($store, $coordinator, StoreStaff::ROLE_COORDINATOR);
        $sale = $this->makeSale($store, $coordinator);

        $this->actingAs($coordinator)
            ->post(route('stores.sales.payments.store', [$store, $sale]), [
                'amount' => 35,
                'payment_method' => 'cash',
            ])
            ->assertRedirect(route('stores.sales.index', $store).'#sale-'.$sale->id);

        $this->assertDatabaseHas('stock_sale_payments', [
            'stock_sale_id' => $sale->id,
            'amount' => 35,
            'tip_amount' => 0,
            'payment_method' => 'cash',
            'received_by' => $coordinator->id,
        ]);
    }

    public function test_staff_cannot_record_payment_for_another_stores_sale(): void
    {
        $storeA = $this->makeStore();
        $storeB = $this->makeStore();

        $coordinator = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);

        $this->assign($storeA, $coordinator, StoreStaff::ROLE_COORDINATOR);
        $this->assign($storeB, $otherUser, StoreStaff::ROLE_COORDINATOR);

        $foreignSale = $this->makeSale($storeB, $otherUser);

        $this->actingAs($coordinator)
            ->post(route('stores.sales.payments.store', [$storeA, $foreignSale]), [
                'amount' => 20,
                'payment_method' => 'cash',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('stock_sale_payments', [
            'stock_sale_id' => $foreignSale->id,
        ]);
    }

    public function test_sales_history_renders_sale_detail_dialog_and_payment_form(): void
    {
        $store = $this->makeStore();
        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($store, $coordinator, StoreStaff::ROLE_COORDINATOR);
        $sale = $this->makeSale($store, $coordinator);

        $this->actingAs($coordinator)
            ->get(route('stores.sales.index', $store))
            ->assertOk()
            ->assertSee('data-sale-open="'.$sale->id.'"', false)
            ->assertSee('id="sale-'.$sale->id.'"', false)
            ->assertSee('Payments')
            ->assertSee('Record payment')
            ->assertSee('bank_transfer');
    }

    public function test_sales_dashboard_uses_actual_collections_and_deducts_all_today_expenses(): void
    {
        Carbon::setTestNow('2026-11-20 12:00:00');

        try {
            $store = $this->makeStore();
            $otherStore = $this->makeStore();

            $coordinator = User::factory()->create(['is_active' => true]);
            $otherCoordinator = User::factory()->create(['is_active' => true]);

            $this->assign($store, $coordinator, StoreStaff::ROLE_COORDINATOR);
            $this->assign($otherStore, $otherCoordinator, StoreStaff::ROLE_COORDINATOR);

            $todaySale = $this->makeSale($store, $coordinator, 'SALE-TODAY');
            $yesterdaySale = $this->makeSale($store, $coordinator, 'SALE-YESTERDAY');
            $sixDaysAgoSale = $this->makeSale($store, $coordinator, 'SALE-SIX-DAYS');
            $twentySevenDaysAgoSale = $this->makeSale($store, $coordinator, 'SALE-TWENTY-SEVEN-DAYS');
            $twentyEightDaysAgoSale = $this->makeSale($store, $coordinator, 'SALE-TWENTY-EIGHT-DAYS');
            $foreignSale = $this->makeSale($otherStore, $otherCoordinator, 'SALE-FOREIGN');

            $payments = [
                [$todaySale, 25, '2026-11-20 09:00:00'],
                [$yesterdaySale, 30, '2026-11-19 09:00:00'],
                [$sixDaysAgoSale, 40, '2026-11-14 09:00:00'],
                [$twentySevenDaysAgoSale, 50, '2026-10-24 09:00:00'],
                [$twentyEightDaysAgoSale, 60, '2026-10-23 09:00:00'],
                [$foreignSale, 999, '2026-11-20 09:00:00'],
            ];

            foreach ($payments as [$sale, $amount, $paidAt]) {
                StockSalePayment::query()->create([
                    'stock_sale_id' => $sale->id,
                    'amount' => $amount,
                    'tip_amount' => 5,
                    'payment_method' => 'cash',
                    'paid_at' => $paidAt,
                    'received_by' => $sale->created_by,
                ]);
            }

            $dailyCash = StoreDailyCashRecord::query()->create([
                'store_id' => $store->id,
                'business_date' => '2026-11-20',
                'opening_cash' => 100,
                'opening_bank' => 200,
                'opened_by' => $coordinator->id,
            ]);

            foreach ([
                [10, StoreExpense::CATEGORY_TRANSPORTATION],
                [20, StoreExpense::CATEGORY_GOODS_PURCHASE],
                [30, StoreExpense::CATEGORY_DEBT_REPAYMENT],
            ] as [$amount, $category]) {
                StoreExpense::query()->create([
                    'store_id' => $store->id,
                    'store_daily_cash_record_id' => $dailyCash->id,
                    'amount' => $amount,
                    'category' => $category,
                    'payment_method' => 'cash',
                    'description' => 'Dashboard test expense',
                    'recorded_by' => $coordinator->id,
                ]);
            }

            $response = $this->actingAs($coordinator)
                ->get(route('stores.sales.index', $store))
                ->assertOk();

            $response->assertViewHas('salesDashboard', function (array $dashboard): bool {
                return $dashboard['today_sales'] === '25.00'
                    && $dashboard['yesterday_sales'] === '30.00'
                    && $dashboard['last_7_days_sales'] === '95.00'
                    && $dashboard['last_28_days_sales'] === '145.00'
                    && $dashboard['today_sales_change'] === '-5.00'
                    && $dashboard['previous_7_days_sales'] === '0.00'
                    && $dashboard['last_7_days_change'] === '95.00'
                    && $dashboard['previous_28_days_sales'] === '60.00'
                    && $dashboard['last_28_days_change'] === '85.00'
                    && $dashboard['opening_balance'] === '300.00'
                    && $dashboard['today_expenses'] === '60.00'
                    && $dashboard['expected_revenue'] === '325.00'
                    && $dashboard['expected_net_revenue'] === '265.00'
                    && $dashboard['opening_cash'] === '100.00'
                    && $dashboard['opening_bank'] === '200.00'
                    && $dashboard['intraday_funds_cash'] === '0.00'
                    && $dashboard['intraday_funds_bank'] === '0.00'
                    && $dashboard['today_sales_cash'] === '25.00'
                    && $dashboard['today_sales_bank'] === '0.00'
                    && $dashboard['expenses_cash'] === '60.00'
                    && $dashboard['expenses_bank'] === '0.00'
                    && $dashboard['expected_cash'] === '65.00'
                    && $dashboard['expected_bank'] === '200.00'
                    && $dashboard['expected_total'] === '265.00'
                    && $dashboard['is_closed'] === false
                    && $dashboard['closing_cash'] === null
                    && $dashboard['closing_bank'] === null
                    && $dashboard['closing_total'] === null;
            });

            $this->actingAs($coordinator)
                ->get(route('stores.sales.outstanding', $store))
                ->assertOk()
                ->assertViewHas('salesDashboard.expected_net_revenue', '265.00');
        } finally {
            Carbon::setTestNow();
        }
    }

    private function makeStore(): Store
    {
        return Store::query()->create([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
        ]);
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

    private function makeSale(
        Store $store,
        User $creator,
        ?string $number = null
    ): StockSale {
        return StockSale::query()->create([
            'store_id' => $store->id,
            'sale_number' => $number ?? 'SALE-'.Str::upper(Str::random(12)),
            'customer_name' => 'Test Customer',
            'customer_phone' => '08030000000',
            'total_amount' => 100,
            'sold_at' => now(),
            'created_by' => $creator->id,
        ]);
    }
}
