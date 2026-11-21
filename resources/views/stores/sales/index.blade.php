@extends('layouts.app')

@section('content')

<div class="mx-auto max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-8 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('stores.show', $store) }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-900">
                ← {{ $store->name }}
            </a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                {{ $outstandingOnly ? 'Outstanding sales' : 'Sales' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">Sales recorded at {{ $store->name }}.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <nav class="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-1 text-sm" aria-label="Sales filter">
                <a
                    href="{{ route('stores.sales.index', $store) }}"
                    class="rounded-lg px-3 py-2 font-semibold transition {{ $outstandingOnly ? 'text-slate-600 hover:bg-white hover:text-slate-900' : 'bg-white text-slate-950 shadow-sm' }}"
                    @unless ($outstandingOnly) aria-current="page" @endunless
                >All sales</a>
                <a
                    href="{{ route('stores.sales.outstanding', $store) }}"
                    class="rounded-lg px-3 py-2 font-semibold transition {{ $outstandingOnly ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}"
                    @if ($outstandingOnly) aria-current="page" @endif
                >Outstanding</a>
            </nav>

            @if ($canRecordSales)
                <a href="{{ route('stores.sales.create', $store) }}" class="oy-btn oy-btn-primary">
                    Record sale
                </a>
            @else
                <span
                    aria-disabled="true"
                    title="Record opening balances before recording sales, or wait until the next business day."
                    class="oy-btn oy-btn-primary cursor-not-allowed opacity-50"
                >
                    Record sale
                </span>
            @endif
        </div>
    </div>

    {{-- Feedback --}}
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any() && ! old('_sale'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Sales dashboard --}}
    @php
        $salesMetrics = [
            [
                'label' => 'Sales today',
                'key' => 'today_sales',
                'change' => 'today_sales_change',
                'note' => 'Compared with yesterday',
                'icon' => 'today',
            ],
            [
                'label' => 'Sales yesterday',
                'key' => 'yesterday_sales',
                'note' => 'Payments received yesterday',
                'icon' => 'yesterday',
            ],
            [
                'label' => 'Last 7 days',
                'key' => 'last_7_days_sales',
                'change' => 'last_7_days_change',
                'note' => 'Compared with the preceding 7 days',
                'icon' => 'week',
            ],
            [
                'label' => 'Last 28 days',
                'key' => 'last_28_days_sales',
                'change' => 'last_28_days_change',
                'note' => 'Compared with the preceding 28 days',
                'icon' => 'month',
            ],
        ];

        $cashMetrics = [
            ['label' => 'Opening balance', 'key' => 'opening_balance', 'note' => 'Cash and bank at opening', 'icon' => 'wallet'],
            ['label' => "Today's expenses", 'key' => 'today_expenses', 'note' => 'All recorded expenses', 'icon' => 'expenses'],
            ['label' => 'Expected revenue', 'key' => 'expected_revenue', 'note' => 'Opening balance + collections', 'icon' => 'revenue'],
            ['label' => 'Expected net revenue', 'key' => 'expected_net_revenue', 'note' => 'After all expenses', 'icon' => 'net'],
        ];
    @endphp

    <section aria-label="Sales dashboard" class="space-y-5">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-base font-bold tracking-tight text-slate-950">Sales overview</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Collections are based on payments received, not the full value of unpaid sales.
                </p>
            </div>
            <span class="text-xs font-medium text-slate-500">Store performance</span>
        </div>

        <details open class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                <span class="text-sm font-semibold uppercase tracking-wider text-slate-700">
                    Sales performance
                </span>
                <span class="flex items-center gap-2 text-xs font-medium text-slate-500">
                    <span class="group-open:hidden">Show</span>
                    <span class="hidden group-open:inline">Hide</span>
                    <svg class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m5 7 5 5 5-5"/>
                    </svg>
                </span>
            </summary>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($salesMetrics as $metric)
                    @php
                        $hasComparison = isset($metric['change']);
                        $change = $hasComparison ? (string) $salesDashboard[$metric['change']] : '0.00';
                        $isUp = bccomp($change, '0.00', 2) >= 0;
                        $border = !$hasComparison
                            ? 'border-[var(--oy-primary-border)]'
                            : ($isUp ? 'border-[var(--oy-success-border)]' : 'border-[var(--oy-danger-border)]');
                        $changeText = $isUp ? 'text-[var(--oy-success)]' : 'text-[var(--oy-danger)]';
                        $changeBg = $isUp ? 'bg-[var(--oy-success-bg)]' : 'bg-[var(--oy-danger-bg)]';
                    @endphp

                    <article class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $metric['label'] }}</p>
                                <p class="mt-2 break-words text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                                    ₦{{ number_format((float) $salesDashboard[$metric['key']], 2) }}
                                </p>
                            </div>
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[var(--oy-primary-soft)] text-[var(--oy-primary)]">
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    @switch($metric['icon'])
                                        @case('today')
                                            <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3"/>
                                            @break
                                        @case('yesterday')
                                            <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>
                                            @break
                                        @case('week')
                                            <rect x="3" y="4" width="18" height="17" rx="2"/><path d="M7 2v4M17 2v4M3 9h18M7 13h2M12 13h2M7 17h2M12 17h2"/>
                                            @break
                                        @case('month')
                                            <rect x="4" y="4" width="16" height="16" rx="1"/><path d="M8 2v4M16 2v4M4 9h16M8 13h2M14 13h2M8 17h2"/>
                                            @break
                                    @endswitch
                                </svg>
                            </span>
                        </div>

                        <div class="mt-3 flex min-h-7 flex-wrap items-center justify-between gap-2">
                            <p class="text-xs text-slate-500">{{ $metric['note'] }}</p>
                            @if ($hasComparison)
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold {{ $changeBg }} {{ $changeText }}">
                                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        @if ($isUp)
                                            <path d="M10 15V5M6 9l4-4 4 4"/>
                                        @else
                                            <path d="M10 5v10m-4-4 4 4 4-4"/>
                                        @endif
                                    </svg>
                                    {{ $isUp ? '+' : '−' }}₦{{ number_format(abs((float) $change), 2) }}
                                </span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

        <div class="space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Cash position</h3>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($cashMetrics as $metric)
                    <article class="rounded-xl border border-[var(--oy-border)] border-t-2 border-t-[var(--oy-primary)] bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $metric['label'] }}</p>
                                <p class="mt-2 break-words text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                                    ₦{{ number_format((float) $salesDashboard[$metric['key']], 2) }}
                                </p>
                            </div>
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[var(--oy-primary-soft)] text-[var(--oy-primary)]">
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    @switch($metric['icon'])
                                        @case('wallet')
                                            <rect x="3" y="5" width="18" height="15" rx="2"/><path d="M3 9h18M16 14h2"/>
                                            @break
                                        @case('expenses')
                                            <path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6h5a3 3 0 0 1 0 6H6"/>
                                            @break
                                        @case('revenue')
                                            <path d="M3 17l6-6 4 4 8-9"/><path d="M15 6h6v6"/>
                                            @break
                                        @case('net')
                                            <path d="M4 19V5M4 19h16"/><path d="m7 15 4-4 3 2 5-7"/>
                                            @break
                                    @endswitch
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">{{ $metric['note'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
        </details>
    </section>

    <details class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <summary class="cursor-pointer list-none px-5 py-4 text-sm font-bold text-slate-900">
            <span class="flex items-center justify-between gap-3">
                Filter sales records
                <span class="text-xs font-normal text-slate-500">Choose date or date range</span>
            </span>
        </summary>
        <div class="border-t border-slate-200 p-4 sm:p-5">
