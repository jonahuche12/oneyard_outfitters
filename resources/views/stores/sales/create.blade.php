@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div>
        <a href="{{ route('stores.sales.index', $store) }}"
           class="text-sm font-medium text-slate-500 hover:text-slate-800">
            ← Sales
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Record sale</h1>
        <p class="mt-1 text-sm text-slate-500">
            Record items sold at {{ $store->name }}. Only stock with an available balance can be sold.
        </p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Please correct the following:</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($stockLevels->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6">
            <h2 class="font-semibold text-amber-900">No available stock</h2>
            <p class="mt-1 text-sm text-amber-800">
                This store currently has no stock with a positive available balance.
                Quarantined stock cannot be sold.
            </p>
            <a href="{{ route('stores.sales.index', $store) }}"
               class="oy-btn oy-btn-secondary mt-4">Back to sales</a>
        </div>
    @else
        <form id="stock-sale-form" method="POST"
              action="{{ route('stores.sales.store', $store) }}"
              class="space-y-6"
              data-draft-key="oneyard:stock-sale-draft:{{ $store->id }}:{{ auth()->id() }}"
              data-new-customer-id="{{ $selectedCustomerId ?? '' }}">
            @csrf

            <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-900">Sale items</h2>
                        <p class="text-sm text-slate-500">Add each stock item once and enter its quantity and selling price.</p>
                    </div>
                    <button type="button" id="add-sale-line" class="oy-btn oy-btn-secondary">
                        Add item
                    </button>
                </div>

                <div id="sale-lines" class="space-y-4">
                    <div class="sale-line rounded-lg border border-slate-200 p-4">
                        <div class="grid gap-4 md:grid-cols-12">
                            <div class="md:col-span-5">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Stock item *</label>
                                <select name="items[0][stock_item_id]" required
                                        class="sale-item oy-select">
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
                                @error('items.0.stock_item_id') <p class="oy-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Quantity *</label>
                                <input name="items[0][quantity]" type="number" min="0.001" step="0.001"
                                       value="{{ old('items.0.quantity') }}" required
                                       class="sale-quantity oy-input">
                                @error('items.0.quantity') <p class="oy-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Unit price (₦) *</label>
                                <input name="items[0][unit_selling_price]" type="number" min="0.01" step="0.01"
                                       value="{{ old('items.0.unit_selling_price') }}" required
                                       class="sale-price oy-input">
                                @error('items.0.unit_selling_price') <p class="oy-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Line total</label>
                                <p class="sale-total rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800">₦0.00</p>
                            </div>

                            <div class="flex items-end md:col-span-1">
                                <button type="button" class="remove-sale-line text-sm font-medium text-red-600 hover:text-red-800"
                                        aria-label="Remove item">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-4">
                    <div class="text-right">
                        <p class="text-sm text-slate-500">Estimated sale total</p>
                        <p id="sale-grand-total" class="text-xl font-semibold text-slate-900">₦0.00</p>
                        <p class="text-xs text-slate-500">The server calculates the authoritative total.</p>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="font-semibold text-slate-900">Customer</h2>
                        <p class="text-sm text-slate-500">A customer is required if any balance will remain unpaid.</p>
                    </div>

                    <div>
                        <label for="customer_id" class="mb-1 block text-sm font-medium text-slate-700">Select customer</label>
                        <select id="customer_id" name="customer_id"
                                class="oy-select">
                            <option value="">Walk-in / no customer record</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id', $selectedCustomerId) == $customer->id)>
                                    {{ $customer->name }} — {{ $customer->phone }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id') <p class="oy-error">{{ $message }}</p> @enderror
                    </div>

                    <a href="{{ route('stores.customers.create', $store) }}?return_to=sale"
                       class="inline-flex text-sm font-medium text-slate-600 underline hover:text-slate-900">
                        Register a customer separately
                    </a>
                </div>

                <div class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="font-semibold text-slate-900">Initial payment</h2>
                        <p class="text-sm text-slate-500">Enter the total amount received. Any amount above the sale total is recorded separately as a tip.</p>
                    </div>

                    <div class="flex items-start gap-3">
                        <input
                            id="initial_payment_full"
                            name="initial_payment_full"
                            type="checkbox"
                            value="1"
                            @checked(old('initial_payment_full'))
                            class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <div>
                            <label for="initial_payment_full" class="font-medium text-slate-800">Paid in full</label>
                            <p class="text-sm text-slate-500">Check this when the customer has paid the complete sale total. The amount will be calculated automatically.</p>
                            @error('initial_payment_full') <p class="oy-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="initial_payment_amount" class="mb-1 block text-sm font-medium text-slate-700">Amount received (₦)</label>
                        <input id="initial_payment_amount" name="initial_payment_amount"
                               type="number" min="0" step="0.01"
                               value="{{ old('initial_payment_amount') }}"
                               class="oy-input">
                        @error('initial_payment_amount') <p class="oy-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="initial_payment_method" class="mb-1 block text-sm font-medium text-slate-700">Payment method</label>
                        <select id="initial_payment_method" name="initial_payment_method"
                                class="oy-select">
                            <option value="">Select method if payment received</option>
                            @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'pos' => 'POS', 'other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('initial_payment_method') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('initial_payment_method') <p class="oy-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="initial_payment_reference" class="mb-1 block text-sm font-medium text-slate-700">Payment reference</label>
                        <input id="initial_payment_reference" name="initial_payment_reference"
                               value="{{ old('initial_payment_reference') }}" maxlength="120"
                               class="oy-input">
                        @error('initial_payment_reference') <p class="oy-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="initial_payment_notes" class="mb-1 block text-sm font-medium text-slate-700">Payment notes</label>
                        <textarea id="initial_payment_notes" name="initial_payment_notes" rows="2"
                                  class="oy-textarea">{{ old('initial_payment_notes') }}</textarea>
                        @error('initial_payment_notes') <p class="oy-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <div class="oy-form-actions">
                <button type="button" id="discard-sale-draft" class="oy-btn oy-btn-secondary">Discard sale</button>
                <button type="submit" class="oy-btn oy-btn-primary">Record sale</button>
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
        line.className = 'sale-line rounded-lg border border-slate-200 p-4';

        const selectOptions = options.map(option =>
            `<option value="${option.id}" data-available="${option.available}" data-price="${option.price ?? ''}">${escapeHtml(option.label)}</option>`
        ).join('');

        line.innerHTML = `
            <div class="grid gap-4 md:grid-cols-12">
                <div class="md:col-span-5">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Stock item *</label>
                    <select name="items[${index}][stock_item_id]" required class="sale-item oy-select">
                        <option value="">Select available stock</option>${selectOptions}
                    </select>
                    <p class="sale-availability mt-1 text-xs text-slate-500">Choose an item to see its available quantity.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Quantity *</label>
                    <input name="items[${index}][quantity]" type="number" min="0.001" step="0.001" required class="sale-quantity oy-input">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Unit price (₦) *</label>
                    <input name="items[${index}][unit_selling_price]" type="number" min="0.01" step="0.01" required class="sale-price oy-input">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Line total</label>
                    <p class="sale-total rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800">₦0.00</p>
                </div>
                <div class="flex items-end md:col-span-1">
                    <button type="button" class="remove-sale-line text-sm font-medium text-red-600 hover:text-red-800">Remove</button>
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
