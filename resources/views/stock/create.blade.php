@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8">

    {{-- Page identity --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
            <a href="{{ route('stores.show', $coordinatorStore) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 transition hover:text-white sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
                Back to {{ $coordinatorStore->name }}
            </a>

            <div class="mt-3 sm:mt-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-200 sm:text-xs">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        New stock item
                    </span>
                    <span class="rounded-full bg-cyan-400/15 px-2.5 py-1 text-[10px] font-bold text-cyan-300 sm:text-xs">
                        {{ $coordinatorStore->name }}
                    </span>
                </div>
                <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">Register Stock</h1>
                <p class="mt-1 max-w-2xl text-xs leading-relaxed text-slate-400 sm:text-sm">
                    Record the item, its opening quantity and cost. The stock becomes available to this store immediately.
                </p>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ route('stock.items.store') }}" enctype="multipart/form-data"
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @csrf

        {{-- Item details --}}
        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z"/><path d="M3 7.5V17l9 4 9-4V7.5M12 12v9"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Item details</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">What the item is and where it is held.</p>
                </div>
            </div>
        </div>

        <div class="space-y-4 p-3 sm:space-y-5 sm:p-6">
            <div class="flex min-w-0 items-start gap-3 rounded-xl border border-sky-100 bg-sky-50/70 p-3 sm:p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-sky-700">Store</p>
                    <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $coordinatorStore->name }}</p>
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-indigo-800">Item description</label>
                <input id="description" name="description" value="{{ old('description') }}" required maxlength="200"
                       aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}"
                       class="mt-1.5 block w-full rounded-lg border {{ $errors->has('description') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                @error('description')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-3 sm:grid-cols-2 sm:gap-4">
                <div>
                    <label for="category" class="block text-sm font-semibold text-indigo-800">Category</label>
                    <input id="category" name="category" value="{{ old('category') }}" maxlength="80"
                           aria-invalid="{{ $errors->has('category') ? 'true' : 'false' }}"
                           class="mt-1.5 block w-full rounded-lg border {{ $errors->has('category') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    @error('category')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="grade" class="block text-sm font-semibold text-indigo-800">Grade</label>
                    <input id="grade" name="grade" value="{{ old('grade') }}" maxlength="50"
                           aria-invalid="{{ $errors->has('grade') ? 'true' : 'false' }}"
                           class="mt-1.5 block w-full rounded-lg border {{ $errors->has('grade') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    @error('grade')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="is_raw" class="flex cursor-pointer items-start gap-3 rounded-xl border border-violet-100 bg-violet-50/70 p-3 transition hover:border-violet-300 hover:bg-violet-50 sm:p-4">
                    <input id="is_raw" type="checkbox" name="is_raw" value="1" @checked(old('is_raw'))
                           class="mt-0.5 h-5 w-5 shrink-0 rounded border-violet-300 text-violet-700 focus:ring-2 focus:ring-violet-400">
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-violet-900">Raw material</span>
                    </span>
                </label>
                @error('is_raw')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Quantity and unit --}}
        <div class="border-y border-slate-100 bg-gradient-to-r from-slate-50 via-white to-cyan-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700 ring-1 ring-cyan-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v14H4zM8 9h8M8 13h8M8 17h4"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Quantity &amp; unit</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">How the item is measured and how much you are starting with.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-3 p-3 sm:grid-cols-2 sm:gap-4 sm:p-6">
            <div>
                <label for="unit" class="block text-sm font-semibold text-cyan-800">Unit</label>
                <select id="unit" name="unit" required
                        aria-invalid="{{ $errors->has('unit') ? 'true' : 'false' }}"
                        class="mt-1.5 block w-full rounded-lg border {{ $errors->has('unit') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500">
                    <option value="">Select unit</option>
                    @foreach (\App\Models\StockItem::UNITS as $unit)
                        <option value="{{ $unit }}" @selected(old('unit') === $unit)>{{ strtoupper($unit) }}</option>
                    @endforeach
                </select>
                @error('unit')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="quantity" class="block text-sm font-semibold text-cyan-800">Opening quantity</label>
                <input id="quantity" name="quantity" type="number" min="0.001" step="0.001" inputmode="decimal"
                       value="{{ old('quantity') }}" required
                       aria-invalid="{{ $errors->has('quantity') ? 'true' : 'false' }}"
                       class="mt-1.5 block w-full rounded-lg border {{ $errors->has('quantity') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm tabular-nums text-slate-900 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500">
                @error('quantity')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Pricing --}}
        <div class="border-y border-slate-100 bg-gradient-to-r from-emerald-50 via-white to-blue-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Pricing</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Cost and selling prices in naira.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-3 p-3 sm:grid-cols-2 sm:gap-4 sm:p-6">
            <div>
                <label for="unit_cost" class="block text-sm font-semibold text-emerald-800">Cost per unit</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                    <input id="unit_cost" name="unit_cost" type="number" min="0" step="0.000001" inputmode="decimal"
                           value="{{ old('unit_cost') }}" required
                           aria-invalid="{{ $errors->has('unit_cost') ? 'true' : 'false' }}"
                           class="block w-full rounded-lg border {{ $errors->has('unit_cost') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                @error('unit_cost')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="suggested_price" class="block text-sm font-semibold text-emerald-800">Suggested selling price</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                    <input id="suggested_price" name="suggested_price" type="number" min="0" step="0.01" inputmode="decimal"
                           value="{{ old('suggested_price') }}"
                           aria-invalid="{{ $errors->has('suggested_price') ? 'true' : 'false' }}"
                           class="block w-full rounded-lg border {{ $errors->has('suggested_price') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                @error('suggested_price')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="current_market_price" class="block text-sm font-semibold text-blue-800">
                    Current market price <span class="font-normal text-slate-500">(optional)</span>
                </label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">₦</span>
                    <input id="current_market_price" name="current_market_price" type="number" min="0" step="0.01" inputmode="decimal"
                           value="{{ old('current_market_price') }}"
                           aria-invalid="{{ $errors->has('current_market_price') ? 'true' : 'false' }}"
                           class="block w-full rounded-lg border {{ $errors->has('current_market_price') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                @error('current_market_price')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Photos --}}
        <div class="border-y border-slate-100 bg-gradient-to-r from-amber-50 via-white to-rose-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="m21 16-5-5-8 8"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Photos</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Optional images to help identify the item.</p>
                </div>
            </div>
        </div>

        <div class="p-3 sm:p-6">
            <label for="photos" class="block text-sm font-semibold text-amber-800">Item photos (up to 10)</label>
            <div class="mt-1.5 rounded-xl border border-dashed border-amber-200 bg-amber-50/40 p-3 sm:p-4">
                <input id="photos" name="photos[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple
                       data-stock-photo-input data-existing-count="0" data-max-photos="10"
                       class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-amber-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-amber-800 hover:file:bg-amber-200 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                <p class="mt-2 text-xs text-slate-500">Select up to 10 JPG, PNG or WebP images. Maximum 4 MB each. Preview and remove selections before submitting.</p>
                <p data-stock-photo-count class="mt-2 inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-700 ring-1 ring-inset ring-slate-200" aria-live="polite">0 / 10 photos selected</p>
                <div data-stock-photo-errors class="mt-2 text-sm text-red-600" role="alert"></div>
                <div data-stock-photo-preview class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"></div>
            </div>
            @error('photos')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            @error('photos.*')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Actions --}}
        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="text-xs text-slate-500">Registered stock is available to this store immediately.</p>
            <div class="flex flex-col-reverse gap-3 sm:flex-row">
                <a href="{{ route('stores.show', $coordinatorStore) }}" class="oy-btn oy-btn-secondary w-full justify-center sm:w-auto">Cancel</a>
                <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">Register Stock</button>
            </div>
        </div>
    </form>
</div>
@endsection