@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

        {{-- Store identity and primary actions --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
                @can('viewAny', App\Models\Store::class)
                    <a href="{{ route('stores.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 transition hover:text-white sm:text-sm">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
                        Stores
                    </a>
                @endcan


                <div class="mt-3 flex flex-col gap-4 sm:mt-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-200 sm:text-xs">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                                {{ $store->isWarehouse() ? 'General store' : 'Store' }} · {{ $store->code }}
                            </span>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold sm:text-xs {{ $store->is_active ? 'bg-emerald-400/15 text-emerald-300' : 'bg-rose-400/15 text-rose-300' }}">
                                {{ $store->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">{{ $store->name }}</h1>
                        <p class="mt-1 text-xs text-slate-400 sm:text-sm">Store management and daily operations</p>
                    </div>

                    {{-- Primary actions: compact icon-first controls on mobile --}}
                    <div class="grid w-full grid-cols-3 gap-2 sm:w-auto sm:min-w-[360px] sm:gap-3">
                        <a href="{{ route('stock.index', ['store_id' => $store->id]) }}" class="group flex min-h-[68px] flex-col items-center justify-center gap-1.5 rounded-xl border border-white/15 bg-white/10 px-2 py-2 text-center transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60 sm:min-h-12 sm:flex-row sm:gap-2 sm:px-4 sm:py-3">
                            <svg class="h-5 w-5 text-cyan-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 7.5 9-4.5 9 4.5v9L12 21l-9-4.5v-9Z"/><path d="m3.5 7.7 8.5 4.5 8.5-4.5M12 12.2V21"/></svg>
                            <span class="text-[10px] font-bold leading-tight text-white sm:text-xs">View stock</span>
                        </a>

                        @if ($dailyCashToday && $dailyCashToday->closed_by === null)
                            <a href="{{ route('stores.sales.create', $store) }}" class="group flex min-h-[68px] flex-col items-center justify-center gap-1.5 rounded-xl bg-emerald-500 px-2 py-2 text-center text-emerald-950 shadow-sm transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 sm:min-h-12 sm:flex-row sm:gap-2 sm:px-4 sm:py-3">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h2l2.2 11h9.5l2-7H7"/><circle cx="10" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/></svg>
                                <span class="text-[10px] font-bold leading-tight sm:text-xs">Record sale</span>
                            </a>
                        @else
                            <span aria-disabled="true" title="{{ $dailyCashToday ? 'This business day is closed.' : 'Record opening balances before recording sales.' }}" class="flex min-h-[68px] cursor-not-allowed flex-col items-center justify-center gap-1.5 rounded-xl bg-emerald-500/20 px-2 py-2 text-center text-emerald-200 opacity-60 sm:min-h-12 sm:flex-row sm:gap-2 sm:px-4 sm:py-3">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h2l2.2 11h9.5l2-7H7"/><circle cx="10" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/></svg>
                                <span class="text-[10px] font-bold leading-tight sm:text-xs">Sale locked</span>
                            </span>
                        @endif

                        @if ($canOperateStock && $dailyCashToday && $dailyCashToday->closed_by === null)
                            <a href="{{ route('stores.expenses.index', $store) }}" class="group flex min-h-[68px] flex-col items-center justify-center gap-1.5 rounded-xl border border-white/15 bg-white/10 px-2 py-2 text-center transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60 sm:min-h-12 sm:flex-row sm:gap-2 sm:px-4 sm:py-3">
                                <svg class="h-5 w-5 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>
                                <span class="text-[10px] font-bold leading-tight text-white sm:text-xs">Expenses</span>
                            </a>
                        @else
                            <span aria-disabled="true" title="{{ $dailyCashToday ? 'This business day is closed.' : 'Record opening balances before recording expenses.' }}" class="flex min-h-[68px] cursor-not-allowed flex-col items-center justify-center gap-1.5 rounded-xl border border-white/10 bg-white/5 px-2 py-2 text-center opacity-50 sm:min-h-12 sm:flex-row sm:gap-2 sm:px-4 sm:py-3">
                                <svg class="h-5 w-5 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>
                                <span class="text-[10px] font-bold leading-tight text-white sm:text-xs">Expenses locked</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Secondary shortcuts grouped by purpose --}}
            <div class="grid gap-3 bg-slate-50 p-3 sm:grid-cols-2 sm:p-4 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v5H3zM5 8v13h14V8M9 12h6M9 16h6"/></svg></span>
                        Store operations
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        @can('viewDashboard', $store)
                            <a href="{{ route('stores.dashboard', $store) }}" class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg bg-indigo-50 px-2.5 text-xs font-semibold text-indigo-800 transition hover:bg-indigo-100"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z"/></svg>Dashboard</a>
                        @endcan
                        @can('viewStore', [\App\Models\StoreRemittance::class, $store])
                            <a href="{{ route('stores.remittances.manage', $store) }}" class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg bg-cyan-50 px-2.5 text-xs font-semibold text-cyan-800 transition hover:bg-cyan-100"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/></svg>Remittances</a>
                        @endcan
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h10M7 14h5"/></svg></span>
                        Finance
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        @can('viewStore', [\App\Models\StoreFundAssignment::class, $store])
                            <a href="{{ route('stores.fund-assignments.index', $store) }}" class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>Assigned funds</a>
                        @endcan
                        @can('createDirect', [\App\Models\StoreFundAssignment::class, $store])
                            <a href="{{ route('stores.fund-assignments.index', $store) }}" class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-2.5 text-xs font-semibold text-white transition hover:bg-emerald-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Assign funds</a>
                        @endcan
                    </div>
                </div>


                @canany(['update', 'toggleActive'], $store)
                    <div class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 4 6v5c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                            Management
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            @can('update', $store)
                                <a href="{{ route('stores.edit', $store) }}" class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg bg-slate-100 px-2.5 text-xs font-semibold text-slate-800 transition hover:bg-slate-200"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 4 4 4L9 19H5v-4L16 4Z"/><path d="m14 6 4 4"/></svg>Edit store</a>
                            @endcan
                            @can('toggleActive', $store)
                                @if (! $store->is_active)
                                    <form method="POST" action="{{ route('stores.activate', $store) }}" class="min-w-0">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>Activate</button>
                                    </form>
                                @elseif (! $store->isWarehouse())
                                    <form method="POST" action="{{ route('stores.deactivate', $store) }}" class="min-w-0">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg bg-rose-50 px-2.5 text-xs font-semibold text-rose-800 transition hover:bg-rose-100"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>Deactivate</button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                @endcanany
            </div>
        </section>

        {{-- Feedback --}}
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('is_active'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first('is_active') }}
            </div>
        @endif

        {{-- Store details --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/><path d="M9 9h.01M15 9h.01"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-slate-900">Store details</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Contact and record information</p>
                </div>
            </div>
            <dl class="grid gap-3 p-3 sm:grid-cols-2 sm:gap-4 sm:p-5">
                <div class="flex min-w-0 items-start gap-3 rounded-xl border border-sky-100 bg-sky-50/70 p-3 sm:p-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3h14v18H5zM8 7h8M8 11h8"/><path d="M8 15h3"/></svg></span>
                    <div class="min-w-0"><dt class="text-[11px] font-bold uppercase tracking-wide text-sky-700">Phone</dt><dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $store->phone ?? '—' }}</dd></div>
                </div>
                <div class="flex min-w-0 items-start gap-3 rounded-xl border border-amber-100 bg-amber-50/70 p-3 sm:p-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                    <div class="min-w-0"><dt class="text-[11px] font-bold uppercase tracking-wide text-amber-700">Address</dt><dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $store->address ?? '—' }}</dd></div>
                </div>
                @can('update', $store)
                    <div class="flex min-w-0 items-start gap-3 rounded-xl border border-violet-100 bg-violet-50/70 p-3 sm:p-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                        <div class="min-w-0"><dt class="text-[11px] font-bold uppercase tracking-wide text-violet-700">Created</dt><dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $store->created_at->format('d M Y') }}@if ($store->creator) <span class="font-normal text-slate-600">by {{ $store->creator->name }}</span>@endif</dd></div>
                    </div>
                @endcan
            </dl>
        </section>

        {{-- Daily opening and closing balances --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gradient-to-r from-emerald-50 via-white to-blue-50 px-4 py-4 sm:px-6 sm:py-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h10M7 14h5"/><path d="M12 2v3"/></svg></span>
                    <div><h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Daily cash &amp; bank balances</h2><p class="mt-1 text-xs text-slate-500 sm:text-sm">Opening funds, daily activity and end-of-day reconciliation.</p></div>
                </div>
            </div>

            @if ($canOperateStock)
                <div class="border-b border-slate-200 bg-slate-50/70 px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500"><svg class="h-3.5 w-3.5 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 9h18"/></svg> Today's business record</p>
                            <h3 class="mt-1 text-base font-semibold text-slate-900">
                                {{ today()->format('l, d F Y') }}
                            </h3>
                        </div>

                        @if (! $dailyCashToday)
                            <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                Awaiting opening
                            </span>
                        @elseif ($dailyCashToday->closed_by === null)
                            <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                Day in progress
                            </span>
                        @else
                            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                Day closed
                            </span>
                        @endif
                    </div>

                    @if (! $dailyCashToday)
                        <div class="mt-4 rounded-xl border border-emerald-100 bg-white p-3 shadow-sm sm:mt-5 sm:p-5">
                            <h4 class="flex items-center gap-2 text-sm font-bold text-slate-900"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg></span>Opening balances</h4>
                            <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                                Enter the cash and bank balances available at the start of the business day.
                            </p>

                            <form method="POST" action="{{ route('stores.daily-cash.open', $store) }}"
                                  class="mt-4 grid gap-3 sm:mt-5 sm:grid-cols-2 sm:gap-4">
                                @csrf

                                <div>
                                    <label for="opening_cash" class="block text-sm font-semibold text-emerald-800">
                                        Opening cash
                                    </label>
                                    <div class="relative mt-1.5">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                        <input id="opening_cash" name="opening_cash" type="number"
                                               min="0" step="0.01" inputmode="decimal"
                                               value="{{ old('opening_cash') }}" required
                                               aria-invalid="{{ $errors->has('opening_cash') ? 'true' : 'false' }}"
                                               class="block w-full rounded-lg border {{ $errors->has('opening_cash') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    </div>
                                    @error('opening_cash')
                                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="opening_bank" class="block text-sm font-semibold text-blue-800">
                                        Opening bank balance
                                    </label>
                                    <div class="relative mt-1.5">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                        <input id="opening_bank" name="opening_bank" type="number"
                                               min="0" step="0.01" inputmode="decimal"
                                               value="{{ old('opening_bank') }}" required
                                               aria-invalid="{{ $errors->has('opening_bank') ? 'true' : 'false' }}"
                                               class="block w-full rounded-lg border {{ $errors->has('opening_bank') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    </div>
                                    @error('opening_bank')
                                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-xs text-slate-500">
                                        Opening balances can only be recorded once for today.
                                    </p>
                                    <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">
                                        Record opening balances
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="mt-4 grid gap-3 sm:mt-5 sm:grid-cols-2">
                            <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-3 shadow-sm sm:p-4">
                                <p class="flex items-center gap-1.5 text-xs font-bold text-emerald-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>Opening cash</p>
                                <p class="mt-2 text-lg font-bold tracking-tight text-emerald-950 sm:text-xl">
                                    ₦{{ number_format((float) $dailyCashToday->opening_cash, 2) }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-3 shadow-sm sm:p-4">
                                <p class="flex items-center gap-1.5 text-xs font-bold text-blue-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h10M7 14h5"/></svg>Opening bank</p>
                                <p class="mt-2 text-lg font-bold tracking-tight text-blue-950 sm:text-xl">
                                    ₦{{ number_format((float) $dailyCashToday->opening_bank, 2) }}
                                </p>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-slate-500">
                            Opened by
                            <span class="font-medium text-slate-700">{{ $dailyCashToday->openedBy?->name ?? 'Unknown' }}</span>
                            on {{ $dailyCashToday->created_at->format('d M Y') }}
                            at {{ $dailyCashToday->created_at->format('h:i A') }}.
                        </p>

                    @endif
                </div>
            @endif

            <div class="px-4 py-4 sm:px-6 sm:py-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="flex items-center gap-2 text-base font-bold text-slate-900"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h18v17H3zM8 2v4M16 2v4M3 9h18"/></svg></span>Daily history</h3>
                        <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                            Previous opening and closing records for this store.
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h18v17H3zM8 2v4M16 2v4M3 9h18"/></svg>{{ $dailyCashHistory->count() }} {{ \Illuminate\Support\Str::plural('record', $dailyCashHistory->count()) }}</span>
                </div>

                @if ($dailyCashHistory->isEmpty())
                    <div class="mt-4 rounded-xl border border-dashed border-amber-200 bg-amber-50/40 px-4 py-8 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h18v17H3zM8 2v4M16 2v4M3 9h18"/></svg></span><p class="mt-3 text-sm font-bold text-slate-700">No daily records yet</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Opening and closing balances will appear here once recorded.
                        </p>
                    </div>
                @else
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                        {{-- Mobile history cards --}}
                        <div class="space-y-3 bg-slate-50/50 p-3 md:hidden">
                            @foreach ($dailyCashHistory as $record)
                                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <h4 class="text-sm font-semibold text-slate-900">
                                            {{ $record->business_date->format('d M Y') }}
                                        </h4>

                                        @if ($record->closed_by)
                                            <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                Closed
                                            </span>
                                        @else
                                            <span class="shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                                Open
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-3 grid grid-cols-2 gap-3">
                                        <div class="rounded-lg border border-emerald-100 bg-emerald-50/70 p-2.5">
                                            <p class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>Opening cash</p>
                                            <p class="mt-1 text-sm font-bold tabular-nums text-emerald-900">
                                                ₦{{ number_format((float) $record->opening_cash, 2) }}
                                            </p>
                                        </div>
                                        <div class="rounded-lg border border-blue-100 bg-blue-50/70 p-2.5">
                                            <p class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h10M7 14h5"/></svg>Opening bank</p>
                                            <p class="mt-1 text-sm font-bold tabular-nums text-blue-900">
                                                ₦{{ number_format((float) $record->opening_bank, 2) }}
                                            </p>
                                        </div>
                                        <div class="rounded-lg border border-rose-100 bg-rose-50/70 p-2.5">
                                            <p class="inline-flex items-center gap-1 text-xs font-semibold text-rose-700"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>Closing cash</p>
                                            <p class="mt-1 text-sm font-bold tabular-nums text-rose-900">
                                                {{ $record->closing_cash === null ? '—' : '₦'.number_format((float) $record->closing_cash, 2) }}
                                            </p>
                                        </div>
                                        <div class="rounded-lg border border-blue-100 bg-blue-50/70 p-2.5">
                                            <p class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h10M7 14h5"/></svg>Closing bank</p>
                                            <p class="mt-1 text-sm font-bold tabular-nums text-blue-900">
                                                {{ $record->closing_bank === null ? '—' : '₦'.number_format((float) $record->closing_bank, 2) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 space-y-2 border-t border-slate-100 pt-3 text-xs">
                                        <div class="flex items-start justify-between gap-3">
                                            <span class="text-slate-500">Opened by</span>
                                            <span class="text-right font-medium text-slate-800">
                                                {{ $record->openedBy?->name ?? '—' }}
                                                <span class="mt-0.5 block font-normal text-slate-500">
                                                    {{ $record->created_at->format('h:i A') }}
                                                </span>
                                            </span>
                                        </div>

                                        <div class="flex items-start justify-between gap-3">
                                            <span class="text-slate-500">Closed by</span>
                                            <span class="text-right font-medium text-slate-800">
                                                @if ($record->closed_by)
                                                    {{ $record->closedBy?->name ?? 'Unknown' }}
                                                    <span class="mt-0.5 block font-normal text-slate-500">
                                                        {{ $record->updated_at->format('h:i A') }}
                                                    </span>
                                                @else
                                                    <span class="font-normal text-slate-400">Not closed</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        {{-- Tablet and desktop history table --}}
                        <div class="hidden overflow-x-auto md:block">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                <thead class="bg-gradient-to-r from-slate-50 via-white to-indigo-50">
                                    <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <th scope="col" class="whitespace-nowrap px-4 py-3">Business date</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-emerald-700"><span class="inline-flex items-center justify-end gap-1"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>Opening cash</span></th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-blue-700"><span class="inline-flex items-center justify-end gap-1"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h10M7 14h5"/></svg>Opening bank</span></th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-rose-700"><span class="inline-flex items-center justify-end gap-1"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>Closing cash</span></th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-blue-700"><span class="inline-flex items-center justify-end gap-1"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h10M7 14h5"/></svg>Closing bank</span></th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3">Opened by</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3">Closed by</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($dailyCashHistory as $record)
                                        <tr class="transition hover:bg-slate-50/70">
                                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">
                                                {{ $record->business_date->format('d M Y') }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums font-semibold text-emerald-800">
                                                ₦{{ number_format((float) $record->opening_cash, 2) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums font-semibold text-blue-800">
                                                ₦{{ number_format((float) $record->opening_bank, 2) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums font-semibold text-rose-800">
                                                {{ $record->closing_cash === null ? '—' : '₦'.number_format((float) $record->closing_cash, 2) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums font-semibold text-blue-800">
                                                {{ $record->closing_bank === null ? '—' : '₦'.number_format((float) $record->closing_bank, 2) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <span class="font-medium text-slate-800">{{ $record->openedBy?->name ?? '—' }}</span>
                                                <span class="mt-0.5 block text-xs text-slate-500">
                                                    {{ $record->created_at->format('h:i A') }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                @if ($record->closed_by)
                                                    <span class="font-medium text-slate-800">{{ $record->closedBy?->name ?? 'Unknown' }}</span>
                                                    <span class="mt-0.5 block text-xs text-slate-500">
                                                        {{ $record->updated_at->format('h:i A') }}
                                                    </span>
                                                @else
                                                    <span class="text-slate-400">Not closed</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                @if ($record->closed_by)
                                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                        Closed
                                                    </span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                                        Open
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        @if ($canOperateStock)
            {{-- Store stock operations --}}
            <section class="overflow-hidden rounded-2xl border border-indigo-100 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-indigo-50 via-white to-cyan-50 px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z"/><path d="M3 7.5V17l9 4 9-4V7.5M12 12v9"/><path d="m7 5 9 4.5"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Stock operations</h2>
                            <p class="mt-1 text-xs text-slate-600 sm:text-sm">Manage inventory, processing, transfers, sales and customer records for {{ $store->name }}.</p>
                        </div>
                        <a href="{{ route('stock.index', ['store_id' => $store->id]) }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-indigo-700 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-800 sm:px-4 sm:text-sm">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v14H4zM8 9h8M8 13h8M8 17h4"/></svg><span>View stock</span>
                        </a>
                    </div>
                </div>

                @if ($incomingConsignments->isNotEmpty())
                    <div class="mx-4 mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 sm:mx-6 sm:p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="1.5"/><circle cx="18" cy="18" r="1.5"/></svg></div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="text-sm font-bold text-amber-900">Incoming consignments awaiting acknowledgement</h3>
                                    <span class="rounded-full bg-amber-200/70 px-2.5 py-1 text-xs font-bold text-amber-900">{{ $incomingConsignments->count() }} pending</span>
                                </div>
                                <p class="mt-1 text-xs text-amber-800">Please confirm receipt after checking the items and quantities delivered.</p>
                            </div>
                        </div>
                        <div class="mt-3 space-y-2.5">
                            @foreach ($incomingConsignments as $incomingConsignment)
                                <div class="rounded-lg border border-amber-200 bg-white p-3 sm:flex sm:items-center sm:justify-between sm:gap-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-slate-900">{{ $incomingConsignment->reference }}</p>
                                        <p class="mt-1 text-xs text-slate-600">From {{ $incomingConsignment->sourceStore->name }}</p>
                                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500 sm:text-xs">Dispatched {{ $incomingConsignment->dispatched_at->format('d M Y, h:i A') }} · {{ $incomingConsignment->pending_line_count }} outstanding line{{ $incomingConsignment->pending_line_count === 1 ? '' : 's' }} · {{ number_format((float) $incomingConsignment->pending_quantity, 3) }} units remaining</p>
                                    </div>
                                    <a href="{{ route('stock.consignments.acknowledgements.create', $incomingConsignment) }}" class="mt-3 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-amber-600 px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-700 sm:mt-0 sm:w-auto sm:shrink-0 sm:text-sm">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg> Acknowledge receipt
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="px-4 pb-4 pt-4 sm:px-6 sm:pb-6">
                    <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Inventory & store activity</p>
                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-2 sm:gap-3 lg:grid-cols-3">
                        @if (auth()->user()->activeStoreAssignment?->role === \App\Models\StoreStaff::ROLE_COORDINATOR)
                            <a href="{{ route('stock.items.create') }}" class="group rounded-xl border border-emerald-100 bg-emerald-50/60 p-3 transition hover:border-emerald-300 hover:bg-emerald-50 sm:p-4">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></span>
                                <span class="mt-2 block text-xs font-bold text-slate-900 sm:text-sm">Register stock</span><span class="mt-1 block text-[10px] leading-relaxed text-slate-600 sm:text-xs">Add item, opening quantity, cost and photos</span>
                            </a>
                        @endif
                        <a href="{{ route('stock.processings.create') }}" class="group rounded-xl border border-violet-100 bg-violet-50/60 p-3 transition hover:border-violet-300 hover:bg-violet-50 sm:p-4">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-100 text-violet-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7M21 3l-9 9"/><path d="M5 7a7 7 0 0 0 0 10l2 2a7 7 0 0 0 10 0"/></svg></span>
                            <span class="mt-2 block text-xs font-bold text-slate-900 sm:text-sm">Stock processing</span><span class="mt-1 block text-[10px] leading-relaxed text-slate-600 sm:text-xs">Record raw materials and processed output</span>
                        </a>
                        <a href="{{ route('stock.consignments.create') }}" class="group rounded-xl border border-sky-100 bg-sky-50/60 p-3 transition hover:border-sky-300 hover:bg-sky-50 sm:p-4">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-100 text-sky-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="1.5"/><circle cx="18" cy="18" r="1.5"/></svg></span>
                            <span class="mt-2 block text-xs font-bold text-slate-900 sm:text-sm">Dispatch consignment</span><span class="mt-1 block text-[10px] leading-relaxed text-slate-600 sm:text-xs">Transfer stock to another store</span>
                        </a>
                        <a href="{{ route('stores.sales.index', $store) }}" class="group rounded-xl border border-rose-100 bg-rose-50/60 p-3 transition hover:border-rose-300 hover:bg-rose-50 sm:p-4">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-100 text-rose-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/><path d="M7 3v4M17 3v4"/></svg></span>
                            <span class="mt-2 block text-xs font-bold text-slate-900 sm:text-sm">Sales</span><span class="mt-1 block text-[10px] leading-relaxed text-slate-600 sm:text-xs">Review sales, records and payments</span>
                        </a>
                        <a href="{{ route('stores.customers.index', $store) }}" class="group rounded-xl border border-cyan-100 bg-cyan-50/60 p-3 transition hover:border-cyan-300 hover:bg-cyan-50 sm:p-4">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-cyan-100 text-cyan-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M16 5a3 3 0 0 1 0 6M18 14a4 4 0 0 1 3 4v2"/></svg></span>
                            <span class="mt-2 block text-xs font-bold text-slate-900 sm:text-sm">Customers</span><span class="mt-1 block text-[10px] leading-relaxed text-slate-600 sm:text-xs">Manage store customer records</span>
                        </a>
                    </div>
                </div>
            </section>
        @endif

        @if ($canOperateStock && $dailyCashToday)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-rose-50 via-white to-amber-50 pb-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 ring-1 ring-rose-200"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/><path d="m16 16 2 2 3-4"/></svg></span>
                    <div><h2 class="text-base font-bold text-slate-900 sm:text-lg">End-of-day reconciliation</h2><p class="mt-1 text-xs text-slate-500 sm:text-sm">Record and confirm the cash and bank balances after today's operations.</p></div>
                </div>

                <div class="pt-1">
                        @if ($dailyCashToday->closed_by === null)
                            <div class="mt-4 rounded-xl border border-rose-100 bg-white p-3 shadow-sm sm:mt-5 sm:p-5">
                                <h4 class="flex items-center gap-2 text-sm font-bold text-slate-900"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-100 text-rose-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg></span>Closing balances</h4>
                                <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                                    Enter the cash and bank balances at the end of the business day.
                                </p>

                                <form method="POST" action="{{ route('stores.daily-cash.close', $store) }}"
                                      class="mt-4 grid gap-3 sm:mt-5 sm:grid-cols-2 sm:gap-4">
                                    @csrf

                                    <div>
                                        <label for="closing_cash" class="block text-sm font-semibold text-rose-800">
                                            Closing cash
                                        </label>
                                        <div class="relative mt-1.5">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                            <input id="closing_cash" name="closing_cash" type="number"
                                                   min="0" step="0.01" inputmode="decimal"
                                                   value="{{ old('closing_cash') }}" required
                                                   aria-invalid="{{ $errors->has('closing_cash') ? 'true' : 'false' }}"
                                                   class="block w-full rounded-lg border {{ $errors->has('closing_cash') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm text-slate-900 shadow-sm focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500">
                                        </div>
                                        @error('closing_cash')
                                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="closing_bank" class="block text-sm font-semibold text-blue-800">
                                            Closing bank balance
                                        </label>
                                        <div class="relative mt-1.5">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                            <input id="closing_bank" name="closing_bank" type="number"
                                                   min="0" step="0.01" inputmode="decimal"
                                                   value="{{ old('closing_bank') }}" required
                                                   aria-invalid="{{ $errors->has('closing_bank') ? 'true' : 'false' }}"
                                                   class="block w-full rounded-lg border {{ $errors->has('closing_bank') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                        @error('closing_bank')
                                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-xs text-slate-500">
                                            Closing the day locks this record from further changes.
                                        </p>
                                        <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">
                                            Close business day
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="mt-5 overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="flex items-center gap-2 text-sm font-bold text-emerald-900"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg></span>Closing balances</h4>
                                    <span class="text-xs font-medium text-emerald-700">Finalised</span>
                                </div>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-lg border border-emerald-100 bg-white/80 p-3">
                                        <p class="text-xs font-bold text-emerald-700">Closing cash</p>
                                        <p class="mt-1 text-base font-semibold text-emerald-950 sm:text-lg">
                                            ₦{{ number_format((float) $dailyCashToday->closing_cash, 2) }}
                                        </p>
                                    </div>
                                    <div class="rounded-lg border border-blue-100 bg-white/80 p-3">
                                        <p class="text-xs font-bold text-blue-700">Closing bank</p>
                                        <p class="mt-1 text-base font-semibold text-blue-950 sm:text-lg">
                                            ₦{{ number_format((float) $dailyCashToday->closing_bank, 2) }}
                                        </p>
                                    </div>
                                </div>
                                <p class="mt-3 border-t border-emerald-200 pt-3 text-xs text-emerald-800">
                                    Closed by
                                    <span class="font-medium">{{ $dailyCashToday->closedBy?->name ?? 'Unknown' }}</span>
                                    on {{ $dailyCashToday->updated_at->format('d M Y') }}
                                    at {{ $dailyCashToday->updated_at->format('h:i A') }}.
                                </p>
                            </div>
                        @endif
                </div>
            </section>
        @endif

        {{-- Staff --}}
        @include('stores.partials.staff-panel')
    </div>
@endsection
