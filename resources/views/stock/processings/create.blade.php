@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-7 px-4 py-5 sm:py-8">
    <div>
        <a href="{{ route('stock.index', $isAdministrator && $store ? ['store_id' => $store->id] : []) }}"
           class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-slate-950">
            ← Back to stock
        </a>
        <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Record Stock Processing</h1>
        <p class="mt-1 text-sm text-slate-600">
            Consume raw material and record the finished fabric and reusable-waste outputs.
        </p>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm leading-6 text-red-800">
            <p class="font-semibold">Please correct the following:</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($isAdministrator && ! $store)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <label for="store-choice" class="mb-2 block text-sm font-medium text-slate-700">
                Select the processing store
            </label>
            <form method="GET" action="{{ route('stock.processings.create') }}" class="flex flex-col gap-3 sm:flex-row">
                <select id="store-choice" name="store_id" required
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Choose a store</option>
                    @foreach ($stores as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="oy-btn oy-btn-primary">Continue</button>
            </form>
        </div>
    @else
        <form method="POST" action="{{ route('stock.processings.store') }}" class="space-y-6">
            @csrf

            @if ($isAdministrator)
                <input type="hidden" name="store_id" value="{{ $store->id }}">
            @else
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm">
                    <span class="font-medium text-slate-700">Processing store:</span>
                    <span class="text-slate-900">{{ $store->name }}</span>
                </div>
            @endif

            <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-semibold text-slate-900">Raw material consumed</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="raw_stock_item_id" class="mb-1 block text-sm font-medium text-slate-700">
                            Raw material
                        </label>
                        <select id="raw_stock_item_id" name="raw_stock_item_id" required
                                class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Select raw material</option>
                            @foreach ($rawItems as $item)
                                @php($level = $item->levels->first())
                                <option value="{{ $item->id }}" @selected((string) old('raw_stock_item_id') === (string) $item->id)>
                                    {{ $item->description }} — available {{ $level?->quantity ?? '0.000' }} kg
                                </option>
                            @endforeach
                        </select>
                        @if ($rawItems->isEmpty())
                            <p class="mt-1 text-xs text-amber-700">
                                No approved raw material with available stock is currently eligible for processing.
                            </p>
                        @endif
                        @error('raw_stock_item_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="quantity_consumed" class="mb-1 block text-sm font-medium text-slate-700">
                            Quantity consumed (kg)
                        </label>
                        <input id="quantity_consumed" name="quantity_consumed" type="number"
                               min="0.001" step="0.001" required value="{{ old('quantity_consumed') }}"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('quantity_consumed')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="processed_on" class="mb-1 block text-sm font-medium text-slate-700">
                            Processing date
                        </label>
                        <input id="processed_on" name="processed_on" type="date" required
                               value="{{ old('processed_on', now()->toDateString()) }}"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('processed_on')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Finished fabric outputs</h2>
                    <p class="mt-1 text-sm text-slate-600">Add each finished fabric item produced. Costs are allocation amounts for each line.</p>
                </div>

                <div id="fabric-output-lines" class="space-y-3">
                    @foreach (old('outputs', [['stock_item_id' => '', 'quantity_produced' => '', 'line_cost' => '']]) as $index => $line)
                        <div class="fabric-output-row grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5 sm:grid-cols-12">
                            <div class="sm:col-span-5">
                                <label class="mb-1 block text-xs font-medium text-slate-600">Finished fabric</label>
                                <select name="outputs[{{ $index }}][stock_item_id]" required
                                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <option value="">Select item</option>
                                    @foreach ($fabricItems as $item)
                                        <option value="{{ $item->id }}" @selected((string) ($line['stock_item_id'] ?? '') === (string) $item->id)>
                                            {{ $item->description }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-3">
                                <label class="mb-1 block text-xs font-medium text-slate-600">Quantity (yards)</label>
                                <input name="outputs[{{ $index }}][quantity_produced]" type="number"
                                       min="0.001" step="0.001" required
                                       value="{{ $line['quantity_produced'] ?? '' }}"
                                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="mb-1 block text-xs font-medium text-slate-600">Allocated cost (₦)</label>
                                <input name="outputs[{{ $index }}][line_cost]" type="number"
                                       min="0" step="0.01" required value="{{ $line['line_cost'] ?? '' }}"
                                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div class="flex items-end sm:col-span-1">
                                <button type="button" class="remove-output oy-btn oy-btn-secondary w-full">Remove</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" id="add-fabric-output" class="oy-btn oy-btn-secondary">
                    + Add fabric output
                </button>
                @if ($fabricItems->isEmpty())
                    <p class="text-xs text-amber-700">No approved yard-based finished fabric items exist for this store.</p>
                @endif
                @error('outputs')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </section>

            <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Waste accounting</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Record the recovered reusable waste and the quantity discarded. Recovered waste becomes stock in this store.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="recoverable_waste_kg" class="mb-1 block text-sm font-medium text-slate-700">
                            Recoverable waste (kg)
                        </label>
                        <input id="recoverable_waste_kg" name="recoverable_waste_kg" type="number"
                               min="0" step="0.001" value="{{ old('recoverable_waste_kg', '0.000') }}"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('recoverable_waste_kg')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="discarded_waste_kg" class="mb-1 block text-sm font-medium text-slate-700">
                            Discarded waste (kg)
                        </label>
                        <input id="discarded_waste_kg" name="discarded_waste_kg" type="number"
                               min="0" step="0.001" value="{{ old('discarded_waste_kg', '0.000') }}"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('discarded_waste_kg')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div id="recoverable-waste-details" class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 sm:p-5">
                    <div>
                        <label for="recoverable_waste_stock_item_id" class="mb-1 block text-sm font-medium text-slate-700">
                            Reusable-waste stock item
                        </label>
                        <select id="recoverable_waste_stock_item_id"
                                name="recoverable_waste[stock_item_id]"
                                class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Select reusable-waste item</option>
                            @foreach ($wasteItems as $item)
                                <option value="{{ $item->id }}" @selected((string) old('recoverable_waste.stock_item_id') === (string) $item->id)>
                                    {{ $item->description }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="recoverable_waste_line_cost" class="mb-1 block text-sm font-medium text-slate-700">
                            Recovered-waste allocated cost (₦)
                        </label>
                        <input id="recoverable_waste_line_cost"
                               name="recoverable_waste[line_cost]" type="number"
                               min="0" step="0.01" value="{{ old('recoverable_waste.line_cost', '0.00') }}"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    @if ($wasteItems->isEmpty())
                        <p class="text-xs text-amber-700 sm:col-span-2">
                            No approved reusable-waste stock item exists for this store. Create and approve one before recording recoverable waste.
                        </p>
                    @endif
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="3"
                          class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </section>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('stock.index', $isAdministrator ? ['store_id' => $store->id] : []) }}"
                   class="oy-btn oy-btn-secondary">Cancel</a>
                <button type="submit" class="oy-btn oy-btn-primary">Record Processing</button>
            </div>
        </form>
    @endif
</div>

@if ($store)
    <template id="fabric-output-template">
        <div class="fabric-output-row grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5 sm:grid-cols-12">
            <div class="sm:col-span-5">
                <label class="mb-1 block text-xs font-medium text-slate-600">Finished fabric</label>
                <select data-field="stock_item_id" required
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select item</option>
                    @foreach ($fabricItems as $item)
                        <option value="{{ $item->id }}">{{ $item->description }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Quantity (yards)</label>
                <input data-field="quantity_produced" type="number" min="0.001" step="0.001" required
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Allocated cost (₦)</label>
                <input data-field="line_cost" type="number" min="0" step="0.01" required
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="flex items-end sm:col-span-1">
                <button type="button" class="remove-output oy-btn oy-btn-secondary w-full">Remove</button>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lines = document.getElementById('fabric-output-lines');
            const template = document.getElementById('fabric-output-template');
            const addButton = document.getElementById('add-fabric-output');
            let nextIndex = lines.querySelectorAll('.fabric-output-row').length;

            addButton?.addEventListener('click', function () {
                const fragment = template.content.cloneNode(true);
                fragment.querySelectorAll('[data-field]').forEach(function (field) {
                    field.name = `outputs[${nextIndex}][${field.dataset.field}]`;
                });
                lines.appendChild(fragment);
                nextIndex++;
            });

            lines?.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-output');
                if (!button) return;

                const rows = lines.querySelectorAll('.fabric-output-row');
                if (rows.length <= 1) {
                    rows[0].querySelectorAll('input').forEach(input => input.value = '');
                    rows[0].querySelector('select').value = '';
                    return;
                }

                button.closest('.fabric-output-row').remove();
            });

            const recoverableQuantity = document.getElementById('recoverable_waste_kg');
            const wasteDetails = document.getElementById('recoverable-waste-details');

            function toggleWasteFields() {
                const enabled = Number(recoverableQuantity?.value || 0) > 0;
                wasteDetails?.querySelectorAll('select, input').forEach(function (field) {
                    field.disabled = !enabled;
                    field.required = enabled;
                });
                wasteDetails?.classList.toggle('opacity-50', !enabled);
            }

            recoverableQuantity?.addEventListener('input', toggleWasteFields);
            toggleWasteFields();
        });
    </script>
@endif
@endsection
