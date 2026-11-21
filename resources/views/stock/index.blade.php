@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-8 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('stores.show', $selectedStoreId) }}"
               class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-950">
                ← Back to Store
            </a>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Store stock</h1>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                    {{ number_format($items->total()) }} {{ $items->total() === 1 ? 'item' : 'items' }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-600">
                Inventory currently held by your assigned store, including stock received from other stores.
            </p>
        </div>

        @if ($canManageItems)
            <a href="{{ route('stock.items.create') }}"
               class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                Register stock
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($isAdministrator)
        <form method="GET" action="{{ route('stock.index') }}"
              class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1">
                <label for="stock-store" class="mb-1.5 block text-sm font-medium text-slate-700">Owning store</label>
                <select id="stock-store" name="store_id"
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">All stores</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) $selectedStoreId === (string) $store->id)>
                            {{ $store->name }}{{ $store->is_active ? '' : ' (Inactive)' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                    Apply filter
                </button>
                <a href="{{ route('stock.index') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Clear
                </a>
            </div>
        </form>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-1 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Inventory list</h2>
                <p class="mt-0.5 text-xs text-slate-500">Balances shown are specific to the store holding the stock.</p>
            </div>
            <span class="text-xs text-slate-500">
                {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} of {{ $items->total() }}
            </span>
        </div>

        @if ($items->isEmpty())
            <div class="px-6 py-14 text-center">
                <h3 class="text-sm font-semibold text-slate-900">No stock in this store yet</h3>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                    Stock registered here or received through a consignment will appear in this list.
                </p>
                @if ($canManageItems)
                    <a href="{{ route('stock.items.create') }}"
                       class="mt-4 inline-flex items-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                        Register first stock item
                    </a>
                @endif
            </div>
        @else
            {{-- Mobile inventory cards --}}
            <div class="space-y-3 p-4 md:hidden">
                @foreach ($items as $item)
                    @php($storeLevel = $item->levels->first())

                    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('stock.items.show', $item) }}"
                                   class="break-words text-sm font-semibold text-slate-900 hover:text-emerald-700">
                                    {{ $item->name }}
                                </a>
                                <p class="mt-1 text-xs font-medium text-slate-500">
                                    {{ $item->code }}
                                </p>
                            </div>

                            <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $item->is_raw ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800' }}">
                                {{ $item->is_raw ? 'Raw material' : 'Finished stock' }}
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-lg bg-slate-50 p-3">
                                <p class="text-xs text-slate-500">Available</p>
                                <p class="mt-1 text-base font-semibold tabular-nums text-slate-900">
                                    {{ number_format((float) ($storeLevel?->quantity ?? 0), 3) }}
                                    <span class="text-xs font-normal text-slate-500">{{ $item->unit }}</span>
                                </p>
                            </div>

                            <div class="rounded-lg bg-slate-50 p-3">
                                <p class="text-xs text-slate-500">Quarantined</p>
                                <p class="mt-1 text-base font-semibold tabular-nums text-slate-700">
                                    {{ number_format((float) ($storeLevel?->quantity_quarantined ?? 0), 3) }}
                                    <span class="text-xs font-normal text-slate-500">{{ $item->unit }}</span>
                                </p>
                            </div>
                        </div>

                        <dl class="mt-3 space-y-2 text-xs">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Category</dt>
                                <dd class="text-right font-medium text-slate-700">
                                    {{ $item->category ?: 'Uncategorised' }}
                                    @if ($item->grade)
                                        <span class="block text-slate-500">{{ $item->grade }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Origin store</dt>
                                <dd class="text-right font-medium text-slate-700">
                                    {{ $item->store?->name ?? '—' }}
                                </dd>
                            </div>
                        </dl>

                        @if ($item->description)
                            <p class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                                {{ $item->description }}
                            </p>
                        @endif

                        <div class="mt-4 flex items-center justify-end gap-4 border-t border-slate-100 pt-3">
                            <a href="{{ route('stock.items.show', $item) }}"
                               class="text-sm font-semibold text-slate-700 hover:text-emerald-700">
                                View details
                            </a>

                            @if ($canManageItems && (int) $item->store_id === (int) $selectedStoreId)
                                <a href="{{ route('stock.items.edit', $item) }}"
                                   class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">
                                    Edit
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop inventory table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-5 py-3">Stock item</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Category</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Origin store</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Available</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Quarantined</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Classification</th>
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-right">Manage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($items as $item)
                            @php($storeLevel = $item->levels->first())
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="min-w-56 px-5 py-4">
                                    <a href="{{ route('stock.items.show', $item) }}"
                                       class="font-semibold text-slate-900 decoration-emerald-600 underline-offset-4 hover:text-emerald-700 hover:underline">
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
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums text-slate-900">
                                    {{ number_format((float) ($storeLevel?->quantity ?? 0), 3) }}
                                    <span class="text-xs font-normal text-slate-500">{{ $item->unit }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums text-slate-700">
                                    {{ number_format((float) ($storeLevel?->quantity_quarantined ?? 0), 3) }}
                                    <span class="text-xs text-slate-500">{{ $item->unit }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $item->is_raw ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800' }}">
                                        {{ $item->is_raw ? 'Raw material' : 'Finished stock' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-3">
                                        <a href="{{ route('stock.items.show', $item) }}"
                                           class="font-medium text-slate-700 hover:text-emerald-700">
                                            View
                                        </a>
                                        @if ($canManageItems && (int) $item->store_id === (int) $selectedStoreId)
                                            <a href="{{ route('stock.items.edit', $item) }}"
                                               class="font-medium text-emerald-700 hover:text-emerald-900">
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
