@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Page identity --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
            <a href="{{ route('stores.sales.index', $store) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 transition hover:text-white sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
                Sales
            </a>

            <div class="mt-3 sm:mt-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-200 sm:text-xs">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h2l2.2 11h9.5l2-7H7"/><circle cx="10" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/></svg>
                        New sale
                    </span>
                    <span class="rounded-full bg-cyan-400/15 px-2.5 py-1 text-[10px] font-bold text-cyan-300 sm:text-xs">
                        {{ $store->name }}
                    </span>
                </div>
                <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">Record sale</h1>
                <p class="mt-1 max-w-2xl text-xs leading-relaxed text-slate-400 sm:text-sm">
                    Record items sold at {{ $store->name }}. Only stock with an available balance can be sold.
                </p>
            </div>
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

    @if ($stockLevels->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold text-amber-900">No available stock</h2>
                    <p class="mt-1 text-xs text-amber-800 sm:text-sm">
                        This store currently has no stock with a positive available balance.
                        Quarantined stock cannot be sold.
                    </p>
                    <a href="{{ route('stores.sales.index', $store) }}"
                       class="oy-btn oy-btn-secondary mt-4 w-full justify-center sm:w-auto">Back to sales</a>
                </div>
            </div>
        </div>
    @else
        <form id="stock-sale-form" method="POST"
              action="{{ route('stores.sales.store', $store) }}"
              class="space-y-5 sm:space-y-6"
              data-draft-key="oneyard:stock-sale-draft:{{ $store->id }}:{{ auth()->id() }}"
              data-new-customer-id="{{ $selectedCustomerId ?? '' }}">
            @csrf

            {{-- Sale items --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-indigo-50 via-white to-cyan-50 px-4 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z"/><path d="M3 7.5V17l9 4 9-4V7.5M12 12v9"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Sale items</h2>
                            <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Add each stock item once and enter its quantity and selling price.</p>
                        </div>
                    </div>
                    <button type="button" id="add-sale-line" class="oy-btn oy-btn-secondary w-full justify-center sm:w-auto">
                        Add item
                    </button>
                </div>

                <div class="p-3 sm:p-6">
                    <div id="sale-lines" class="space-y-3">
                        <div class="sale-line rounded-xl border border-indigo-100 bg-indigo-50/40 p-3 sm:p-4">
                            <div class="grid gap-3 md:grid-cols-12 md:gap-4">
                                <div class="md:col-span-5">
                                    <label class="block text-xs font-bold text-indigo-800">Stock item *</label>
                                    <select name="items[0][stock_item_id]" required
                                            class="sale-item mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                        <option value="">Select available stock</option>
                                        @foreach ($stockLevels as $level)
                                            <option value="{{ $level->stock_item_id }}"
                                                    data-available="{{ $level->quantity }}"
                                                    data-price="{{ $level->effectivePrice() ?? '' }}"
                                                    @selected(old('items.0.stock_item_id') == $level->stock_item_id)>
                                                {{ $level->item->description }}
                                                — {{ $level->item->category }}
                                                — {{ $level->item->grade }}
                                                ({{ $level->quantity }} {{ $level->item->unit }} available)
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="sale-availability mt-1 text-xs text-slate-500">Choose an item to see its available quantity.</p>
                                    @error('items.0.stock_item_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-emerald-800">Quantity *</label>
                                    <input name="items[0][quantity]" type="number" min="0.001" step="0.001" inputmode="decimal"
                                           value="{{ old('items.0.quantity') }}" required
                                           class="sale-quantity mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    @error('items.0.quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-emerald-800">Unit price *</label>
                                    <div class="relative mt-1.5">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                        <input name="items[0][unit_selling_price]" type="number" min="0.01" step="0.01" inputmode="decimal"
                                               value="{{ old('items.0.unit_selling_price') }}" required
                                               class="sale-price block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    </div>
                                    @error('items.0.unit_selling_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-emerald-800">Line total</label>
                                    <p class="sale-total mt-1.5 rounded-lg border border-emerald-100 bg-emerald-50/70 px-3 py-2.5 text-sm font-bold tabular-nums text-emerald-900">₦0.00</p>
                                </div>

                                <div class="flex items-end md:col-span-1">
                                    <button type="button" aria-label="Remove item"
                                            class="remove-sale-line inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg bg-rose-50 px-2.5 text-xs font-semibold text-rose-800 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-300">
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                        <span class="md:sr-only">Remove</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end border-t border-slate-100 pt-4">
                        <div class="w-full rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-3 text-right shadow-sm sm:w-auto sm:min-w-[260px] sm:p-4">
                            <p class="text-xs font-bold text-emerald-700">Estimated sale total</p>
                            <p id="sale-grand-total" class="mt-1 text-xl font-bold tabular-nums tracking-tight text-emerald-950">₦0.00</p>
                            <p class="mt-1 text-xs text-slate-500">The server calculates the authoritative total.</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 sm:gap-6 lg:grid-cols-2">
                {{-- Customer --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-sky-50 via-white to-cyan-50 px-4 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 ring-1 ring-sky-200">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M16 5a3 3 0 0 1 0 6M18 14a4 4 0 0 1 3 4v2"/></svg>
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Customer</h2>
                                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">A customer is required if any balance will remain unpaid.</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 p-3 sm:p-6">
                        <div>
                            <label for="customer_id" class="block text-sm font-semibold text-sky-800">Select customer</label>
                            <select id="customer_id" name="customer_id"
                                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
                                <option value="">Walk-in / no customer record</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id', $selectedCustomerId) == $customer->id)>
                                        {{ $customer->name }} — {{ $customer->phone }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <a href="{{ route('stores.customers.create', $store) }}?return_to=sale"
                           class="inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg bg-sky-50 px-3 text-sm font-semibold text-sky-800 transition hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-sky-300 sm:w-auto">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            Register a customer separately
                        </a>
                    </div>
                </section>

                {{-- Initial payment --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-50 via-white to-blue-50 px-4 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Initial payment</h2>
                                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Enter the total amount received. Any amount above the sale total is recorded separately as a tip.</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 p-3 sm:p-6">
                        <div class="flex items-start gap-3 rounded-xl border border-emerald-100 bg-emerald-50/70 p-3 sm:p-4">
                            <input
                                id="initial_payment_full"
                                name="initial_payment_full"
                                type="checkbox"
                                value="1"
                                @checked(old('initial_payment_full'))
                                class="mt-0.5 h-5 w-5 shrink-0 rounded border-emerald-300 text-emerald-700 focus:ring-2 focus:ring-emerald-400"
                            >
                            <div class="min-w-0">
                                <label for="initial_payment_full" class="cursor-pointer text-sm font-bold text-emerald-900">Paid in full</label>
                                <p id="initial-payment-full-help" class="mt-1 text-xs text-emerald-800">Check this when the customer has paid the complete sale total. The amount will be calculated automatically.</p>
                                @error('initial_payment_full') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="initial_payment_amount" class="block text-sm font-semibold text-emerald-800">Amount received</label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                <input id="initial_payment_amount" name="initial_payment_amount"
                                       type="number" min="0" step="0.01" inputmode="decimal"
                                       value="{{ old('initial_payment_amount') }}"
                                       class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 disabled:cursor-not-allowed disabled:bg-slate-100">
                            </div>
                            @error('initial_payment_amount') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 sm:gap-4">
                            <div>
                                <label for="initial_payment_method" class="block text-sm font-semibold text-blue-800">Payment method</label>
                                <select id="initial_payment_method" name="initial_payment_method"
                                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    <option value="">Select method if payment received</option>
                                    @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'pos' => 'POS', 'other' => 'Other'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('initial_payment_method') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('initial_payment_method') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="initial_payment_reference" class="block text-sm font-semibold text-blue-800">Payment reference</label>
                                <input id="initial_payment_reference" name="initial_payment_reference"
                                       value="{{ old('initial_payment_reference') }}" maxlength="120"
                                       class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                @error('initial_payment_reference') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="initial_payment_notes" class="block text-sm font-semibold text-slate-800">Payment notes</label>
                            <textarea id="initial_payment_notes" name="initial_payment_notes" rows="2"
                                      class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">{{ old('initial_payment_notes') }}</textarea>
                            @error('initial_payment_notes') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-end sm:px-6">
                <button type="button" id="discard-sale-draft" class="oy-btn oy-btn-secondary w-full justify-center sm:w-auto">Discard sale</button>
                <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">Record sale</button>
            </div>
        </form>
    @endif
</div>

@if ($stockLevels->isNotEmpty())
@php
    $saleStockOptions = $stockLevels->map(function ($level) {
        return [
            'id' => $level->stock_item_id,
            'label' => $level->item->description.' — '.$level->item->category.' — '.$level->item->grade.' ('.$level->quantity.' '.$level->item->unit.' available)',
            'available' => (string) $level->quantity,
            'price' => $level->effectivePrice(),
        ];
    })->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('sale-lines');
    const addButton = document.getElementById('add-sale-line');
    const grandTotal = document.getElementById('sale-grand-total');
    const options = @json($saleStockOptions);

    let nextIndex = 1;

    const money = value => '₦' + (Number.isFinite(value) ? value : 0).toLocaleString('en-NG', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    function refresh() {
        let grand = 0;

        container.querySelectorAll('.sale-line').forEach(line => {
            const select = line.querySelector('.sale-item');
            const quantity = line.querySelector('.sale-quantity');
            const price = line.querySelector('.sale-price');
            const selected = select.selectedOptions[0];
            const available = selected?.dataset.available;
            const qty = Number(quantity.value);
            const unitPrice = Number(price.value);
            const total = qty > 0 && unitPrice > 0 ? Math.round(qty * unitPrice * 100) / 100 : 0;

            line.querySelector('.sale-total').textContent = money(total);
            line.querySelector('.sale-availability').textContent = selected?.value
                ? `Available: ${available}`
                : 'Choose an item to see its available quantity.';

            grand += total;
        });

        grandTotal.textContent = money(grand);
    }

    function updateOptions() {
        const selectedIds = [...container.querySelectorAll('.sale-item')]
            .map(select => select.value)
            .filter(Boolean);

        container.querySelectorAll('.sale-item').forEach(select => {
            const current = select.value;

            [...select.options].forEach(option => {
                if (!option.value) return;
                option.disabled = option.value !== current && selectedIds.includes(option.value);
            });
        });
    }

    function bind(line) {
        const select = line.querySelector('.sale-item');
        const price = line.querySelector('.sale-price');

        select.addEventListener('change', () => {
            const selected = select.selectedOptions[0];
            if (selected?.dataset.price && !price.value) {
                price.value = selected.dataset.price;
            }
            updateOptions();
            refresh();
            saveDraft();
        });

        line.querySelectorAll('.sale-quantity, .sale-price').forEach(input => {
            input.addEventListener('input', () => {
                refresh();
                saveDraft();
            });
        });

        line.querySelector('.remove-sale-line').addEventListener('click', () => {
            if (container.querySelectorAll('.sale-line').length === 1) {
                select.value = '';
                line.querySelector('.sale-quantity').value = '';
                price.value = '';
            } else {
                line.remove();
            }
            updateOptions();
            refresh();
            saveDraft();
        });
    }

    function addLine(draftItem = null) {
        const index = nextIndex++;
        const line = document.createElement('div');
        line.className = 'sale-line rounded-xl border border-indigo-100 bg-indigo-50/40 p-3 sm:p-4';

        const selectOptions = options.map(option =>
            `<option value="${option.id}" data-available="${option.available}" data-price="${option.price ?? ''}">${escapeHtml(option.label)}</option>`
        ).join('');

        line.innerHTML = `
            <div class="grid gap-3 md:grid-cols-12 md:gap-4">
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-indigo-800">Stock item *</label>
                    <select name="items[${index}][stock_item_id]" required class="sale-item mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">Select available stock</option>${selectOptions}
                    </select>
                    <p class="sale-availability mt-1 text-xs text-slate-500">Choose an item to see its available quantity.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-emerald-800">Quantity *</label>
                    <input name="items[${index}][quantity]" type="number" min="0.001" step="0.001" inputmode="decimal" required class="sale-quantity mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-emerald-800">Unit price *</label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                        <input name="items[${index}][unit_selling_price]" type="number" min="0.01" step="0.01" inputmode="decimal" required class="sale-price block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-emerald-800">Line total</label>
                    <p class="sale-total mt-1.5 rounded-lg border border-emerald-100 bg-emerald-50/70 px-3 py-2.5 text-sm font-bold tabular-nums text-emerald-900">₦0.00</p>
                </div>
                <div class="flex items-end md:col-span-1">
                    <button type="button" aria-label="Remove item" class="remove-sale-line inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg bg-rose-50 px-2.5 text-xs font-semibold text-rose-800 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-300">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                        <span class="md:sr-only">Remove</span>
                    </button>
                </div>
            </div>`;

        container.appendChild(line);
        bind(line);

        if (draftItem) {
            const select = line.querySelector('.sale-item');
            const itemId = String(draftItem.stock_item_id ?? '');

            if (itemId && ![...select.options].some(option => option.value === itemId)) {
                const unavailable = new Option(
                    `Previously selected item #${itemId} — no longer available`,
                    itemId,
                    true,
                    true
                );
                unavailable.dataset.available = '0';
                select.add(unavailable);
            }

            select.value = itemId;
            line.querySelector('.sale-quantity').value = draftItem.quantity ?? '';
            line.querySelector('.sale-price').value = draftItem.unit_selling_price ?? '';
        }

        updateOptions();
        refresh();
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[char]);
    }

    const form = document.getElementById('stock-sale-form');
    const draftKey = form?.dataset.draftKey;
    const customerField = form?.querySelector('[name="customer_id"]');

    function saveDraft() {
        if (!draftKey || !form) return;

        const draft = {
            items: [...container.querySelectorAll('.sale-line')].map(line => ({
                stock_item_id: line.querySelector('.sale-item').value,
                quantity: line.querySelector('.sale-quantity').value,
                unit_selling_price: line.querySelector('.sale-price').value
            })),
            customer_id: customerField?.value ?? '',
            initial_payment_amount: form.querySelector('[name="initial_payment_amount"]')?.value ?? '',
            initial_payment_method: form.querySelector('[name="initial_payment_method"]')?.value ?? '',
            initial_payment_reference: form.querySelector('[name="initial_payment_reference"]')?.value ?? '',
            initial_payment_notes: form.querySelector('[name="initial_payment_notes"]')?.value ?? '',
            initial_payment_full: form.querySelector('[name="initial_payment_full"]')?.checked ?? false
        };

        try {
            sessionStorage.setItem(draftKey, JSON.stringify(draft));
        } catch (error) {
            console.warn('Unable to save this sale draft in this browser tab.', error);
        }
    }

    function restoreDraft() {
        if (!draftKey || !form) return;

        let draft = null;

        try {
            draft = JSON.parse(sessionStorage.getItem(draftKey) || 'null');
        } catch (error) {
            sessionStorage.removeItem(draftKey);
        }

        if (!draft || !Array.isArray(draft.items)) return;

        const lines = container.querySelectorAll('.sale-line');
        const firstLine = lines[0];
        const firstItem = draft.items[0];

        if (firstItem && firstLine) {
            const select = firstLine.querySelector('.sale-item');
            const itemId = String(firstItem.stock_item_id ?? '');

            if (itemId && ![...select.options].some(option => option.value === itemId)) {
                const unavailable = new Option(
                    `Previously selected item #${itemId} — no longer available`,
                    itemId,
                    true,
                    true
                );
                unavailable.dataset.available = '0';
                select.add(unavailable);
            }

            select.value = itemId;
            firstLine.querySelector('.sale-quantity').value = firstItem.quantity ?? '';
            firstLine.querySelector('.sale-price').value = firstItem.unit_selling_price ?? '';
        }

        draft.items.slice(1).forEach(item => addLine(item));

        const newCustomerId = form.dataset.newCustomerId;
        if (newCustomerId && customerField) {
            customerField.value = newCustomerId;
        } else if (customerField && draft.customer_id) {
            customerField.value = String(draft.customer_id);
        }

        [
            'initial_payment_amount',
            'initial_payment_method',
            'initial_payment_reference',
            'initial_payment_notes'
        ].forEach(name => {
            const field = form.querySelector(`[name="${name}"]`);
            if (field && draft[name] !== undefined) field.value = draft[name];
        });

        const fullPaymentField = form.querySelector('[name="initial_payment_full"]');
        if (fullPaymentField && draft.initial_payment_full !== undefined) {
            fullPaymentField.checked = Boolean(draft.initial_payment_full);
        }

        updateOptions();
        refresh();
        saveDraft();
    }

    container.querySelectorAll('.sale-line').forEach(bind);
    addButton.addEventListener('click', () => addLine());

    const fullPaymentField = form?.querySelector('[name="initial_payment_full"]');
    const initialAmountField = form?.querySelector('[name="initial_payment_amount"]');

    function syncFullPaymentField() {
        if (!initialAmountField) return;

        const isFullPayment = Boolean(fullPaymentField?.checked);
        initialAmountField.disabled = isFullPayment;
        initialAmountField.setAttribute(
            'aria-describedby',
            isFullPayment ? 'initial-payment-full-help' : ''
        );
    }

    fullPaymentField?.addEventListener('change', () => {
        syncFullPaymentField();
        saveDraft();
    });

    form?.addEventListener('input', saveDraft);
    form?.addEventListener('change', saveDraft);
    container.addEventListener('change', updateOptions);

    document.getElementById('discard-sale-draft')?.addEventListener('click', () => {
        if (draftKey) sessionStorage.removeItem(draftKey);
        window.location.href = @json(route('stores.sales.index', $store));
    });

    restoreDraft();
    syncFullPaymentField();
    refresh();
});
</script>
@endif
@endsection