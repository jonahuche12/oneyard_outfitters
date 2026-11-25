@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Page identity --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
            <a href="{{ route('stock.index', $isAdministrator && $store ? ['store_id' => $store->id] : []) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 transition hover:text-white sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
                Back to stock
            </a>

            <div class="mt-3 sm:mt-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-200 sm:text-xs">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7M21 3l-9 9"/><path d="M5 7a7 7 0 0 0 0 10l2 2a7 7 0 0 0 10 0"/></svg>
                        Stock processing
                    </span>
                    @if ($store)
                        <span class="rounded-full bg-cyan-400/15 px-2.5 py-1 text-[10px] font-bold text-cyan-300 sm:text-xs">
                            {{ $store->name }}
                        </span>
                    @endif
                </div>
                <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">Record Stock Processing</h1>
                <p class="mt-1 max-w-2xl text-xs leading-relaxed text-slate-400 sm:text-sm">
                    Consume raw material and record the finished fabric and reusable-waste outputs.
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

    @if ($isAdministrator && ! $store)
        {{-- Administrator: choose processing store --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Processing store</h2>
                        <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Choose the store where the processing took place.</p>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('stock.processings.create') }}" class="flex flex-col gap-3 p-3 sm:flex-row sm:items-end sm:gap-4 sm:p-6">
                <div class="min-w-0 flex-1">
                    <label for="store-choice" class="block text-sm font-semibold text-indigo-800">
                        Select the processing store
                    </label>
                    <select id="store-choice" name="store_id" required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">Choose a store</option>
                        @foreach ($stores as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">Continue</button>
            </form>
        </section>
    @else
        <form method="POST" action="{{ route('stock.processings.store') }}" class="space-y-5 sm:space-y-6">
            @csrf

            @if ($isAdministrator)
                <input type="hidden" name="store_id" value="{{ $store->id }}">
            @else
                <div class="flex min-w-0 items-start gap-3 rounded-xl border border-sky-100 bg-sky-50/70 p-3 sm:p-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-sky-700">Processing store</p>
                        <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $store->name }}</p>
                    </div>
                </div>
            @endif

            {{-- Raw material consumed --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-gradient-to-r from-rose-50 via-white to-amber-50 px-4 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 ring-1 ring-rose-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z"/><path d="M3 7.5V17l9 4 9-4V7.5M12 12v9"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Raw material consumed</h2>
                            <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">What was used up in this processing run.</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 p-3 sm:grid-cols-2 sm:gap-4 sm:p-6">
                    <div class="sm:col-span-2">
                        <label for="raw_stock_item_id" class="block text-sm font-semibold text-rose-800">
                            Raw material
                        </label>
                        <select id="raw_stock_item_id" name="raw_stock_item_id" required
                                aria-invalid="{{ $errors->has('raw_stock_item_id') ? 'true' : 'false' }}"
                                class="mt-1.5 block w-full rounded-lg border {{ $errors->has('raw_stock_item_id') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500">
                            <option value="">Select raw material</option>
                            @foreach ($rawItems as $item)
                                @php($level = $item->levels->first())
                                <option value="{{ $item->id }}" @selected((string) old('raw_stock_item_id') === (string) $item->id)>
                                    {{ $item->description }} — available {{ $level?->quantity ?? '0.000' }} kg
                                </option>
                            @endforeach
                        </select>
                        @if ($rawItems->isEmpty())
                            <div class="mt-2 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>
                                <p>No approved raw material with available stock is currently eligible for processing.</p>
                            </div>
                        @endif
                        @error('raw_stock_item_id')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="quantity_consumed" class="block text-sm font-semibold text-rose-800">
                            Quantity consumed (kg)
                        </label>
                        <input id="quantity_consumed" name="quantity_consumed" type="number"
                               min="0.001" step="0.001" inputmode="decimal" required value="{{ old('quantity_consumed') }}"
                               aria-invalid="{{ $errors->has('quantity_consumed') ? 'true' : 'false' }}"
                               class="mt-1.5 block w-full rounded-lg border {{ $errors->has('quantity_consumed') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm tabular-nums text-slate-900 shadow-sm focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500">
                        @error('quantity_consumed')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="processed_on" class="block text-sm font-semibold text-rose-800">
                            Processing date
                        </label>
                        <input id="processed_on" name="processed_on" type="date" required
                               value="{{ old('processed_on', now()->toDateString()) }}"
                               aria-invalid="{{ $errors->has('processed_on') ? 'true' : 'false' }}"
                               class="mt-1.5 block w-full rounded-lg border {{ $errors->has('processed_on') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500">
                        @error('processed_on')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- Finished fabric outputs --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-50 via-white to-blue-50 px-4 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v14H4zM8 9h8M8 13h8M8 17h4"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Finished fabric outputs</h2>
                            <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Add each finished fabric item produced. Costs are allocation amounts for each line.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 p-3 sm:p-6">
                    <div id="fabric-output-lines" class="space-y-3">
                        @foreach (old('outputs', [['stock_item_id' => '', 'quantity_produced' => '', 'line_cost' => '']]) as $index => $line)
                            <div class="fabric-output-row grid gap-3 rounded-xl border border-emerald-100 bg-emerald-50/50 p-3 sm:grid-cols-12 sm:p-4">
                                <div class="sm:col-span-5">
                                    <label class="block text-xs font-bold text-emerald-800">Finished fabric</label>
                                    <select name="outputs[{{ $index }}][stock_item_id]" required
                                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                        <option value="">Select item</option>
                                        @foreach ($fabricItems as $item)
                                            <option value="{{ $item->id }}" @selected((string) ($line['stock_item_id'] ?? '') === (string) $item->id)>
                                                {{ $item->description }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-bold text-emerald-800">Quantity (yards)</label>
                                    <input name="outputs[{{ $index }}][quantity_produced]" type="number"
                                           min="0.001" step="0.001" inputmode="decimal" required
                                           value="{{ $line['quantity_produced'] ?? '' }}"
                                           class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-bold text-emerald-800">Allocated cost</label>
                                    <div class="relative mt-1.5">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                        <input name="outputs[{{ $index }}][line_cost]" type="number"
                                               min="0" step="0.01" inputmode="decimal" required value="{{ $line['line_cost'] ?? '' }}"
                                               class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    </div>
                                </div>
                                <div class="flex items-end sm:col-span-1">
                                    <button type="button" aria-label="Remove output line"
                                            class="remove-output inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg bg-rose-50 px-2.5 text-xs font-semibold text-rose-800 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-300">
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                        <span class="sm:sr-only">Remove</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-fabric-output"
                            class="oy-btn oy-btn-secondary w-full justify-center sm:w-auto">
                        + Add fabric output
                    </button>

                    @if ($fabricItems->isEmpty())
                        <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>
                            <p>No approved yard-based finished fabric items exist for this store.</p>
                        </div>
                    @endif
                    @error('outputs')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- Waste accounting --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-gradient-to-r from-amber-50 via-white to-violet-50 px-4 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Waste accounting</h2>
                            <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                                Record the recovered reusable waste and the quantity discarded. Recovered waste becomes stock in this store.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 p-3 sm:p-6">
                    <div class="grid gap-3 sm:grid-cols-2 sm:gap-4">
                        <div>
                            <label for="recoverable_waste_kg" class="block text-sm font-semibold text-violet-800">
                                Recoverable waste (kg)
                            </label>
                            <input id="recoverable_waste_kg" name="recoverable_waste_kg" type="number"
                                   min="0" step="0.001" inputmode="decimal" value="{{ old('recoverable_waste_kg', '0.000') }}"
                                   aria-invalid="{{ $errors->has('recoverable_waste_kg') ? 'true' : 'false' }}"
                                   class="mt-1.5 block w-full rounded-lg border {{ $errors->has('recoverable_waste_kg') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm tabular-nums text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500">
                            @error('recoverable_waste_kg')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="discarded_waste_kg" class="block text-sm font-semibold text-amber-800">
                                Discarded waste (kg)
                            </label>
                            <input id="discarded_waste_kg" name="discarded_waste_kg" type="number"
                                   min="0" step="0.001" inputmode="decimal" value="{{ old('discarded_waste_kg', '0.000') }}"
                                   aria-invalid="{{ $errors->has('discarded_waste_kg') ? 'true' : 'false' }}"
                                   class="mt-1.5 block w-full rounded-lg border {{ $errors->has('discarded_waste_kg') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm tabular-nums text-slate-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                            @error('discarded_waste_kg')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div id="recoverable-waste-details" class="grid gap-3 rounded-xl border border-violet-100 bg-violet-50/50 p-3 transition sm:grid-cols-2 sm:gap-4 sm:p-4">
                        <div>
                            <label for="recoverable_waste_stock_item_id" class="block text-sm font-semibold text-violet-800">
                                Reusable-waste stock item
                            </label>
                            <select id="recoverable_waste_stock_item_id"
                                    name="recoverable_waste[stock_item_id]"
                                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500 disabled:cursor-not-allowed disabled:bg-slate-100">
                                <option value="">Select reusable-waste item</option>
                                @foreach ($wasteItems as $item)
                                    <option value="{{ $item->id }}" @selected((string) old('recoverable_waste.stock_item_id') === (string) $item->id)>
                                        {{ $item->description }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="recoverable_waste_line_cost" class="block text-sm font-semibold text-violet-800">
                                Recovered-waste allocated cost
                            </label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                                <input id="recoverable_waste_line_cost"
                                       name="recoverable_waste[line_cost]" type="number"
                                       min="0" step="0.01" inputmode="decimal" value="{{ old('recoverable_waste.line_cost', '0.00') }}"
                                       class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500 disabled:cursor-not-allowed disabled:bg-slate-100">
                            </div>
                        </div>
                        @if ($wasteItems->isEmpty())
                            <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 sm:col-span-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>
                                <p>No approved reusable-waste stock item exists for this store. Create and approve one before recording recoverable waste.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Notes --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="p-3 sm:p-6">
                    <label for="notes" class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3h14v18H5zM8 8h8M8 12h8M8 16h4"/></svg>
                        </span>
                        Notes <span class="font-normal text-slate-500">(optional)</span>
                    </label>
                    <textarea id="notes" name="notes" rows="3"
                              class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">{{ old('notes') }}</textarea>
                </div>
            </section>

            {{-- Actions --}}
            <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-end sm:px-6">
                <a href="{{ route('stock.index', $isAdministrator ? ['store_id' => $store->id] : []) }}"
                   class="oy-btn oy-btn-secondary w-full justify-center sm:w-auto">Cancel</a>
                <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">Record Processing</button>
            </div>
        </form>
    @endif
</div>

@if ($store)
    <template id="fabric-output-template">
        <div class="fabric-output-row grid gap-3 rounded-xl border border-emerald-100 bg-emerald-50/50 p-3 sm:grid-cols-12 sm:p-4">
            <div class="sm:col-span-5">
                <label class="block text-xs font-bold text-emerald-800">Finished fabric</label>
                <select data-field="stock_item_id" required
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Select item</option>
                    @foreach ($fabricItems as $item)
                        <option value="{{ $item->id }}">{{ $item->description }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-3">
                <label class="block text-xs font-bold text-emerald-800">Quantity (yards)</label>
                <input data-field="quantity_produced" type="number" min="0.001" step="0.001" inputmode="decimal" required
                       class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            </div>
            <div class="sm:col-span-3">
                <label class="block text-xs font-bold text-emerald-800">Allocated cost</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                    <input data-field="line_cost" type="number" min="0" step="0.01" inputmode="decimal" required
                           class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
            </div>
            <div class="flex items-end sm:col-span-1">
                <button type="button" aria-label="Remove output line"
                        class="remove-output inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg bg-rose-50 px-2.5 text-xs font-semibold text-rose-800 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-300">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                    <span class="sm:sr-only">Remove</span>
                </button>
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