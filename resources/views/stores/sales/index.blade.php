@extends('layouts.app')

@section('content')
@php
    // Semantic tone map (full literal classes so Tailwind can detect them).
    $tones = [
        'emerald' => ['card' => 'border-emerald-100 bg-gradient-to-br from-emerald-50 to-white', 'label' => 'text-emerald-700', 'value' => 'text-emerald-950', 'chip' => 'bg-emerald-100 text-emerald-700'],
        'blue'    => ['card' => 'border-blue-100 bg-gradient-to-br from-blue-50 to-white', 'label' => 'text-blue-700', 'value' => 'text-blue-950', 'chip' => 'bg-blue-100 text-blue-700'],
        'rose'    => ['card' => 'border-rose-100 bg-gradient-to-br from-rose-50 to-white', 'label' => 'text-rose-700', 'value' => 'text-rose-950', 'chip' => 'bg-rose-100 text-rose-700'],
        'indigo'  => ['card' => 'border-indigo-100 bg-gradient-to-br from-indigo-50 to-white', 'label' => 'text-indigo-700', 'value' => 'text-indigo-950', 'chip' => 'bg-indigo-100 text-indigo-700'],
    ];

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
        [
            'label' => 'Opening cash',
            'key' => 'opening_cash',
            'note' => 'Physical cash recorded at opening',
            'tone' => 'emerald',
            'icon' => 'cash',
        ],
        [
            'label' => 'Opening bank',
            'key' => 'opening_bank',
            'note' => 'Bank balance recorded at opening',
            'tone' => 'blue',
            'icon' => 'bank',
        ],
        [
            'label' => 'Confirmed funds received — cash',
            'key' => 'intraday_funds_cash',
            'note' => 'Confirmed after opening',
            'tone' => 'emerald',
            'icon' => 'cash',
        ],
        [
            'label' => 'Confirmed funds received — bank',
            'key' => 'intraday_funds_bank',
            'note' => 'Confirmed after opening',
            'tone' => 'blue',
            'icon' => 'bank',
        ],
        [
            'label' => 'Sales collections — cash',
            'key' => 'today_sales_cash',
            'note' => 'Cash payments received today',
            'tone' => 'emerald',
            'icon' => 'cash',
        ],
        [
            'label' => 'Sales collections — bank',
            'key' => 'today_sales_bank',
            'note' => 'POS, transfer and other non-cash payments',
            'tone' => 'blue',
            'icon' => 'bank',
        ],
        [
            'label' => 'Expenses — cash',
            'key' => 'expenses_cash',
            'note' => 'Expenses paid with physical cash',
            'tone' => 'rose',
            'icon' => 'cash',
        ],
        [
            'label' => 'Expenses — bank',
            'key' => 'expenses_bank',
            'note' => 'Expenses paid by POS, transfer or other non-cash method',
            'tone' => 'rose',
            'icon' => 'bank',
        ],
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Page identity and primary actions --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
            <a href="{{ route('stores.show', $store) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 transition hover:text-white sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
                {{ $store->name }}
            </a>

            <div class="mt-3 flex flex-col gap-4 sm:mt-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-200 sm:text-xs">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="M7 3v4M17 3v4"/></svg>
                            Sales
                        </span>
                        <span class="rounded-full bg-cyan-400/15 px-2.5 py-1 text-[10px] font-bold text-cyan-300 sm:text-xs">
                            {{ $store->name }}
                        </span>
                        @if ($outstandingOnly)
                            <span class="rounded-full bg-amber-400/15 px-2.5 py-1 text-[10px] font-bold text-amber-300 sm:text-xs">
                                Outstanding
                            </span>
                        @endif
                    </div>
                    <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">
                        {{ $outstandingOnly ? 'Outstanding sales' : 'Sales' }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-400 sm:text-sm">Sales recorded at {{ $store->name }}.</p>
                </div>

                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                    <nav class="grid grid-cols-2 rounded-xl border border-white/15 bg-white/10 p-1 sm:inline-flex" aria-label="Sales filter">
                        <a
                            href="{{ route('stores.sales.index', $store) }}"
                            class="rounded-lg px-3 py-2 text-center text-xs font-semibold transition sm:text-sm {{ $outstandingOnly ? 'text-slate-200 hover:bg-white/10 hover:text-white' : 'bg-white text-slate-950 shadow-sm' }}"
                            @unless ($outstandingOnly) aria-current="page" @endunless
                        >All sales</a>
                        <a
                            href="{{ route('stores.sales.outstanding', $store) }}"
                            class="rounded-lg px-3 py-2 text-center text-xs font-semibold transition sm:text-sm {{ $outstandingOnly ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}"
                            @if ($outstandingOnly) aria-current="page" @endif
                        >Outstanding</a>
                    </nav>

                    @if ($canRecordSales)
                        <a href="{{ route('stores.sales.create', $store) }}"
                           class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-xs font-bold text-emerald-950 shadow-sm transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 sm:w-auto sm:text-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h2l2.2 11h9.5l2-7H7"/><circle cx="10" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/></svg>
                            Record sale
                        </a>
                    @else
                        <span
                            aria-disabled="true"
                            title="Record opening balances before recording sales, or wait until the next business day."
                            class="inline-flex min-h-12 w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-emerald-500/20 px-4 py-3 text-xs font-bold text-emerald-200 opacity-60 sm:w-auto sm:text-sm"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h2l2.2 11h9.5l2-7H7"/><circle cx="10" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/></svg>
                            Record sale
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </section>

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
    <section aria-label="Sales dashboard" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 bg-gradient-to-r from-emerald-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Sales overview</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                        Collections are based on payments received, not the full value of unpaid sales.
                    </p>
                </div>
            </div>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Store performance</span>
        </div>

        <details class="group">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 border-y border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-6">
                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700">
                        Sales performance
                    </span>
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold leading-none text-emerald-700 ring-1 ring-inset ring-emerald-200">
                        Sales today · ₦{{ number_format((float) $salesDashboard['today_sales'], 2) }}
                    </span>
                </div>
                <span class="flex items-center gap-2 text-xs font-medium text-slate-500">
                    <span class="group-open:hidden">Show</span>
                    <span class="hidden group-open:inline">Hide</span>
                    <svg class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m5 7 5 5 5-5"/>
                    </svg>
                </span>
            </summary>

            <div class="space-y-5 p-3 sm:space-y-6 sm:p-5">
                {{-- Performance tiles --}}
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($salesMetrics as $metric)
                        @php
                            $hasComparison = isset($metric['change']);
                            $change = $hasComparison ? (string) $salesDashboard[$metric['change']] : '0.00';
                            $isUp = bccomp($change, '0.00', 2) >= 0;
                            $t = $tones[! $hasComparison ? 'indigo' : ($isUp ? 'emerald' : 'rose')];
                            $changeBadge = $isUp ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800';
                        @endphp

                        <article class="min-w-0 overflow-hidden rounded-xl border p-3 shadow-sm sm:p-4 {{ $t['card'] }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-wide {{ $t['label'] }}">{{ $metric['label'] }}</p>
                                    <p class="mt-2 break-words text-lg font-bold tabular-nums tracking-tight sm:text-xl {{ $t['value'] }}">
                                        ₦{{ number_format((float) $salesDashboard[$metric['key']], 2) }}
                                    </p>
                                </div>
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $t['chip'] }}">
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
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold {{ $changeBadge }}">
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

                {{-- Cash and bank position --}}
                <div class="space-y-4">
                    <div class="flex flex-col gap-1 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Cash and bank position
                        </h3>

                        @if ($salesDashboard['is_closed'])
                            <span class="inline-flex w-fit rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                Business day closed — actual closing count
                            </span>
                        @else
                            <span class="inline-flex w-fit rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                Live expected position
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($cashMetrics as $metric)
                            @php $t = $tones[$metric['tone']]; @endphp
                            <article class="min-w-0 rounded-xl border p-3 shadow-sm sm:p-4 {{ $t['card'] }}">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $t['chip'] }}">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            @if ($metric['icon'] === 'bank')
                                                <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h10M7 14h5"/>
                                            @else
                                                <path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/>
                                            @endif
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-bold uppercase tracking-wide {{ $t['label'] }}">
                                            {{ $metric['label'] }}
                                        </p>
                                        <p class="mt-1 break-words text-lg font-bold tabular-nums tracking-tight sm:text-xl {{ $t['value'] }}">
                                            ₦{{ number_format((float) $salesDashboard[$metric['key']], 2) }}
                                        </p>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $metric['note'] }}
                                </p>
                            </article>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @if ($salesDashboard['is_closed'])
                            <article class="rounded-xl border border-rose-100 bg-gradient-to-br from-rose-50 to-white p-3 shadow-sm sm:p-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-rose-700">
                                    Actual closing cash
                                </p>
                                <p class="mt-2 text-lg font-bold tabular-nums text-rose-950 sm:text-xl">
                                    ₦{{ number_format((float) $salesDashboard['closing_cash'], 2) }}
                                </p>
                                <p class="mt-2 text-xs text-slate-500">
                                    Physical cash counted and recorded at closing
                                </p>
                            </article>

                            <article class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-3 shadow-sm sm:p-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-blue-700">
                                    Actual closing bank
                                </p>
                                <p class="mt-2 text-lg font-bold tabular-nums text-blue-950 sm:text-xl">
                                    ₦{{ number_format((float) $salesDashboard['closing_bank'], 2) }}
                                </p>
                                <p class="mt-2 text-xs text-slate-500">
                                    Bank balance counted and recorded at closing
                                </p>
                            </article>

                            <article class="rounded-xl border border-slate-200 bg-slate-50 p-3 shadow-sm sm:p-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-600">
                                    Actual closing total
                                </p>
                                <p class="mt-2 text-lg font-bold tabular-nums text-slate-950 sm:text-xl">
                                    ₦{{ number_format((float) $salesDashboard['closing_total'], 2) }}
                                </p>
                                <p class="mt-2 text-xs text-slate-500">
                                    Recorded closing cash plus recorded closing bank
                                </p>
                            </article>
                        @else
                            <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 shadow-sm sm:p-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">
                                    Expected cash
                                </p>
                                <p class="mt-2 text-lg font-bold tabular-nums text-emerald-950 sm:text-xl">
                                    ₦{{ number_format((float) $salesDashboard['expected_cash'], 2) }}
                                </p>
                                <p class="mt-2 text-xs text-emerald-800">
                                    Opening cash + confirmed cash funds + cash collections − cash expenses
                                </p>
                            </article>

                            <article class="rounded-xl border border-blue-200 bg-blue-50 p-3 shadow-sm sm:p-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-blue-700">
                                    Expected bank
                                </p>
                                <p class="mt-2 text-lg font-bold tabular-nums text-blue-950 sm:text-xl">
                                    ₦{{ number_format((float) $salesDashboard['expected_bank'], 2) }}
                                </p>
                                <p class="mt-2 text-xs text-blue-800">
                                    Opening bank + confirmed bank funds + non-cash collections − non-cash expenses
                                </p>
                            </article>

                            <article class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-600">
                                    Expected cash and bank total
                                </p>
                                <p class="mt-2 text-lg font-bold tabular-nums text-slate-950 sm:text-xl">
                                    ₦{{ number_format((float) $salesDashboard['expected_total'], 2) }}
                                </p>
                                <p class="mt-2 text-xs text-slate-500">
                                    Expected cash plus expected bank
                                </p>
                            </article>
                        @endif
                    </div>

                    <div class="flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-600">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v5h1"/></svg>
                        <p>
                            Only confirmed fund assignments affect this position. Funds included in the opening balance are not counted again as intraday receipts. Closing figures are the actual amounts recorded at close.
                        </p>
                    </div>
                </div>
            </div>
        </details>
    </section>

    {{-- Filter --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <details>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
                <span class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 5h18l-7 8v6l-4 2v-8L3 5Z"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-base font-bold text-slate-900">Filter sales records</span>
                        <span class="mt-0.5 block text-xs font-normal text-slate-500">Choose date or date range</span>
                    </span>
                </span>
                <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 7 5 5 5-5"/></svg>
            </summary>

            <div class="border-t border-slate-100 p-3 sm:p-5">
                <form
                    data-ajax-list-form
                    method="GET"
                    action="{{ $outstandingOnly
                        ? route('stores.sales.outstanding', $store)
                        : route('stores.sales.index', $store) }}"
                >
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
                        <label class="block text-sm font-semibold text-indigo-800">
                            Specific date
                            <input type="date" name="sales_date" value="{{ old('sales_date', $salesFilters['sales_date'] ?? '') }}"
                                   class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </label>
                        <label class="block text-sm font-semibold text-indigo-800">
                            From
                            <input type="date" name="sales_from" value="{{ old('sales_from', $salesFilters['sales_from'] ?? '') }}"
                                   class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </label>
                        <label class="block text-sm font-semibold text-indigo-800">
                            To
                            <input type="date" name="sales_to" value="{{ old('sales_to', $salesFilters['sales_to'] ?? '') }}"
                                   class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </label>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center">
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
    </section>

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