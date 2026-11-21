<?php

namespace App\Http\Controllers;

use App\Actions\Stock\RecordStockSale;
use App\Actions\Stock\RecordStockSalePayment;
use App\Models\Customer;
use App\Models\StockLevel;
use App\Models\StockSale;
use App\Models\StockSalePayment;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreExpense;
use App\Models\Store;
use App\Models\StoreStaff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class StoreSalesController extends Controller
{

    public function create(Request $request, Store $store): View
    {
        $this->authorizeStoreAccess($request, $store);
        $canRecordSales = $this->businessDayIsOpen($store);

        abort_unless(
            $canRecordSales,
            403,
            'Record opening balances before recording sales. Sales are unavailable after the business day is closed.'
        );

        $stockLevels = StockLevel::query()
            ->where('store_id', $store->id)
            ->where('quantity', '>', 0)
            ->with('item')
            ->whereHas('item')
            ->orderBy('stock_item_id')
            ->get();

        $customers = Customer::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $requestedCustomerId = $request->integer('customer_id');
        $selectedCustomerId = $customers->contains('id', $requestedCustomerId)
            ? $requestedCustomerId
            : null;

        return view('stores.sales.create', [
            'store' => $store,
            'stockLevels' => $stockLevels,
            'customers' => $customers,
            'selectedCustomerId' => $selectedCustomerId,
        ]);
    }

    public function store(
        Request $request,
        Store $store,
        RecordStockSale $recordStockSale
    ): RedirectResponse {
        $this->authorizeStoreAccess($request, $store);

        $sale = $recordStockSale->execute(
            $request->user(),
            $store,
            $request->all()
        );

        return redirect()
            ->route('stores.sales.index', $store)
            ->with('success', "Sale {$sale->sale_number} recorded successfully.");
    }

    public function index(Request $request, Store $store): View|Response
    {
        $this->authorizeStoreAccess($request, $store);
        $salesFilters = $this->validatedSalesFilters($request);

        $salesQuery = StockSale::query()
            ->where('store_id', $store->id);
        $this->applySalesDateFilters($salesQuery, $salesFilters);

        $sales = $salesQuery
            ->with([
                'customer',
                'creator',
                'items.stockItem.photos' => fn ($query) => $query->orderBy('id'),
                'payments.receiver',
            ])
            ->withSum('payments', 'amount')
            ->latest('sold_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            return response()
                ->view('stores.sales.partials.results', [
                    'store' => $store,
                    'sales' => $sales,
                    'outstandingOnly' => false,
                ])
                ->header('Vary', 'X-Requested-With', false);
        }

        return response()
            ->view('stores.sales.index', [
                'store' => $store,
                'sales' => $sales,
                'outstandingOnly' => false,
                'canRecordSales' => $this->businessDayIsOpen($store),
                'salesDashboard' => $this->salesDashboard($store),
                'salesFilters' => $salesFilters,
            ])
            ->header('Vary', 'X-Requested-With', false);
    }

    public function outstanding(Request $request, Store $store): View|Response
    {
        $this->authorizeStoreAccess($request, $store);
        $salesFilters = $this->validatedSalesFilters($request);

        $salesQuery = StockSale::query()
            ->where('store_id', $store->id);
        $this->applySalesDateFilters($salesQuery, $salesFilters);

        $sales = $salesQuery
            ->whereRaw(
                'stock_sales.total_amount > (
                    select coalesce(sum(stock_sale_payments.amount), 0)
                    from stock_sale_payments
                    where stock_sale_payments.stock_sale_id = stock_sales.id
                )'
            )
            ->with([
                'customer',
                'creator',
                'items.stockItem.photos' => fn ($query) => $query->orderBy('id'),
                'payments.receiver',
            ])
            ->withSum('payments', 'amount')
            ->latest('sold_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            return response()
                ->view('stores.sales.partials.results', [
                    'store' => $store,
                    'sales' => $sales,
                    'outstandingOnly' => true,
                ])
                ->header('Vary', 'X-Requested-With', false);
        }

        return response()
            ->view('stores.sales.index', [
                'store' => $store,
                'sales' => $sales,
                'outstandingOnly' => true,
                'canRecordSales' => $this->businessDayIsOpen($store),
                'salesDashboard' => $this->salesDashboard($store),
                'salesFilters' => $salesFilters,
            ])
            ->header('Vary', 'X-Requested-With', false);
    }

    public function payment(
        Request $request,
        Store $store,
        StockSale $sale,
        RecordStockSalePayment $recordStockSalePayment
    ): RedirectResponse {
        $this->authorizeStoreAccess($request, $store);

        $recordStockSalePayment->execute(
            $request->user(),
            $store,
            $sale,
            $request->all()
        );

        return redirect()->to(
            route('stores.sales.index', $store).'#sale-'.$sale->id
        )->with('success', 'Sale payment recorded successfully.');
    }

    private function authorizeStoreAccess(Request $request, Store $store): void
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && $store->is_active, 403);

        abort_unless(
            StoreStaff::query()
                ->active()
                ->where('user_id', $user->id)
                ->where('store_id', $store->id)
                ->whereIn('role', [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ])
                ->exists(),
            403
        );
    }

    private function validatedSalesFilters(Request $request): array
    {
        $filters = $request->validate([
            'sales_date' => ['nullable', 'date_format:Y-m-d'],
            'sales_from' => [
                'nullable',
                'required_with:sales_to',
                'date_format:Y-m-d',
            ],
            'sales_to' => [
                'nullable',
                'required_with:sales_from',
                'date_format:Y-m-d',
                'after_or_equal:sales_from',
            ],
        ]);

        if (
            filled($filters['sales_date'] ?? null)
            && (
                filled($filters['sales_from'] ?? null)
                || filled($filters['sales_to'] ?? null)
            )
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'sales_date' => 'Choose one date or a date range, not both.',
            ]);
        }

        return $filters;
    }

    private function applySalesDateFilters(Builder $query, array $filters): void
    {
        if (filled($filters['sales_date'] ?? null)) {
            $query->whereDate('sold_at', $filters['sales_date']);

            return;
        }

        if (
            filled($filters['sales_from'] ?? null)
            && filled($filters['sales_to'] ?? null)
        ) {
            $from = Carbon::createFromFormat('Y-m-d', $filters['sales_from'])
                ->startOfDay();

            $to = Carbon::createFromFormat('Y-m-d', $filters['sales_to'])
                ->endOfDay();

            $query->whereBetween('sold_at', [$from, $to]);
        }
    }

    private function salesDashboard(Store $store): array
    {
        $today = today();

        $todayStart = $today->copy()->startOfDay();
        $todayEnd = $today->copy()->endOfDay();

        $yesterdayStart = $today->copy()->subDay()->startOfDay();
        $yesterdayEnd = $today->copy()->subDay()->endOfDay();

        $sevenDayStart = $today->copy()->subDays(6)->startOfDay();
        $previousSevenDayStart = $today->copy()->subDays(13)->startOfDay();
        $previousSevenDayEnd = $today->copy()->subDays(7)->endOfDay();

        $twentyEightDayStart = $today->copy()->subDays(27)->startOfDay();
        $previousTwentyEightDayStart = $today->copy()->subDays(55)->startOfDay();
        $previousTwentyEightDayEnd = $today->copy()->subDays(28)->endOfDay();

        $paymentsReceived = function ($start, $end) use ($store): string {
            return number_format(
                (float) StockSalePayment::query()
                    ->whereHas('sale', fn ($query) => $query->where('store_id', $store->id))
                    ->whereBetween('paid_at', [$start, $end])
                    ->sum('amount'),
                2,
                '.',
                ''
            );
        };

        $dailyCash = StoreDailyCashRecord::query()
            ->where('store_id', $store->id)
            ->whereDate('business_date', $today->toDateString())
            ->first();

        $openingBalance = $dailyCash
            ? bcadd((string) $dailyCash->opening_cash, (string) $dailyCash->opening_bank, 2)
            : '0.00';

        $todaySales = $paymentsReceived($todayStart, $todayEnd);
        $yesterdaySales = $paymentsReceived($yesterdayStart, $yesterdayEnd);

        $lastSevenDaysSales = $paymentsReceived($sevenDayStart, $todayEnd);
        $previousSevenDaysSales = $paymentsReceived(
            $previousSevenDayStart,
            $previousSevenDayEnd
        );

        $lastTwentyEightDaysSales = $paymentsReceived($twentyEightDayStart, $todayEnd);
        $previousTwentyEightDaysSales = $paymentsReceived(
            $previousTwentyEightDayStart,
            $previousTwentyEightDayEnd
        );

        $todayExpenses = $dailyCash
            ? number_format(
                (float) StoreExpense::query()
                    ->where('store_id', $store->id)
                    ->where('store_daily_cash_record_id', $dailyCash->id)
                    ->sum('amount'),
                2,
                '.',
                ''
            )
            : '0.00';

        $expectedRevenue = bcadd($openingBalance, $todaySales, 2);
        $expectedNetRevenue = bcsub($expectedRevenue, $todayExpenses, 2);

        return [
            'today_sales' => $todaySales,
            'yesterday_sales' => $yesterdaySales,
            'today_sales_change' => bcsub($todaySales, $yesterdaySales, 2),
            'last_7_days_sales' => $lastSevenDaysSales,
            'previous_7_days_sales' => $previousSevenDaysSales,
            'last_7_days_change' => bcsub($lastSevenDaysSales, $previousSevenDaysSales, 2),
            'last_28_days_sales' => $lastTwentyEightDaysSales,
            'previous_28_days_sales' => $previousTwentyEightDaysSales,
            'last_28_days_change' => bcsub($lastTwentyEightDaysSales, $previousTwentyEightDaysSales, 2),
            'opening_balance' => $openingBalance,
            'today_expenses' => $todayExpenses,
            'expected_revenue' => $expectedRevenue,
            'expected_net_revenue' => $expectedNetRevenue,
        ];
    }

    private function businessDayIsOpen(Store $store): bool
    {
        return $store->dailyCashRecords()
            ->whereDate('business_date', today()->toDateString())
            ->whereNull('closed_by')
            ->exists();
    }
}
