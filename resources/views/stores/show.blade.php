@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-8 lg:px-8">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                @can('viewAny', App\Models\Store::class)
                    <a href="{{ route('stores.index') }}" class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900">
                        ← Back to Stores
                    </a>
                @endcan

                <div class="mt-3 flex flex-wrap items-center gap-2 sm:mt-4 sm:gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $store->name }}</h1>

                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $store->isWarehouse() ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700' }}">
                        {{ $store->isWarehouse() ? 'General Store' : 'Store' }}
                    </span>

                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $store->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                        {{ $store->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <p class="mt-1 text-xs text-slate-500 sm:text-sm">Code {{ $store->code }}</p>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center">
                @can('viewDashboard', $store)
                    <a href="{{ route('stores.dashboard', $store) }}" class="oy-btn oy-btn-secondary">
                        Store dashboard
                    </a>
                @endcan


                @if ($dailyCashToday && $dailyCashToday->closed_by === null)
                    <a href="{{ route('stores.sales.create', $store) }}"
                       class="oy-btn oy-btn-primary">
                        Record sale
                    </a>
                @else
                    <span
                        aria-disabled="true"
                        title="{{ $dailyCashToday ? 'This business day is closed.' : 'Record opening balances before recording sales.' }}"
                        class="oy-btn oy-btn-primary cursor-not-allowed opacity-50"
                    >
                        Record sale
                    </span>
                @endif
                @if ($canOperateStock && $dailyCashToday && $dailyCashToday->closed_by === null)
                    <a href="{{ route('stores.expenses.index', $store) }}"
                       class="oy-btn oy-btn-secondary">
                        Expenses
                    </a>
                @else
                    <span
                        aria-disabled="true"
                        title="{{ $dailyCashToday ? 'This business day is closed.' : 'Record opening balances before recording expenses.' }}"
                        class="oy-btn oy-btn-secondary cursor-not-allowed opacity-50"
                    >
                        Expenses
                    </span>
                @endif

                @can('update', $store)
                    <a href="{{ route('stores.edit', $store) }}" class="oy-btn oy-btn-secondary">Edit Store</a>
                @endcan

                @can('toggleActive', $store)
                    @if (! $store->is_active)
                        <form method="POST" action="{{ route('stores.activate', $store) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="oy-btn oy-btn-primary">Activate</button>
                        </form>
                    @elseif (! $store->isWarehouse())
                        <form method="POST" action="{{ route('stores.deactivate', $store) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="oy-btn oy-btn-secondary">Deactivate</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

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

        {{-- Details --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-base font-semibold text-slate-900">Details</h2>

            <dl class="mt-3 grid gap-3 text-xs sm:mt-4 sm:grid-cols-2 sm:gap-4 sm:text-sm">
                <div>
                    <dt class="text-slate-500">Phone</dt>
                    <dd class="mt-1 text-slate-900">{{ $store->phone ?? '—' }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500">Address</dt>
                    <dd class="mt-1 text-slate-900">{{ $store->address ?? '—' }}</dd>
                </div>

                @can('update', $store)
                    <div>
                        <dt class="text-slate-500">Created</dt>
                        <dd class="mt-1 text-slate-900">
                            {{ $store->created_at->format('d M Y') }}@if ($store->creator) by {{ $store->creator->name }}@endif
                        </dd>
                    </div>
                @endcan
            </dl>
        </div>

        {{-- Daily opening and closing balances --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-4 sm:px-6 sm:py-5">
                <h2 class="text-base font-semibold tracking-tight text-slate-900 sm:text-lg">
                    Daily cash and bank balances
                </h2>
                <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                    Record the store's opening and closing balances for each business day.
                </p>
            </div>

            @if ($canOperateStock)
                <div class="border-b border-slate-200 bg-slate-50/70 px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Today's business record
                            </p>
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
                        <div class="mt-4 rounded-lg border border-slate-200 bg-white p-3 sm:mt-5 sm:p-5">
                            <h4 class="text-sm font-semibold text-slate-900">Opening balances</h4>
                            <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                                Enter the cash and bank balances available at the start of the business day.
                            </p>

                            <form method="POST" action="{{ route('stores.daily-cash.open', $store) }}"
                                  class="mt-4 grid gap-3 sm:mt-5 sm:grid-cols-2 sm:gap-4">
                                @csrf

                                <div>
                                    <label for="opening_cash" class="block text-sm font-medium text-slate-700">
                                        Opening cash
                                    </label>
                                    <div class="relative mt-1.5">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                        <input id="opening_cash" name="opening_cash" type="number"
                                               min="0" step="0.01" inputmode="decimal"
                                               value="{{ old('opening_cash') }}" required
                                               aria-invalid="{{ $errors->has('opening_cash') ? 'true' : 'false' }}"
                                               class="block w-full rounded-lg border {{ $errors->has('opening_cash') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                                    </div>
                                    @error('opening_cash')
                                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="opening_bank" class="block text-sm font-medium text-slate-700">
                                        Opening bank balance
                                    </label>
                                    <div class="relative mt-1.5">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                        <input id="opening_bank" name="opening_bank" type="number"
                                               min="0" step="0.01" inputmode="decimal"
                                               value="{{ old('opening_bank') }}" required
                                               aria-invalid="{{ $errors->has('opening_bank') ? 'true' : 'false' }}"
                                               class="block w-full rounded-lg border {{ $errors->has('opening_bank') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
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
                            <div class="rounded-lg border border-slate-200 bg-white p-3 sm:p-4">
                                <p class="text-xs font-medium text-slate-500">Opening cash:</p>
                                <p class="mt-2 text-lg font-semibold tracking-tight text-slate-900 sm:text-xl">
                                    ₦{{ number_format((float) $dailyCashToday->opening_cash, 2) }}
                                </p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3 sm:p-4">
                                <p class="text-xs font-medium text-slate-500">Opening bank:</p>
                                <p class="mt-2 text-lg font-semibold tracking-tight text-slate-900 sm:text-xl">
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
                        <h3 class="text-base font-semibold text-slate-900">Daily history</h3>
                        <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                            Previous opening and closing records for this store.
                        </p>
                    </div>
                    <span class="text-xs text-slate-500">
                        {{ $dailyCashHistory->count() }} {{ \Illuminate\Support\Str::plural('record', $dailyCashHistory->count()) }}
                    </span>
                </div>

                @if ($dailyCashHistory->isEmpty())
                    <div class="mt-4 rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center">
                        <p class="text-sm font-medium text-slate-700">No daily records yet</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Opening and closing balances will appear here once recorded.
                        </p>
                    </div>
                @else
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                        {{-- Mobile history cards --}}
                        <div class="space-y-3 bg-slate-50/50 p-3 md:hidden">
                            @foreach ($dailyCashHistory as $record)
                                <article class="rounded-lg border border-slate-200 bg-white p-3">
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
                                        <div class="rounded-md bg-slate-50 p-2.5">
                                            <p class="text-xs text-slate-500">Opening cash</p>
                                            <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                                                ₦{{ number_format((float) $record->opening_cash, 2) }}
                                            </p>
                                        </div>
                                        <div class="rounded-md bg-slate-50 p-2.5">
                                            <p class="text-xs text-slate-500">Opening bank</p>
                                            <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                                                ₦{{ number_format((float) $record->opening_bank, 2) }}
                                            </p>
                                        </div>
                                        <div class="rounded-md bg-slate-50 p-2.5">
                                            <p class="text-xs text-slate-500">Closing cash</p>
                                            <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                                                {{ $record->closing_cash === null ? '—' : '₦'.number_format((float) $record->closing_cash, 2) }}
                                            </p>
                                        </div>
                                        <div class="rounded-md bg-slate-50 p-2.5">
                                            <p class="text-xs text-slate-500">Closing bank</p>
                                            <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
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
                                <thead class="bg-slate-50">
                                    <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <th scope="col" class="whitespace-nowrap px-4 py-3">Business date</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Opening cash</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Opening bank</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Closing cash</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Closing bank</th>
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
                                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-slate-700">
                                                ₦{{ number_format((float) $record->opening_cash, 2) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-slate-700">
                                                ₦{{ number_format((float) $record->opening_bank, 2) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-slate-700">
                                                {{ $record->closing_cash === null ? '—' : '₦'.number_format((float) $record->closing_cash, 2) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-slate-700">
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
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 sm:text-lg">Stock operations</h2>
                        <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                            Manage stock belonging to {{ $store->name }} from this store.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">

                        <a
                            href="{{ route('stock.index', ['store_id' => $store->id]) }}"
                            class="oy-btn oy-btn-secondary"
                        >
                            View stock items
                        </a>
                    </div>
                </div>

                @if ($incomingConsignments->isNotEmpty())
                    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-amber-900">
                                    Pending incoming consignments
                                </h3>
                                <p class="mt-1 text-xs text-amber-800 sm:text-sm">
                                    {{ $incomingConsignments->count() }}
                                    consignment{{ $incomingConsignments->count() === 1 ? '' : 's' }}
                                    {{ $incomingConsignments->count() === 1 ? 'needs' : 'need' }}
                                    acknowledgement.
                                </p>
                            </div>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-900">
                                {{ $incomingConsignments->count() }} pending
                            </span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($incomingConsignments as $incomingConsignment)
                                <div class="flex flex-col gap-3 rounded-lg border border-amber-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900">
                                            {{ $incomingConsignment->reference }}
                                        </p>
                                        <p class="mt-1 text-sm text-slate-600">
                                            From {{ $incomingConsignment->sourceStore->name }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Dispatched {{ $incomingConsignment->dispatched_at->format('d M Y, h:i A') }}
                                            · {{ $incomingConsignment->pending_line_count }}
                                            outstanding item line{{ $incomingConsignment->pending_line_count === 1 ? '' : 's' }}
                                            · {{ number_format((float) $incomingConsignment->pending_quantity, 3) }}
                                            units remaining
                                        </p>
                                    </div>

                                    <a
                                        href="{{ route('stock.consignments.acknowledgements.create', $incomingConsignment) }}"
                                        class="oy-btn oy-btn-primary whitespace-nowrap"
                                    >
                                        Accept consignment
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-4 grid gap-3 sm:mt-5 sm:grid-cols-2 lg:grid-cols-3">
                    @if (auth()->user()->activeStoreAssignment?->role === \App\Models\StoreStaff::ROLE_COORDINATOR)
                        <a href="{{ route('stock.items.create') }}"
                           class="rounded-lg border border-slate-200 p-3 transition hover:border-slate-400 sm:p-4">
                            <span class="block font-medium text-slate-900">Register stock</span>
                            <span class="mt-1 block text-xs text-slate-500 sm:text-sm">Add item details, opening quantity, cost and photos</span>
                        </a>
                    @endif

                    <a href="{{ route('stock.processings.create') }}"
                       class="rounded-lg border border-slate-200 p-3 transition hover:border-slate-400 sm:p-4">
                        <span class="block font-medium text-slate-900">Stock processing</span>
                        <span class="mt-1 block text-xs text-slate-500 sm:text-sm">Record raw material processing and outputs</span>
                    </a>

                    <a href="{{ route('stock.consignments.create') }}"
                       class="rounded-lg border border-slate-200 p-3 transition hover:border-slate-400 sm:p-4">
                        <span class="block font-medium text-slate-900">Dispatch consignment</span>
                        <span class="mt-1 block text-xs text-slate-500 sm:text-sm">Send stock to another store</span>
                    </a>

                    <a href="{{ route('stores.sales.index', $store) }}"
                       class="rounded-lg border border-slate-200 p-3 transition hover:border-slate-400 sm:p-4">
                        <span class="block font-medium text-slate-900">Sales</span>
                        <span class="mt-1 block text-xs text-slate-500 sm:text-sm">Review this store's sales history and payments</span>
                    </a>

                    <a href="{{ route('stores.customers.index', $store) }}"
                       class="rounded-lg border border-slate-200 p-3 transition hover:border-slate-400 sm:p-4">
                        <span class="block font-medium text-slate-900">Customers</span>
                        <span class="mt-1 block text-xs text-slate-500 sm:text-sm">Register and manage this store's customer records</span>
                    </a>
                </div>
            </section>
        @endif

        @if ($canOperateStock && $dailyCashToday)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-base font-semibold text-slate-900 sm:text-lg">End of day balances</h2>
                    <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                        Record the cash and bank balances after today's store operations are complete.
                    </p>
                </div>

                <div class="pt-1">
                        @if ($dailyCashToday->closed_by === null)
                            <div class="mt-4 rounded-lg border border-slate-200 bg-white p-3 sm:mt-5 sm:p-5">
                                <h4 class="text-sm font-semibold text-slate-900">Closing balances</h4>
                                <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                                    Enter the cash and bank balances at the end of the business day.
                                </p>

                                <form method="POST" action="{{ route('stores.daily-cash.close', $store) }}"
                                      class="mt-4 grid gap-3 sm:mt-5 sm:grid-cols-2 sm:gap-4">
                                    @csrf

                                    <div>
                                        <label for="closing_cash" class="block text-sm font-medium text-slate-700">
                                            Closing cash
                                        </label>
                                        <div class="relative mt-1.5">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                            <input id="closing_cash" name="closing_cash" type="number"
                                                   min="0" step="0.01" inputmode="decimal"
                                                   value="{{ old('closing_cash') }}" required
                                                   aria-invalid="{{ $errors->has('closing_cash') ? 'true' : 'false' }}"
                                                   class="block w-full rounded-lg border {{ $errors->has('closing_cash') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                                        </div>
                                        @error('closing_cash')
                                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="closing_bank" class="block text-sm font-medium text-slate-700">
                                            Closing bank balance
                                        </label>
                                        <div class="relative mt-1.5">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                            <input id="closing_bank" name="closing_bank" type="number"
                                                   min="0" step="0.01" inputmode="decimal"
                                                   value="{{ old('closing_bank') }}" required
                                                   aria-invalid="{{ $errors->has('closing_bank') ? 'true' : 'false' }}"
                                                   class="block w-full rounded-lg border {{ $errors->has('closing_bank') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
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
                            <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50/60 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-semibold text-emerald-900">Closing balances</h4>
                                    <span class="text-xs font-medium text-emerald-700">Finalised</span>
                                </div>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <p class="text-xs text-emerald-700">Closing cash:</p>
                                        <p class="mt-1 text-base font-semibold text-emerald-950 sm:text-lg">
                                            ₦{{ number_format((float) $dailyCashToday->closing_cash, 2) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-emerald-700">Closing bank:</p>
                                        <p class="mt-1 text-base font-semibold text-emerald-950 sm:text-lg">
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