<form
        data-ajax-list-form
        method="GET"
        action="{{ $outstandingOnly
            ? route('stores.sales.outstanding', $store)
            : route('stores.sales.index', $store) }}"
        class="mb-5 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5"
    >
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <label class="block text-sm font-medium text-slate-700">
                Specific date
                <input type="date" name="sales_date" value="{{ old('sales_date', $salesFilters['sales_date'] ?? '') }}" class="oy-input mt-1 w-full rounded-lg">
            </label>
            <label class="block text-sm font-medium text-slate-700">
                From
                <input type="date" name="sales_from" value="{{ old('sales_from', $salesFilters['sales_from'] ?? '') }}" class="oy-input mt-1 w-full rounded-lg">
            </label>
            <label class="block text-sm font-medium text-slate-700">
                To
                <input type="date" name="sales_to" value="{{ old('sales_to', $salesFilters['sales_to'] ?? '') }}" class="oy-input mt-1 w-full rounded-lg">
            </label>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button type="submit" class="oy-btn oy-btn-primary justify-center">Apply filter</button>
            <a href="{{ $outstandingOnly ? route('stores.sales.outstanding', $store) : route('stores.sales.index', $store) }}" class="oy-btn oy-btn-secondary justify-center">Clear</a>
        </div>
        @if ($errors->has('sales_date') || $errors->has('sales_from') || $errors->has('sales_to'))
            <p class="mt-2 text-sm text-red-700">
                {{ $errors->first('sales_date') ?: ($errors->first('sales_from') ?: $errors->first('sales_to')) }}
            </p>
        @endif
    </form>
        </div>
    </details>

    <script>
    (function () {
        var filterForm = document.querySelector('form[data-ajax-list-form]');

        if (!filterForm || filterForm.dataset.dateFilterReady === 'true') {
            return;
        }

        filterForm.dataset.dateFilterReady = 'true';

        var specificDate = filterForm.querySelector('[name="sales_date"]');
        var fromDate = filterForm.querySelector('[name="sales_from"]');
        var toDate = filterForm.querySelector('[name="sales_to"]');

        if (!specificDate || !fromDate || !toDate) {
            return;
        }

        function clearRange() {
            fromDate.value = '';
            toDate.value = '';
        }

        function clearSpecificDate() {
            specificDate.value = '';
        }

        specificDate.addEventListener('input', function () {
            if (specificDate.value) {
                clearRange();
            }
        });

        specificDate.addEventListener('change', function () {
            if (specificDate.value) {
                clearRange();
            }
        });

        [fromDate, toDate].forEach(function (field) {
            field.addEventListener('input', function () {
                if (field.value) {
                    clearSpecificDate();
                }
            });

            field.addEventListener('change', function () {
                if (field.value) {
                    clearSpecificDate();
                }
            });
        });

        // Capture before the AJAX handler serializes the form.
        filterForm.addEventListener('submit', function () {
            if (fromDate.value && toDate.value) {
                clearSpecificDate();
            } else if (specificDate.value) {
                clearRange();
            }
        }, true);
    })();
    </script>

    {{-- AJAX results: list, pagination and sale detail dialogs. --}}
    <div data-ajax-list-results>
        @include('stores.sales.partials.results')
    </div>

