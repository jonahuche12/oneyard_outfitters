@extends('layouts.app')

@section('content')
@php
    $oldLines = old('items', [
        ['stock_item_id' => '', 'quantity' => ''],
    ]);

    if (! is_array($oldLines) || count($oldLines) === 0) {
        $oldLines = [['stock_item_id' => '', 'quantity' => '']];
    }
@endphp

<div class="mx-auto max-w-4xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Page identity --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-200 sm:text-xs">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="1.5"/><circle cx="18" cy="18" r="1.5"/></svg>
                    Consignment
                </span>
                @if ($sourceStore)
                    <span class="rounded-full bg-cyan-400/15 px-2.5 py-1 text-[10px] font-bold text-cyan-300 sm:text-xs">
                        {{ $sourceStore->name }}
                    </span>
                @endif
            </div>
            <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">Dispatch Stock Consignment</h1>
            <p class="mt-1 max-w-3xl text-xs leading-relaxed text-slate-400 sm:text-sm">
                Send available stock from this store to another store. The receiving store must acknowledge
                the delivery before the accepted quantity becomes available for sale.
            </p>
        </div>
    </section>

    {{-- Validation summary --}}
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 sm:px-5 sm:py-4">
            <div class="flex items-start gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="font-bold">Please correct the following:</p>
                    <ul class="mt-2 list-inside list-disc space-y-0.5 text-xs sm:text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if ($sourceStore)
        {{-- Dispatch context --}}
        <section class="grid gap-3 sm:grid-cols-2 sm:gap-4">
            <div class="flex min-w-0 items-start gap-3 rounded-xl border border-sky-100 bg-sky-50/70 p-3 sm:p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-sky-700">Dispatching from</p>
                    <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $sourceStore->name }}</p>
                </div>
            </div>
            <div class="flex min-w-0 items-start gap-3 rounded-xl border border-violet-100 bg-violet-50/70 p-3 sm:p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3.5"/><path d="M5 20v-1a7 7 0 0 1 14 0v1"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-violet-700">Recorded by</p>
                    <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500">Store coordinator or assistant</p>
                </div>
            </div>
        </section>

        @if ($items->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-amber-900">No available stock to dispatch</h2>
                        <p class="mt-1 text-xs text-amber-800 sm:text-sm">
                            This store currently has no stock items with an available quantity greater than zero.
                            Register stock or check the store's inventory balances before creating a consignment.
                        </p>
                    </div>
                </div>
            </div>
        @else
            <form method="POST"
                  action="{{ route('stock.consignments.store') }}"
                  class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                  id="consignment-form">
                @csrf

                {{-- 1. Destination --}}
                <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">1. Destination</h2>
                            <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Choose the store that will receive and acknowledge these items.</p>
                        </div>
                    </div>
                </div>

                <div class="p-3 sm:p-6">
                    <label for="destination_store_id" class="block text-sm font-semibold text-indigo-800">
                        Destination store <span class="text-red-600">*</span>
                    </label>
                    <select id="destination_store_id"
                            name="destination_store_id"
                            required
                            aria-invalid="{{ $errors->has('destination_store_id') ? 'true' : 'false' }}"
                            class="mt-1.5 block w-full rounded-lg border {{ $errors->has('destination_store_id') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">Choose destination store</option>
                        @foreach ($destinations as $destination)
                            <option value="{{ $destination->id }}"
                                @selected(old('destination_store_id') == $destination->id)>
                                {{ $destination->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('destination_store_id')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 2. Stock items --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-y border-slate-100 bg-gradient-to-r from-cyan-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700 ring-1 ring-cyan-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z"/><path d="M3 7.5V17l9 4 9-4V7.5M12 12v9"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">2. Stock items</h2>
                            <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Select the items and quantities leaving this store.</p>
                        </div>
                    </div>
                    <button type="button"
                            id="add-consignment-line"
                            class="oy-btn oy-btn-secondary w-full justify-center sm:w-auto">
                        + Add another item
                    </button>
                </div>

                <div class="p-3 sm:p-6">
                    <div id="consignment-lines" class="space-y-3">
                        @foreach ($oldLines as $index => $oldLine)
                            <div class="consignment-line rounded-xl border border-indigo-100 bg-indigo-50/40 p-3 sm:p-4"
                                 data-line>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="flex items-center gap-2 text-sm font-bold text-indigo-900">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 7.5 9-4.5 9 4.5v9L12 21l-9-4.5v-9Z"/><path d="m3.5 7.7 8.5 4.5 8.5-4.5M12 12.2V21"/></svg>
                                        </span>
                                        <span class="line-heading">Item {{ $loop->iteration }}</span>
                                    </p>
                                    <button type="button"
                                            class="remove-line inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-rose-50 px-3 text-xs font-semibold text-rose-800 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-300 disabled:cursor-not-allowed disabled:opacity-40"
                                            @disabled(count($oldLines) === 1)>
                                        <svg class="pointer-events-none h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                        Remove
                                    </button>
                                </div>

                                <div class="mt-3 grid gap-3 md:grid-cols-12 md:gap-4">
                                    <div class="md:col-span-7">
                                        <label class="block text-xs font-bold text-indigo-800">
                                            Stock item <span class="text-red-600">*</span>
                                        </label>
                                        <select name="items[{{ $index }}][stock_item_id]"
                                                required
                                                class="item-select mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                            <option value="">Choose stock item</option>
                                            @foreach ($items as $item)
                                                @php
                                                    $level = $item->levels->first();
                                                    $available = (string) ($level?->quantity ?? '0.000');
                                                    $unitCost = (string) ($level?->average_unit_cost ?? '0.000000');
                                                    $marketPrice = $item->current_market_price ?? $item->suggested_price;
                                                @endphp
                                                <option value="{{ $item->id }}"
                                                        data-available="{{ $available }}"
                                                        data-unit="{{ $item->unit }}"
                                                        data-cost="{{ $unitCost }}"
                                                        data-price="{{ $marketPrice ?? '' }}"
                                                    @selected(($oldLine['stock_item_id'] ?? '') == $item->id)>
                                                    {{ $item->description }}
                                                    — Available: {{ number_format((float) $available, 3) }} {{ $item->unit }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-bold text-emerald-800">
                                            Dispatch quantity <span class="text-red-600">*</span>
                                        </label>
                                        <input name="items[{{ $index }}][quantity]"
                                               type="number"
                                               min="0.001"
                                               step="0.001"
                                               value="{{ $oldLine['quantity'] ?? '' }}"
                                               required
                                               inputmode="decimal"
                                               class="quantity-input mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 disabled:cursor-not-allowed disabled:bg-slate-100">
                                        <p class="availability-hint mt-1 text-xs text-slate-500">
                                            Select an item to see its available balance.
                                        </p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <div class="rounded-lg border border-blue-100 bg-blue-50/70 p-2.5">
                                            <p class="text-xs font-bold text-blue-700">Unit cost</p>
                                            <p class="unit-cost mt-1 text-sm font-bold tabular-nums text-blue-950">—</p>
                                            <p class="text-[11px] text-slate-500">Current average cost</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="line-summary mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-indigo-100 pt-3 text-sm">
                                    <span class="text-xs font-medium text-slate-500">Estimated dispatch cost</span>
                                    <span class="line-total font-bold tabular-nums text-emerald-800">₦0.00</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v5h1"/></svg>
                        <p>
                            Only available stock can be dispatched. Quantities are checked again against the
                            latest inventory balance when you submit. Repeated item selections are combined
                            by the system.
                        </p>
                    </div>
                </div>

                {{-- 3. Dispatch summary --}}
                <div class="border-y border-slate-100 bg-gradient-to-r from-emerald-50 via-white to-blue-50 px-4 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/><path d="m16 16 2 2 3-4"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">3. Dispatch summary</h2>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 p-3 sm:p-6">
                    <div class="grid gap-3 sm:grid-cols-2 sm:gap-4">
                        <div class="rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-3 shadow-sm sm:p-4">
                            <p class="flex items-center gap-1.5 text-xs font-bold text-indigo-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 7.5 9-4.5 9 4.5v9L12 21l-9-4.5v-9Z"/><path d="m3.5 7.7 8.5 4.5 8.5-4.5M12 12.2V21"/></svg>
                                Total quantity
                            </p>
                            <p id="total-quantity" class="mt-2 text-lg font-bold tabular-nums tracking-tight text-indigo-950 sm:text-xl">0.000</p>
                        </div>
                        <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-3 shadow-sm sm:p-4">
                            <p class="flex items-center gap-1.5 text-xs font-bold text-emerald-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>
                                Estimated stock value at cost
                            </p>
                            <p id="total-cost" class="mt-2 text-lg font-bold tabular-nums tracking-tight text-emerald-950 sm:text-xl">₦0.00</p>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3h14v18H5zM8 8h8M8 12h8M8 16h4"/></svg>
                            </span>
                            Dispatch notes
                        </label>
                        <textarea id="notes"
                                  name="notes"
                                  rows="3"
                                  maxlength="5000"
                                  class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                                  placeholder="Optional: transport details, dispatch reference or other relevant instructions">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/70 px-4 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <a href="{{ url()->previous() }}"
                       class="oy-btn oy-btn-secondary w-full justify-center sm:w-auto">
                        Cancel
                    </a>
                    <button type="submit"
                            class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">
                        Confirm and dispatch stock
                    </button>
                </div>
            </form>
        @endif
    @endif
</div>

@if ($sourceStore && $items->isNotEmpty())
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('consignment-lines');
    const addButton = document.getElementById('add-consignment-line');
    const form = document.getElementById('consignment-form');

    const money = new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    function updateLine(line) {
        const select = line.querySelector('.item-select');
        const quantityInput = line.querySelector('.quantity-input');
        const option = select.options[select.selectedIndex];
        const available = Number(option?.dataset.available || 0);
        const cost = Number(option?.dataset.cost || 0);
        const quantity = Number(quantityInput.value || 0);
        const unit = option?.dataset.unit || '';

        const hasItem = Boolean(select.value);
        quantityInput.max = hasItem ? available : '';
        quantityInput.disabled = !hasItem;

        line.querySelector('.availability-hint').textContent = hasItem
            ? 'Available: ' + available.toFixed(3) + ' ' + unit
            : 'Select an item to see its available balance.';

        line.querySelector('.unit-cost').textContent = hasItem
            ? money.format(cost)
            : '—';

        const validQuantity = quantity > 0 && quantity <= available;
        line.querySelector('.line-total').textContent = hasItem && validQuantity
            ? money.format(quantity * cost)
            : money.format(0);

        quantityInput.setCustomValidity(
            hasItem && quantity > available
                ? 'Quantity cannot exceed the available balance of ' + available.toFixed(3) + ' ' + unit + '.'
                : ''
        );

        return {
            quantity: hasItem && validQuantity ? quantity : 0,
            value: hasItem && validQuantity ? quantity * cost : 0
        };
    }

    function updateSummary() {
        let totalQuantity = 0;
        let totalCost = 0;

        container.querySelectorAll('.consignment-line').forEach(function (line) {
            const result = updateLine(line);
            totalQuantity += result.quantity;
            totalCost += result.value;
        });

        document.getElementById('total-quantity').textContent = totalQuantity.toFixed(3);
        document.getElementById('total-cost').textContent = money.format(totalCost);

        const lines = container.querySelectorAll('.consignment-line');
        lines.forEach(function (line, index) {
            line.querySelector('.line-heading').textContent = 'Item ' + (index + 1);
            line.querySelector('.remove-line').disabled = lines.length === 1;
        });
    }

    function reindexLines() {
        container.querySelectorAll('.consignment-line').forEach(function (line, index) {
            line.querySelectorAll('select, input').forEach(function (field) {
                field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
        });

        updateSummary();
    }

    addButton.addEventListener('click', function () {
        const first = container.querySelector('.consignment-line');
        const line = first.cloneNode(true);

        line.querySelectorAll('select, input').forEach(function (field) {
            field.value = '';
            field.setCustomValidity('');
        });

        line.querySelector('.quantity-input').disabled = true;
        line.querySelector('.quantity-input').removeAttribute('max');

        container.appendChild(line);
        reindexLines();
    });

    container.addEventListener('change', function (event) {
        if (event.target.matches('.item-select')) {
            const quantityInput = event.target.closest('.consignment-line').querySelector('.quantity-input');
            quantityInput.value = '';
            updateSummary();
        }
    });

    container.addEventListener('input', function (event) {
        if (event.target.matches('.quantity-input')) {
            updateSummary();
        }
    });

    container.addEventListener('click', function (event) {
        if (event.target.matches('.remove-line') && container.querySelectorAll('.consignment-line').length > 1) {
            event.target.closest('.consignment-line').remove();
            reindexLines();
        }
    });

    form.addEventListener('submit', function (event) {
        updateSummary();

        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
        }
    });

    container.querySelectorAll('.consignment-line').forEach(function (line) {
        const select = line.querySelector('.item-select');
        const quantityInput = line.querySelector('.quantity-input');

        quantityInput.disabled = !select.value;
    });

    updateSummary();
});
</script>
@endif
@endsection