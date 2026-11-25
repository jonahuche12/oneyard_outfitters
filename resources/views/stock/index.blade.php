@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Page identity and primary action --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
            <a href="{{ route('stores.show', $selectedStoreId) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 transition hover:text-white sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
                Back to Store
            </a>

            <div class="mt-3 flex flex-col gap-4 sm:mt-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-200 sm:text-xs">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 7.5 9-4.5 9 4.5v9L12 21l-9-4.5v-9Z"/><path d="m3.5 7.7 8.5 4.5 8.5-4.5M12 12.2V21"/></svg>
                            Inventory
                        </span>
                        <span class="rounded-full bg-cyan-400/15 px-2.5 py-1 text-[10px] font-bold text-cyan-300 sm:text-xs">
                            {{ number_format($items->total()) }} {{ $items->total() === 1 ? 'item' : 'items' }}
                        </span>
                    </div>
                    <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">Store stock</h1>
                    <p class="mt-1 max-w-2xl text-xs text-slate-400 sm:text-sm">
                        Inventory currently held by your assigned store, including stock received from other stores.
                    </p>
                </div>

                @if ($canManageItems)
                    <a href="{{ route('stock.items.create') }}"
                       class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-xs font-bold text-emerald-950 shadow-sm transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 sm:w-auto sm:text-sm">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        Register stock
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- Feedback --}}
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Store filter (administrators) --}}
    @if ($isAdministrator)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('stock.index') }}" class="flex flex-col gap-3 p-3 sm:flex-row sm:items-end sm:gap-4 sm:p-5">
                <div class="min-w-0 flex-1">
                    <label for="stock-store" class="flex items-center gap-2 text-sm font-semibold text-indigo-800">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                        </span>
                        Owning store
                    </label>
                    <select id="stock-store" name="store_id"
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">All stores</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) $selectedStoreId === (string) $store->id)>
                                {{ $store->name }}{{ $store->is_active ? '' : ' (Inactive)' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:gap-2">
                    <button type="submit"
                            class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg bg-indigo-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h18l-7 8v6l-4 2v-8L3 5Z"/></svg>
                        Apply filter
                    </button>
                    <a href="{{ route('stock.index') }}"
                       class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        Clear
                    </a>
                </div>
            </form>
        </section>
    @endif

    {{-- Inventory list --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z"/><path d="M3 7.5V17l9 4 9-4V7.5M12 12v9"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Inventory list</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Balances shown are specific to the store holding the stock.</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} of {{ $items->total() }}
            </span>
        </div>

        @if ($items->isEmpty())
            <div class="p-4 sm:p-6">
                <div class="rounded-xl border border-dashed border-indigo-200 bg-indigo-50/40 px-4 py-10 text-center sm:py-14">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 7.5 9-4.5 9 4.5v9L12 21l-9-4.5v-9Z"/><path d="m3.5 7.7 8.5 4.5 8.5-4.5M12 12.2V21"/></svg>
                    </span>
                    <h3 class="mt-3 text-sm font-bold text-slate-700">No stock in this store yet</h3>
                    <p class="mx-auto mt-1 max-w-md text-xs text-slate-500 sm:text-sm">
                        Stock registered here or received through a consignment will appear in this list.
                    </p>
                    @if ($canManageItems)
                        <a href="{{ route('stock.items.create') }}"
                           class="mt-4 inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg bg-indigo-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            Register first stock item
                        </a>
                    @endif
                </div>
            </div>
        @else
            {{-- Mobile inventory cards --}}
            <div class="space-y-3 bg-slate-50/50 p-3 md:hidden">
                @foreach ($items as $item)
                    @php($storeLevel = $item->levels->first())

                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('stock.items.show', $item) }}"
                                   class="break-words text-sm font-bold text-slate-900 transition hover:text-indigo-700">
                                    {{ $item->name }}
                                </a>
                                <p class="mt-1 text-xs font-medium text-slate-500">
                                    {{ $item->code }}
                                </p>
                            </div>

                            <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $item->is_raw ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">
                                {{ $item->is_raw ? 'Raw material' : 'Finished stock' }}
                            </span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-lg border border-emerald-100 bg-emerald-50/70 p-2.5">
                                <p class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>Available
                                </p>
                                <p class="mt-1 text-sm font-bold tabular-nums text-emerald-950">
                                    {{ number_format((float) ($storeLevel?->quantity ?? 0), 3) }}
                                    <span class="text-xs font-normal text-emerald-700">{{ $item->unit }}</span>
                                </p>
                            </div>

                            <div class="rounded-lg border border-amber-100 bg-amber-50/70 p-2.5">
                                <p class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>Quarantined
                                </p>
                                <p class="mt-1 text-sm font-bold tabular-nums text-amber-950">
                                    {{ number_format((float) ($storeLevel?->quantity_quarantined ?? 0), 3) }}
                                    <span class="text-xs font-normal text-amber-700">{{ $item->unit }}</span>
                                </p>
                            </div>
                        </div>

                        <dl class="mt-3 space-y-2 border-t border-slate-100 pt-3 text-xs">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-slate-500">Category</dt>
                                <dd class="text-right font-medium text-slate-800">
                                    {{ $item->category ?: 'Uncategorised' }}
                                    @if ($item->grade)
                                        <span class="mt-0.5 block font-normal text-slate-500">{{ $item->grade }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-slate-500">Origin store</dt>
                                <dd class="text-right font-medium text-slate-800">
                                    {{ $item->store?->name ?? '—' }}
                                </dd>
                            </div>
                        </dl>

                        @if ($item->description)
                            <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                {{ $item->description }}
                            </p>
                        @endif

                        <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                            <a href="{{ route('stock.items.show', $item) }}"
                               class="inline-flex min-h-10 flex-1 items-center justify-center gap-1.5 rounded-lg bg-indigo-50 px-2.5 text-xs font-semibold text-indigo-800 transition hover:bg-indigo-100">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                View details
                            </a>

                            @if ($canManageItems && (int) $item->store_id === (int) $selectedStoreId)
                                <a href="{{ route('stock.items.edit', $item) }}"
                                   class="inline-flex min-h-10 flex-1 items-center justify-center gap-1.5 rounded-lg bg-slate-100 px-2.5 text-xs font-semibold text-slate-800 transition hover:bg-slate-200">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 4 4 4L9 19H5v-4L16 4Z"/><path d="m14 6 4 4"/></svg>
                                    Edit
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop inventory table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-gradient-to-r from-slate-50 via-white to-indigo-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-5 py-3">Stock item</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Category</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Origin store</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-emerald-700">
                                <span class="inline-flex items-center justify-end gap-1">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>Available
                                </span>
                            </th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-amber-700">
                                <span class="inline-flex items-center justify-end gap-1">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>Quarantined
                                </span>
                            </th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Classification</th>
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-right">Manage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($items as $item)
                            @php($storeLevel = $item->levels->first())
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="min-w-56 px-5 py-4">
                                    <a href="{{ route('stock.items.show', $item) }}"
                                       class="font-semibold text-slate-900 decoration-indigo-600 underline-offset-4 transition hover:text-indigo-700 hover:underline">
                                        {{ $item->name }}
                                    </a>
                                    <p class="mt-1 text-xs font-medium text-slate-600">{{ $item->code }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->description }} · {{ $item->unit }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-700">
                                    <span>{{ $item->category ?: 'Uncategorised' }}</span>
                                    @if ($item->grade)
                                        <span class="mt-1 block text-xs text-slate-500">{{ $item->grade }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-700">
                                    {{ $item->store?->name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums text-emerald-800">
                                    {{ number_format((float) ($storeLevel?->quantity ?? 0), 3) }}
                                    <span class="text-xs font-normal text-slate-500">{{ $item->unit }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums text-amber-800">
                                    {{ number_format((float) ($storeLevel?->quantity_quarantined ?? 0), 3) }}
                                    <span class="text-xs font-normal text-slate-500">{{ $item->unit }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $item->is_raw ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">
                                        {{ $item->is_raw ? 'Raw material' : 'Finished stock' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <a href="{{ route('stock.items.show', $item) }}"
                                           class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-indigo-50 px-3 text-xs font-semibold text-indigo-800 transition hover:bg-indigo-100">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            View
                                        </a>
                                        @if ($canManageItems && (int) $item->store_id === (int) $selectedStoreId)
                                            <a href="{{ route('stock.items.edit', $item) }}"
                                               class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-slate-100 px-3 text-xs font-semibold text-slate-800 transition hover:bg-slate-200">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 4 4 4L9 19H5v-4L16 4Z"/><path d="m14 6 4 4"/></svg>
                                                Edit
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($items->hasPages())
        <div>{{ $items->withQueryString()->links() }}</div>
    @endif
</div>
@endsection