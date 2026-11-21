@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-6 px-4 py-5 sm:py-8">
    <div>
        <a href="{{ route('stores.show', $coordinatorStore) }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-950">← Back to {{ $coordinatorStore->name }}</a>
        <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Register Stock</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
            Record the item, its opening quantity and cost. The stock becomes available to this store immediately.
        </p>
    </div>

    <form method="POST" action="{{ route('stock.items.store') }}" enctype="multipart/form-data"
          class="space-y-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        @csrf

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-800">Store</label>
            <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900">{{ $coordinatorStore->name }}</p>
        </div>

        <div>
            <label for="description" class="mb-2 block text-sm font-semibold text-slate-800">Item description</label>
            <input id="description" name="description" value="{{ old('description') }}" required maxlength="200"
                   class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="category" class="mb-2 block text-sm font-semibold text-slate-800">Category</label>
                <input id="category" name="category" value="{{ old('category') }}" maxlength="80"
                       class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="grade" class="mb-2 block text-sm font-semibold text-slate-800">Grade</label>
                <input id="grade" name="grade" value="{{ old('grade') }}" maxlength="50"
                       class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                @error('grade')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="unit" class="mb-2 block text-sm font-semibold text-slate-800">Unit</label>
                <select id="unit" name="unit" required
                        class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                    <option value="">Select unit</option>
                    @foreach (\App\Models\StockItem::UNITS as $unit)
                        <option value="{{ $unit }}" @selected(old('unit') === $unit)>{{ strtoupper($unit) }}</option>
                    @endforeach
                </select>
                @error('unit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="quantity" class="mb-2 block text-sm font-semibold text-slate-800">Opening quantity</label>
                <input id="quantity" name="quantity" type="number" min="0.001" step="0.001"
                       value="{{ old('quantity') }}" required
                       class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                @error('quantity')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="unit_cost" class="mb-2 block text-sm font-semibold text-slate-800">Cost per unit (₦)</label>
                <input id="unit_cost" name="unit_cost" type="number" min="0" step="0.000001"
                       value="{{ old('unit_cost') }}" required
                       class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                @error('unit_cost')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="suggested_price" class="mb-2 block text-sm font-semibold text-slate-800">Suggested selling price (₦)</label>
                <input id="suggested_price" name="suggested_price" type="number" min="0" step="0.01"
                       value="{{ old('suggested_price') }}"
                       class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                @error('suggested_price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="current_market_price" class="mb-2 block text-sm font-semibold text-slate-800">Current market price (₦, optional)</label>
            <input id="current_market_price" name="current_market_price" type="number" min="0" step="0.01"
                   value="{{ old('current_market_price') }}"
                   class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            @error('current_market_price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_raw" value="1" @checked(old('is_raw')) class="rounded border-slate-300 text-slate-900 focus:ring-2 focus:ring-slate-300">
                Raw material
            </label>
            @error('is_raw')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="photos" class="mb-2 block text-sm font-semibold text-slate-800">Item photos (up to 10)</label>
            <input id="photos" name="photos[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple
                   data-stock-photo-input data-existing-count="0" data-max-photos="10"
                   class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            <p class="mt-1 text-xs text-slate-500">Select up to 10 JPG, PNG or WebP images. Maximum 4 MB each. Preview and remove selections before submitting.</p>
            <p data-stock-photo-count class="mt-2 text-sm font-medium text-slate-700" aria-live="polite">0 / 10 photos selected</p>
            <div data-stock-photo-errors class="mt-2 text-sm text-red-600" role="alert"></div>
            <div data-stock-photo-preview class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"></div>
            @error('photos')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('photos.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a href="{{ route('stores.show', $coordinatorStore) }}" class="oy-btn oy-btn-secondary">Cancel</a>
            <button type="submit" class="oy-btn oy-btn-primary">Register Stock</button>
        </div>
    </form>
</div>
@endsection
