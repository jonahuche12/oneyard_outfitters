<?php

namespace App\Http\Controllers;

use App\Models\StockLevel;
use App\Models\StockConsignment;
use App\Models\StockConsignmentItem;
use App\Models\StockMovement;
use App\Models\StockSale;
use App\Models\StockSalePayment;
use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreExpense;
use App\Models\StoreFundAssignment;
use App\Models\StoreRemittance;
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


        /*
         * Expected proceeds from stock supplied to this store.
         *
         * Start with the original dispatched quantity. Deduct quantities
         * recorded as damaged, short, returned and explicitly reversed.
         *
         * Damaged and shortage quantities come from acknowledgement records.
         * Do not deduct their write-off/shortage movements again.
         *
         * Returns count only after the source store confirms receipt.
         *
         * Reversals are traced through reversal_of_id on the original
         * consignment_in movement. Unrelated stock movements are excluded.
         *
         * Use the original expected_sales_value snapshot. Never revalue
         * historical consignments using today's suggested selling price.
         */
        $consignmentItems = StockConsignmentItem::query()
            ->whereHas('consignment', function ($query) use ($store, $period) {
                $query
                    ->where('destination_store_id', $store->id)
                    ->whereBetween('dispatched_at', $period)
                    ->whereNotIn('status', [
                        StockConsignment::STATUS_DRAFT,
                        StockConsignment::STATUS_CANCELLED,
                    ]);
            })
            ->with([
                'acknowledgementItems',
                'returnItems.stockReturn',
            ])
            ->get();

        /*
         * Find reversal quantities linked specifically to the destination
         * consignment_in movements belonging to these consignments.
         */
        $reversalQuantities = DB::table('stock_movements as incoming')
            ->join(
                'stock_movements as reversal',
                'reversal.reversal_of_id',
                '=',
                'incoming.id'
            )
            ->where('incoming.store_id', $store->id)
            ->where('incoming.type', StockMovement::TYPE_CONSIGNMENT_IN)
            ->where('incoming.source_type', StockConsignment::class)
            ->where('reversal.store_id', $store->id)
            ->whereIn(
                'incoming.source_id',
                $consignmentItems
                    ->pluck('stock_consignment_id')
                    ->unique()
                    ->values()
            )
            ->selectRaw('
                incoming.source_id AS consignment_id,
                incoming.stock_item_id,
                COALESCE(SUM(ABS(reversal.quantity)), 0) AS quantity
            ')
            ->groupBy('incoming.source_id', 'incoming.stock_item_id')
            ->get()
            ->keyBy(fn ($row) => $row->consignment_id . ':' . $row->stock_item_id);

        $initialSuppliedQuantity = 0.0;
        $damagedSuppliedQuantity = 0.0;
        $shortageSuppliedQuantity = 0.0;
        $returnedSuppliedQuantity = 0.0;
        $reversedSuppliedQuantity = 0.0;
        $totalSupplyDeductions = 0.0;
        $accountableSuppliedQuantity = 0.0;
        $expectedStockProceeds = 0.0;

        foreach ($consignmentItems as $item) {
            $dispatchedQuantity = (float) $item->quantity_dispatched;

            if ($dispatchedQuantity <= 0) {
                continue;
            }

            $damagedQuantity = (float) $item->acknowledgementItems
                ->sum(fn ($acknowledgementItem) =>
                    (float) $acknowledgementItem->quantity_damaged
                );

            $shortageQuantity = (float) $item->acknowledgementItems
                ->sum(fn ($acknowledgementItem) =>
                    (float) $acknowledgementItem->quantity_shortage
                );

            $returnedQuantity = (float) $item->returnItems
                ->sum(function ($returnItem) {
                    if (!$returnItem->stockReturn?->received_at) {
                        return 0;
                    }

                    return (float) $returnItem->quantity_received;
                });

            $reversalKey = $item->stock_consignment_id . ':' . $item->stock_item_id;

            $reversedQuantity = (float) (
                $reversalQuantities->get($reversalKey)?->quantity ?? 0
            );

            $initialSuppliedQuantity += $dispatchedQuantity;
            $damagedSuppliedQuantity += $damagedQuantity;
            $shortageSuppliedQuantity += $shortageQuantity;
            $returnedSuppliedQuantity += $returnedQuantity;
            $reversedSuppliedQuantity += $reversedQuantity;

            /*
             * Cap combined deductions at the original dispatched quantity.
             * This prevents the accountable quantity from becoming negative.
             */
            $recordedDeductions = (
                $damagedQuantity
                + $shortageQuantity
                + $returnedQuantity
                + $reversedQuantity
            );

            $deductedQuantity = min(
                $dispatchedQuantity,
                max(0, $recordedDeductions)
            );

            $netAccountableQuantity = max(
                0,
                $dispatchedQuantity - $deductedQuantity
            );

            $totalSupplyDeductions += $deductedQuantity;
            $accountableSuppliedQuantity += $netAccountableQuantity;

            /*
             * expected_sales_value is the original dispatch-time snapshot.
             * market_price is the fallback snapshot for older rows where
             * expected_sales_value is null.
             */
            if ($item->expected_sales_value !== null) {
                $expectedSalesValue = (float) $item->expected_sales_value;
            } else {
                $expectedSalesValue = (
                    $dispatchedQuantity * (float) $item->market_price
                );
            }

            $expectedValuePerUnit = $expectedSalesValue / $dispatchedQuantity;

            $expectedStockProceeds += round(
                $netAccountableQuantity * $expectedValuePerUnit,
                2
            );
        }

        /*
         * Only confirmed assignments count as funds received.
         * Use the amounts actually sent, not the requested amounts.
         */
        $confirmedFundsReceived = StoreFundAssignment::query()
            ->where('store_id', $store->id)
            ->whereBetween('business_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->where('status', StoreFundAssignment::STATUS_CONFIRMED)
            ->selectRaw('COALESCE(SUM(sent_cash + sent_bank), 0) AS total')
            ->value('total');

        // Pending requests are displayed separately, not counted as received.
        $pendingFundRequests = StoreFundAssignment::query()
            ->where('store_id', $store->id)
            ->whereBetween('business_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->whereIn('status', [
                StoreFundAssignment::STATUS_REQUESTED,
                StoreFundAssignment::STATUS_PENDING_APPROVAL,
                StoreFundAssignment::STATUS_APPROVED,
            ])
            ->selectRaw('COALESCE(SUM(requested_cash + requested_bank), 0) AS total')
            ->value('total');

        // Dispatched assignments await confirmation by the receiving store.
        $fundsAwaitingConfirmation = StoreFundAssignment::query()
            ->where('store_id', $store->id)
            ->whereBetween('business_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->where('status', StoreFundAssignment::STATUS_SENT)
            ->selectRaw('COALESCE(SUM(sent_cash + sent_bank), 0) AS total')
            ->value('total');

        // Only confirmed remittances count as actual remitted funds.
        $actualRemitted = StoreRemittance::query()
            ->where('store_id', $store->id)
            ->whereBetween('business_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->where('status', StoreRemittance::STATUS_CONFIRMED)
            ->selectRaw('COALESCE(SUM(amount_cash + amount_bank), 0) AS total')
            ->value('total');

        $confirmedFundsReceived = (float) $confirmedFundsReceived;
        $pendingFundRequests = (float) $pendingFundRequests;
        $fundsAwaitingConfirmation = (float) $fundsAwaitingConfirmation;
        $actualRemitted = (float) $actualRemitted;
        $expectedStockProceeds = (float) $expectedStockProceeds;

        $expectedRemittance = $expectedStockProceeds
            + $confirmedFundsReceived
            - (float) $expenseTotal;

        $remittanceBalance = $expectedRemittance - $actualRemitted;


        /*
         * Lifetime stock throughput.
         *
         * This is independent of the selected dashboard reporting period.
         *
         * Store-created opening stock is sourced from opening_stock movements.
         * Central dispatch supply starts with original dispatched quantities
         * and deducts recorded damage, shortages and returns confirmed received.
         *
         * Damage and shortage stock movements are not deducted again because
         * their quantities are already represented by acknowledgement records.
         * Quarantine releases are transfers, not additional supply.
         *
         * Sales are counted from immutable sale lines for this store. Stock
         * movements are not added because they would duplicate those sales.
         */
        $stockThroughputOpeningByItem = DB::table('stock_movements')
            ->where('store_id', $store->id)
            ->where('type', StockMovement::TYPE_OPENING_STOCK)
            ->select('stock_item_id')
            ->selectRaw('SUM(quantity) AS opening_stock')
            ->groupBy('stock_item_id')
            ->get()
            ->keyBy('stock_item_id');

        $stockThroughputConsignmentItems = StockConsignmentItem::query()
            ->whereHas('consignment', function ($query) use ($store) {
                $query
                    ->where('destination_store_id', $store->id)
                    ->whereNotIn('status', [
                        StockConsignment::STATUS_DRAFT,
                        StockConsignment::STATUS_CANCELLED,
                    ]);
            })
            ->with([
                'acknowledgementItems',
                'returnItems.stockReturn',
            ])
            ->get();

        $stockThroughputDispatchByItem = [];

        foreach ($stockThroughputConsignmentItems as $item) {
            $dispatchedQuantity = (float) $item->quantity_dispatched;

            if ($dispatchedQuantity <= 0) {
                continue;
            }

            $damagedQuantity = (float) $item->acknowledgementItems
                ->sum(fn ($acknowledgementItem) =>
                    (float) $acknowledgementItem->quantity_damaged
                );

            $shortageQuantity = (float) $item->acknowledgementItems
                ->sum(fn ($acknowledgementItem) =>
                    (float) $acknowledgementItem->quantity_shortage
                );

            $receivedReturnQuantity = (float) $item->returnItems
                ->sum(function ($returnItem) {
                    if (! $returnItem->stockReturn?->received_at) {
                        return 0;
                    }

                    return (float) $returnItem->quantity_received;
                });

            $recordedDeductions = (
                $damagedQuantity
                + $shortageQuantity
                + $receivedReturnQuantity
            );

            $deductedQuantity = min(
                $dispatchedQuantity,
                max(0, $recordedDeductions)
            );

            $netDispatchQuantity = max(
                0,
                $dispatchedQuantity - $deductedQuantity
            );

            $stockItemId = $item->stock_item_id;

            $stockThroughputDispatchByItem[$stockItemId] =
                ($stockThroughputDispatchByItem[$stockItemId] ?? 0)
                + $netDispatchQuantity;
        }

        // Sale-line quantities are the source of truth for recorded sales.
        $stockThroughputSales = DB::table('stock_sale_items')
            ->join(
                'stock_sales',
                'stock_sales.id',
                '=',
                'stock_sale_items.stock_sale_id'
            )
            ->where('stock_sales.store_id', $store->id)
            ->select('stock_sale_items.stock_item_id')
            ->selectRaw('SUM(stock_sale_items.quantity) AS units_sold')
            ->groupBy('stock_sale_items.stock_item_id')
            ->get()
            ->keyBy('stock_item_id');

        $stockThroughputItemIds = collect($stockThroughputOpeningByItem->keys())
            ->merge(array_keys($stockThroughputDispatchByItem))
            ->merge($stockThroughputSales->keys())
            ->unique()
            ->values();

        $stockThroughputItemRecords = $stockThroughputItemIds->isEmpty()
            ? collect()
            : DB::table('stock_items')
                ->whereIn('id', $stockThroughputItemIds)
                ->where('store_id', $store->id)
                ->orderBy('description')
                ->get(['id', 'description', 'unit']);

        $stockThroughput = $stockThroughputItemRecords
            ->map(function ($stockItem) use (
                $stockThroughputOpeningByItem,
                $stockThroughputDispatchByItem,
                $stockThroughputSales
            ) {
                $openingStock = (float) (
                    $stockThroughputOpeningByItem
                        ->get($stockItem->id)?->opening_stock ?? 0
                );

                $netDispatchSupplied = (float) (
                    $stockThroughputDispatchByItem[$stockItem->id] ?? 0
                );

                $totalNetSupplied = $openingStock + $netDispatchSupplied;

                $unitsSold = (float) (
                    $stockThroughputSales->get($stockItem->id)?->units_sold ?? 0
                );

                return (object) [
                    'stock_item_id' => $stockItem->id,
                    'description' => $stockItem->description,
                    'unit' => $stockItem->unit,
                    'opening_stock' => $openingStock,
                    'net_dispatch_supplied' => $netDispatchSupplied,
                    'total_net_supplied' => $totalNetSupplied,
                    'units_sold' => $unitsSold,
                    'remaining_accountable' => $totalNetSupplied - $unitsSold,
                ];
            })
            ->filter(fn ($item) =>
                $item->opening_stock > 0
                || $item->net_dispatch_supplied > 0
                || $item->units_sold > 0
            )
            ->values();

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
            'expectedStockProceeds',
            'initialSuppliedQuantity',
            'damagedSuppliedQuantity',
            'shortageSuppliedQuantity',
            'returnedSuppliedQuantity',
            'reversedSuppliedQuantity',
            'totalSupplyDeductions',
            'accountableSuppliedQuantity',
            'confirmedFundsReceived',
            'expectedRemittance',
            'actualRemitted',
            'remittanceBalance',
            'pendingFundRequests',
            'fundsAwaitingConfirmation',
            'inventoryValue',
            'inventoryTotals',
            'outOfStockCount',
            'inventoryCategories',
            'fastMovingItems',
            'stockThroughput',
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
