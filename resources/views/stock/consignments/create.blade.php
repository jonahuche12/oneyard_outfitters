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

<div class="mx-auto max-w-4xl space-y-7 px-4 py-5 sm:py-8">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dispatch Stock Consignment</h1>
        <p class="mt-1 max-w-3xl text-sm text-gray-600">
            Send available stock from this store to another store. The receiving store must acknowledge
            the delivery before the accepted quantity becomes available for sale.
        </p>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm leading-6 text-red-800">
            <p class="font-semibold">Please correct the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($sourceStore)
        <section class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dispatching from</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $sourceStore->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Recorded by</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500">Store coordinator or assistant</p>
                </div>
            </div>
        </section>

        @if ($items->isEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:p-6">
                <h2 class="font-semibold text-amber-900">No available stock to dispatch</h2>
                <p class="mt-1 text-sm text-amber-800">
                    This store currently has no stock items with an available quantity greater than zero.
                    Register stock or check the store's inventory balances before creating a consignment.
                </p>
            </div>
        @else
            <form method="POST"
                  action="{{ route('stock.consignments.store') }}"
                  class="space-y-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7"
                  id="consignment-form">
                @csrf

                <section class="space-y-4">
                    <h2 class="font-semibold text-slate-900">1. Destination</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Choose the store that will receive and acknowledge these items.
                    </p>

                    <div class="mt-4">
                        <label for="destination_store_id" class="mb-1 block text-sm font-medium text-slate-700">
                            Destination store <span class="text-red-600">*</span>
                        </label>
                        <select id="destination_store_id"
                                name="destination_store_id"
                                required
                                class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Choose destination store</option>
                            @foreach ($destinations as $destination)
                                <option value="{{ $destination->id }}"
                                    @selected(old('destination_store_id') == $destination->id)>
                                    {{ $destination->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('destination_store_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-slate-900">2. Stock items</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Select the items and quantities leaving this store.
                            </p>
                        </div>
                        <button type="button"
                                id="add-consignment-line"
                                class="oy-btn oy-btn-secondary">
                            + Add another item
                        </button>
                    </div>

                    <div id="consignment-lines" class="mt-5 space-y-4">
                        @foreach ($oldLines as $index => $oldLine)
                            <div class="consignment-line rounded-2xl border border-slate-200 bg-slate-50/50 p-4 sm:p-5"
                                 data-line>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="line-heading text-sm font-semibold text-slate-700">
                                        Item {{ $loop->iteration }}
                                    </p>
                                    <button type="button"
                                            class="remove-line rounded-md px-2 py-1 text-sm font-medium text-red-700 hover:bg-red-50"
                                            @disabled(count($oldLines) === 1)>
                                        Remove
                                    </button>
                                </div>

                                <div class="mt-3 grid gap-4 md:grid-cols-12">
                                    <div class="md:col-span-7">
                                        <label class="mb-1 block text-sm font-medium text-slate-700">
                                            Stock item <span class="text-red-600">*</span>
                                        </label>
                                        <select name="items[{{ $index }}][stock_item_id]"
                                                required
                                                class="item-select block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
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
                                        <label class="mb-1 block text-sm font-medium text-slate-700">
                                            Dispatch quantity <span class="text-red-600">*</span>
                                        </label>
                                        <input name="items[{{ $index }}][quantity]"
                                               type="number"
                                               min="0.001"
                                               step="0.001"
                                               value="{{ $oldLine['quantity'] ?? '' }}"
                                               required
                                               inputmode="decimal"
                                               class="quantity-input block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                        <p class="availability-hint mt-1 text-xs text-slate-500">
                                            Select an item to see its available balance.
                                        </p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <p class="text-sm font-medium text-slate-700">Unit cost</p>
                                        <p class="unit-cost mt-2 text-sm font-semibold text-slate-900">—</p>
                                        <p class="text-xs text-slate-500">Current average cost</p>
                                    </div>
                                </div>

                                <div class="line-summary mt-3 flex flex-wrap justify-between gap-2 border-t border-slate-200 pt-3 text-sm">
                                    <span class="text-slate-500">Estimated dispatch cost</span>
                                    <span class="line-total font-semibold text-slate-900">₦0.00</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <p class="mt-3 text-xs text-slate-500">
                        Only available stock can be dispatched. Quantities are checked again against the
                        latest inventory balance when you submit. Repeated item selections are combined
                        by the system.
                    </p>
                </section>

                <section class="space-y-4">
                    <h2 class="font-semibold text-slate-900">3. Dispatch summary</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                            <p class="text-sm text-slate-500">Total quantity</p>
                            <p id="total-quantity" class="mt-1 text-xl font-semibold text-slate-900">0.000</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                            <p class="text-sm text-slate-500">Estimated stock value at cost</p>
                            <p id="total-cost" class="mt-1 text-xl font-semibold text-slate-900">₦0.00</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">
                            Dispatch notes
                        </label>
                        <textarea id="notes"
                                  name="notes"
                                  rows="3"
                                  maxlength="5000"
                                  class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                  placeholder="Optional: transport details, dispatch reference or other relevant instructions">{{ old('notes') }}</textarea>
                    </div>
                </section>

                <div class="flex flex-col-reverse justify-end gap-3 border-t border-slate-100 pt-5 sm:flex-row">
                    <a href="{{ url()->previous() }}"
                       class="oy-btn oy-btn-secondary">
                        Cancel
                    </a>
                    <button type="submit"
                            class="oy-btn oy-btn-primary">
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
