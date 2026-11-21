@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-8 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('stores.show', $store) }}"
                   class="text-sm font-medium text-slate-500 hover:text-slate-900">
                    ← Back to {{ $store->name }}
                </a>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                    Store dashboard
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $store->name }} · {{ $store->code }}
                </p>
            </div>

            <form method="GET" action="{{ route('stores.dashboard', $store) }}"
                  class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-end">
                <div>
                    <label for="from" class="block text-xs font-medium text-slate-600">From</label>
                    <input id="from" name="from" type="date" value="{{ $from->toDateString() }}"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-3 text-sm sm:w-auto sm:py-2">
                </div>
                <div>
                    <label for="to" class="block text-xs font-medium text-slate-600">To</label>
                    <input id="to" name="to" type="date" value="{{ $to->toDateString() }}"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-3 text-sm sm:w-auto sm:py-2">
                </div>
                <button type="submit" class="oy-btn oy-btn-primary">Apply</button>
            </form>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                ['Sales revenue', '₦'.number_format((float) $revenue, 2), 'Recorded sales dated in this period'],
                ['Payments collected', '₦'.number_format((float) $collections, 2), 'Payment amounts received in this period'],
                ['Outstanding receivables', '₦'.number_format((float) $outstanding, 2), 'Current unpaid sale balances; tips excluded'],
                ['Units sold', number_format((float) $unitsSold, 3), 'Recorded sale-line quantities in this period'],
                ['Expenses recorded', '₦'.number_format((float) $expenseTotal, 2), 'Expenses linked to business dates in this period'],
                ['Current inventory value', '₦'.number_format((float) $inventoryValue, 2), 'Current stock-level inventory value'],
            ] as [$label, $value, $description])
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                    <p class="mt-2 break-words text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">{{ $value }}</p>
                    <p class="mt-2 text-xs text-slate-500">{{ $description }}</p>
                </article>
            @endforeach
        </div>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Sales performance</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Transactions</p>
                        <p class="mt-1 text-xl font-semibold text-slate-900">
                            {{ number_format($transactionCount) }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Average sale value</p>
                        <p class="mt-1 text-xl font-semibold text-slate-900">
                            ₦{{ number_format($transactionCount ? $revenue / $transactionCount : 0, 2) }}
                        </p>
                    </div>
                </div>
                <h3 class="mt-5 text-sm font-semibold text-slate-800">Revenue by day</h3>
        @if ($revenueTrend->isEmpty())
            <p class="mt-3 text-sm text-slate-500">No sales in this period.</p>
        @else
            <div class="relative mt-4 h-64 w-full rounded-xl bg-slate-50 p-3 sm:p-4">
                <canvas
                    data-store-chart="revenue"
                    data-chart-data='@json($revenueTrend->map(fn ($day) => ["label" => \Carbon\Carbon::parse($day->day)->format("d M"), "value" => (float) $day->total])->values())'
                    role="img"
                    aria-label="Daily sales revenue chart">
                </canvas>
            </div>
        @endif
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Payment collections</h2>
                <div class="mt-3 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-xs text-slate-500">Payment amounts collected</p>
                        <p class="mt-1 text-2xl font-bold text-slate-950">₦{{ number_format((float) $collections, 2) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500">Tips received</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">₦{{ number_format((float) $tips, 2) }}</p>
                    </div>
                </div>

                <h3 class="mt-5 text-sm font-semibold text-slate-800">Collections by day</h3>
                @if ($collectionTrend->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">No payments in this period.</p>
                @else
                    <div class="relative mt-4 h-64 w-full rounded-xl bg-slate-50 p-3 sm:p-4">
                        <canvas
                            data-store-chart="collections"
                            data-chart-data='@json($collectionTrend->map(fn ($day) => ["label" => \Carbon\Carbon::parse($day->day)->format("d M"), "value" => (float) $day->total])->values())'
                            role="img"
                            aria-label="Daily payment collections chart">
                        </canvas>
                    </div>
                @endif

                <h3 class="mt-5 text-sm font-semibold text-slate-800">Payment methods</h3>
                <div class="mt-2 space-y-2">
                    @forelse ($paymentMethods as $method)
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="capitalize text-slate-600">{{ str_replace('_', ' ', $method->payment_method) }}</span>
                            <span class="font-medium tabular-nums text-slate-900">₦{{ number_format((float) $method->total, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No payment breakdown available.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-slate-900">Fast-moving stock</h2>
                    <p class="mt-1 text-xs text-slate-500">Ranked by recorded sale quantities in the selected period.</p>
                </div>
                <a href="{{ route('stock.index', ['store_id' => $store->id]) }}"
                   class="text-sm font-medium text-slate-700 hover:text-slate-950">View stock</a>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Item</th>
                            <th class="px-3 py-3 text-right">Units sold</th>
                            <th class="px-3 py-3 text-right">Sales revenue</th>
                            <th class="px-3 py-3 text-right">Avg. selling price</th>
                            <th class="px-3 py-3 text-right">Available now</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($fastMovingItems as $item)
                            <tr>
                                <td class="px-3 py-3">
                                    <p class="font-medium text-slate-900">{{ $item->description }}</p>
                                    <p class="text-xs text-slate-500">{{ $item->category ?? 'Uncategorised' }} · {{ $item->unit }}</p>
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format((float) $item->units_sold, 3) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">₦{{ number_format((float) $item->sales_revenue, 2) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">₦{{ number_format((float) $item->average_selling_price, 2) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format((float) $item->available_quantity, 3) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No recorded sales in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Inventory health</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Available quantity</p>
                        <p class="mt-1 text-xl font-semibold">{{ number_format((float) ($inventoryTotals->available ?? 0), 3) }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Quarantined quantity</p>
                        <p class="mt-1 text-xl font-semibold">{{ number_format((float) ($inventoryTotals->quarantined ?? 0), 3) }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Total on-hand quantity</p>
                        <p class="mt-1 text-xl font-semibold">{{ number_format((float) ($inventoryTotals->on_hand ?? 0), 3) }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Out-of-stock items</p>
                        <p class="mt-1 text-xl font-semibold">{{ number_format($outOfStockCount) }}</p>
                    </div>
                </div>
                <h3 class="mt-5 text-sm font-semibold text-slate-800">Inventory value by category</h3>
                <div class="mt-3 space-y-2">
                    @forelse ($inventoryCategories as $category)
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-slate-600">{{ $category->category }}</span>
                            <span class="font-medium tabular-nums">₦{{ number_format((float) $category->inventory_value, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No inventory balances recorded.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Expenses</h2>
                <p class="mt-2 text-2xl font-bold text-slate-950">₦{{ number_format((float) $expenseTotal, 2) }}</p>
                <h3 class="mt-5 text-sm font-semibold text-slate-800">By category</h3>
                <div class="mt-3 space-y-2">
                    @forelse ($expenseCategories as $category)
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="capitalize text-slate-600">{{ str_replace('_', ' ', $category->category) }}</span>
                            <span class="font-medium tabular-nums">₦{{ number_format((float) $category->total, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No expenses in this period.</p>
                    @endforelse
                </div>
                <h3 class="mt-5 text-sm font-semibold text-slate-800">By payment method</h3>
                <div class="mt-3 space-y-2">
                    @forelse ($expenseMethods as $method)
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="capitalize text-slate-600">{{ $method->payment_method }}</span>
                            <span class="font-medium tabular-nums">₦{{ number_format((float) $method->total, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No expense payment breakdown available.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Daily cash and bank records</h2>
            @if ($latestClosedCashRecord)
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Latest closed day · {{ $latestClosedCashRecord->business_date->format('d M Y') }} · Closing cash</p>
                        <p class="mt-1 text-lg font-semibold">₦{{ number_format((float) $latestClosedCashRecord->closing_cash, 2) }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Latest closed day · Closing bank</p>
                        <p class="mt-1 text-lg font-semibold">₦{{ number_format((float) $latestClosedCashRecord->closing_bank, 2) }}</p>
                    </div>
                </div>
            @else
                <p class="mt-3 text-sm text-slate-500">No completed daily cash record is available yet.</p>
            @endif

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Business date</th>
                            <th class="px-3 py-3 text-right">Opening cash</th>
                            <th class="px-3 py-3 text-right">Opening bank</th>
                            <th class="px-3 py-3 text-right">Closing cash</th>
                            <th class="px-3 py-3 text-right">Closing bank</th>
                            <th class="px-3 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($dailyCashRecords as $record)
                            <tr>
                                <td class="px-3 py-3">{{ $record->business_date->format('d M Y') }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">₦{{ number_format((float) $record->opening_cash, 2) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">₦{{ number_format((float) $record->opening_bank, 2) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ $record->closing_cash === null ? '—' : '₦'.number_format((float) $record->closing_cash, 2) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ $record->closing_bank === null ? '—' : '₦'.number_format((float) $record->closing_bank, 2) }}</td>
                                <td class="px-3 py-3">{{ $record->closed_by ? 'Closed' : 'Open' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No daily records in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-slate-500">
                Closing balances are recorded balances, not a calculated cash-flow or reconciliation statement.
            </p>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Recent sales</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    @forelse ($recentSales as $sale)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $sale->sale_number }}</p>
                                <p class="text-xs text-slate-500">{{ $sale->customer_name ?: 'Walk-in customer' }} · {{ $sale->sold_at->format('d M, h:i A') }}</p>
                            </div>
                            <p class="whitespace-nowrap text-sm font-semibold">₦{{ number_format((float) $sale->total_amount, 2) }}</p>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-500">No recent sales.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Recent payments</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    @forelse ($recentPayments as $payment)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $payment->sale?->sale_number ?? 'Sale payment' }}</p>
                                <p class="text-xs text-slate-500">{{ ucfirst($payment->payment_method) }} · {{ $payment->paid_at->format('d M, h:i A') }}</p>
                            </div>
                            <p class="whitespace-nowrap text-sm font-semibold">₦{{ number_format((float) $payment->amount, 2) }}</p>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-500">No recent payments.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Recent expenses</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    @forelse ($recentExpenses as $expense)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $expense->description }}</p>
                                <p class="text-xs text-slate-500">{{ str_replace('_', ' ', $expense->category) }} · {{ $expense->dailyCashRecord?->business_date?->format('d M Y') }}</p>
                            </div>
                            <p class="whitespace-nowrap text-sm font-semibold">₦{{ number_format((float) $expense->amount, 2) }}</p>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-500">No recent expenses.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Recent stock movements</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    @forelse ($recentMovements as $movement)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $movement->item?->description ?? 'Stock item' }}</p>
                                <p class="text-xs text-slate-500">{{ str_replace('_', ' ', $movement->type) }} · {{ $movement->created_at?->format('d M, h:i A') }}</p>
                            </div>
                            <p class="whitespace-nowrap text-sm font-semibold">{{ number_format((float) $movement->quantity, 3) }}</p>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-500">No stock movements in this period.</p>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
@endsection
