<?php

namespace App\Http\Controllers;

use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockSale;
use App\Models\StockSalePayment;
use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreExpense;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StoreDashboardController extends Controller
{
    public function show(Request $request, Store $store)
    {
        Gate::authorize('viewDashboard', $store);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now())->endOfDay();

        if ($from->gt($to)) {
            return back()->withErrors([
                'from' => 'The start date must be on or before the end date.',
            ]);
        }

        $period = [$from, $to];

        // Sales revenue is based on the sale date, not payment dates.
        $sales = StockSale::query()
            ->where('store_id', $store->id)
            ->whereBetween('sold_at', $period);

        $revenue = (clone $sales)->sum('total_amount');
        $transactionCount = (clone $sales)->count();

        // Sale quantities are recorded quantities. No unverified reversal
        // relationship is assumed when calculating this dashboard metric.
        $unitsSold = DB::table('stock_sale_items')
            ->join('stock_sales', 'stock_sales.id', '=', 'stock_sale_items.stock_sale_id')
            ->where('stock_sales.store_id', $store->id)
            ->whereBetween('stock_sales.sold_at', $period)
            ->sum('stock_sale_items.quantity');

        // Collections are grouped by payment date. Tips are displayed separately.
        $payments = StockSalePayment::query()
            ->whereHas('sale', fn ($query) => $query->where('store_id', $store->id))
            ->whereBetween('paid_at', $period);

        $collections = (clone $payments)->sum('amount');
        $tips = (clone $payments)->sum('tip_amount');

        // Receivables are current outstanding sale balances, independent of
        // the selected reporting period. Tips do not reduce sale balances.
        $paymentTotals = DB::table('stock_sale_payments')
            ->select('stock_sale_id')
            ->selectRaw('SUM(amount) AS paid_total')
            ->groupBy('stock_sale_id');

        $outstanding = DB::table('stock_sales')
            ->leftJoinSub($paymentTotals, 'payment_totals', function ($join) {
                $join->on('payment_totals.stock_sale_id', '=', 'stock_sales.id');
            })
            ->where('stock_sales.store_id', $store->id)
            ->selectRaw(
                'COALESCE(SUM(GREATEST(stock_sales.total_amount - COALESCE(payment_totals.paid_total, 0), 0)), 0) AS outstanding'
            )
            ->value('outstanding');

        // Expenses are assigned to a daily cash record. Use its business date
        // rather than the expense row's creation timestamp.
        $expenses = StoreExpense::query()
            ->where('store_id', $store->id)
            ->whereHas('dailyCashRecord', fn ($query) => $query->whereBetween('business_date', [
                $from->toDateString(),
                $to->toDateString(),
            ]));

        $expenseTotal = (clone $expenses)->sum('amount');

        $expenseCategories = (clone $expenses)
            ->select('category')
            ->selectRaw('SUM(amount) AS total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $expenseMethods = (clone $expenses)
            ->select('payment_method')
            ->selectRaw('SUM(amount) AS total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // Current inventory is a present-day balance, not a historical
        // inventory snapshot for the selected reporting period.
        $inventory = StockLevel::query()->where('store_id', $store->id);

        $inventoryValue = (clone $inventory)->sum('inventory_value');

        $inventoryTotals = (clone $inventory)
            ->selectRaw('
                COALESCE(SUM(quantity), 0) AS on_hand,
                COALESCE(SUM(quantity_quarantined), 0) AS quarantined,
                COALESCE(SUM(quantity - quantity_quarantined), 0) AS available
            ')
            ->first();

        $outOfStockCount = DB::table('stock_levels')
            ->where('store_id', $store->id)
            ->whereRaw('(quantity - quantity_quarantined) <= 0')
            ->count();

        $inventoryCategories = DB::table('stock_levels')
            ->join('stock_items', 'stock_items.id', '=', 'stock_levels.stock_item_id')
            ->where('stock_levels.store_id', $store->id)
            ->selectRaw("COALESCE(stock_items.category, 'Uncategorised') AS category")
            ->selectRaw('SUM(stock_levels.inventory_value) AS inventory_value')
            ->groupBy('stock_items.category')
            ->orderByDesc('inventory_value')
            ->get();

        // Fast-moving items use recorded sale lines for this store and period.
        $fastMovingItems = DB::table('stock_sale_items')
            ->join('stock_sales', 'stock_sales.id', '=', 'stock_sale_items.stock_sale_id')
            ->join('stock_items', 'stock_items.id', '=', 'stock_sale_items.stock_item_id')
            ->leftJoin('stock_levels', function ($join) use ($store) {
                $join->on('stock_levels.stock_item_id', '=', 'stock_items.id')
                    ->where('stock_levels.store_id', '=', $store->id);
            })
            ->where('stock_sales.store_id', $store->id)
            ->whereBetween('stock_sales.sold_at', $period)
            ->select(
                'stock_items.id',
                'stock_items.description',
                'stock_items.category',
                'stock_items.unit'
            )
            ->selectRaw('SUM(stock_sale_items.quantity) AS units_sold')
            ->selectRaw('SUM(stock_sale_items.line_total) AS sales_revenue')
            ->selectRaw('CASE WHEN SUM(stock_sale_items.quantity) > 0
                THEN SUM(stock_sale_items.line_total) / SUM(stock_sale_items.quantity)
                ELSE 0 END AS average_selling_price')
            ->selectRaw('COALESCE(MAX(stock_levels.quantity - stock_levels.quantity_quarantined), 0) AS available_quantity')
            ->groupBy(
                'stock_items.id',
                'stock_items.description',
                'stock_items.category',
                'stock_items.unit'
            )
            ->orderByDesc('units_sold')
            ->limit(10)
            ->get();

        $revenueTrend = DB::table('stock_sales')
            ->where('store_id', $store->id)
            ->whereBetween('sold_at', $period)
            ->selectRaw('DATE(sold_at) AS day')
            ->selectRaw('SUM(total_amount) AS total')
            ->selectRaw('COUNT(*) AS transactions')
            ->groupByRaw('DATE(sold_at)')
            ->orderBy('day')
            ->get();

        $collectionTrend = DB::table('stock_sale_payments')
            ->join('stock_sales', 'stock_sales.id', '=', 'stock_sale_payments.stock_sale_id')
            ->where('stock_sales.store_id', $store->id)
            ->whereBetween('stock_sale_payments.paid_at', $period)
            ->selectRaw('DATE(stock_sale_payments.paid_at) AS day')
            ->selectRaw('SUM(stock_sale_payments.amount) AS total')
            ->groupByRaw('DATE(stock_sale_payments.paid_at)')
            ->orderBy('day')
            ->get();

        $paymentMethods = (clone $payments)
            ->select('payment_method')
            ->selectRaw('SUM(amount) AS total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $dailyCashRecords = StoreDailyCashRecord::query()
            ->where('store_id', $store->id)
            ->whereBetween('business_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->orderByDesc('business_date')
            ->limit(10)
            ->get();

        $latestClosedCashRecord = StoreDailyCashRecord::query()
            ->where('store_id', $store->id)
            ->whereNotNull('closed_by')
            ->whereNotNull('closing_cash')
            ->whereNotNull('closing_bank')
            ->orderByDesc('business_date')
            ->first();

        $recentSales = StockSale::query()
            ->where('store_id', $store->id)
            ->whereBetween('sold_at', $period)
            ->with('creator')
            ->latest('sold_at')
            ->limit(8)
            ->get();

        $recentPayments = StockSalePayment::query()
            ->whereHas('sale', fn ($query) => $query->where('store_id', $store->id))
            ->whereBetween('paid_at', $period)
            ->with('sale', 'receiver')
            ->latest('paid_at')
            ->limit(8)
            ->get();

        $recentExpenses = StoreExpense::query()
            ->where('store_id', $store->id)
            ->whereHas('dailyCashRecord', fn ($query) => $query->whereBetween('business_date', [
                $from->toDateString(),
                $to->toDateString(),
            ]))
            ->with('recordedBy', 'dailyCashRecord')
            ->latest()
            ->limit(8)
            ->get();

        $recentMovements = StockMovement::query()
            ->where('store_id', $store->id)
            ->whereBetween('created_at', $period)
            ->with('item', 'actor')
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('stores.dashboard', compact(
            'store',
            'from',
            'to',
            'revenue',
            'transactionCount',
            'unitsSold',
            'collections',
            'tips',
            'outstanding',
            'expenseTotal',
            'expenseCategories',
            'expenseMethods',
            'inventoryValue',
            'inventoryTotals',
            'outOfStockCount',
            'inventoryCategories',
            'fastMovingItems',
            'revenueTrend',
            'collectionTrend',
            'paymentMethods',
            'dailyCashRecords',
            'latestClosedCashRecord',
            'recentSales',
            'recentPayments',
            'recentExpenses',
            'recentMovements',
        ));
    }
}