@if (session('success'))
    <script>
        try {
            sessionStorage.removeItem(@json('oneyard:stock-sale-draft:'.$store->id.':'.auth()->id()));
        } catch (error) {
            console.warn('Unable to clear the completed sale draft.', error);
        }
    </script>
@endif

<script>
(function () {
    // Guard against a second copy running (for example a stale bundle).
    if (window.__saleDetailsReady) {
        return;
    }
    window.__saleDetailsReady = true;

    var root = document.documentElement;

    function openDialog(dialog) {
        if (!dialog || dialog.open || typeof dialog.showModal !== 'function') {
            return;
        }

        try {
            dialog.showModal();
        } catch (error) {
            console.error('Unable to open the sale details.', error);
            return;
        }

        root.classList.add('overflow-hidden');
    }

    function unlockIfNoneOpen() {
        if (!document.querySelector('dialog[data-sale-dialog][open]')) {
            root.classList.remove('overflow-hidden');
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        var opener = target.closest('[data-sale-open]');

        if (opener) {
            openDialog(document.getElementById('sale-' + opener.getAttribute('data-sale-open')));
            return;
        }

        var closer = target.closest('[data-sale-close]');

        if (closer) {
            var dialog = closer.closest('dialog');

            if (dialog) {
                dialog.close();
            }

            return;
        }

        var fill = target.closest('[data-fill-amount]');

        if (fill) {
            var input = document.getElementById(fill.getAttribute('data-fill-target'));

            if (input) {
                input.value = fill.getAttribute('data-fill-amount');
                input.focus();
            }

            return;
        }

        // The dialog has no padding, so a click on the dialog element itself
        // is a click on the backdrop.
        if (target instanceof HTMLDialogElement && target.hasAttribute('data-sale-dialog')) {
            target.close();
        }
    });

    // "close" does not bubble, so listen in the capture phase (Escape lands here too).
    document.addEventListener('close', function (event) {
        if (event.target instanceof HTMLDialogElement && event.target.hasAttribute('data-sale-dialog')) {
            unlockIfNoneOpen();
        }
    }, true);

    function openRequested() {
        document.querySelectorAll('dialog[data-open-on-load]').forEach(openDialog);

        var match = window.location.hash.match(/^#sale-(\d+)$/);

        if (match) {
            openDialog(document.getElementById('sale-' + match[1]));
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', openRequested);
    } else {
        openRequested();
    }
})();
</script>

<style>
    @media (min-width: 768px) {
        [data-mobile-sales-list] { display: none !important; }
        [data-desktop-sales-table] { display: block !important; }
        [data-desktop-sales-table] thead { display: table-header-group !important; }
        [data-desktop-sales-table] td.md\:table-cell { display: table-cell !important; }
    }
    @media (min-width: 1024px) {
        [data-desktop-sales-table] .lg\:table-cell { display: table-cell !important; }
    }
</style>
@endsection
