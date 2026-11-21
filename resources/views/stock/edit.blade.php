@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-6 px-4 py-5 sm:py-8">
    <div>
        <a href="{{ route('stock.index') }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-950">← Back to Stock</a>
        <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Edit Stock Item</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Update item details and selling prices. Inventory quantity and cost are managed through stock movements.</p>
    </div>

    <form method="POST" action="{{ route('stock.items.update', $item) }}" enctype="multipart/form-data"
          class="space-y-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        @csrf
        @method('PUT')

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-800">Store</label>
            <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900">{{ $coordinatorStore->name }}</p>
        </div>

        <div>
            <label for="description" class="mb-2 block text-sm font-semibold text-slate-800">Item description</label>
            <input id="description" name="description" value="{{ old('description', $item->description) }}" required maxlength="200"
                   class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="category" class="mb-2 block text-sm font-semibold text-slate-800">Category</label>
                <input id="category" name="category" value="{{ old('category', $item->category) }}" maxlength="80"
                       class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="grade" class="mb-2 block text-sm font-semibold text-slate-800">Grade</label>
                <input id="grade" name="grade" value="{{ old('grade', $item->grade) }}" maxlength="50"
                       class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                @error('grade')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-800">Stock unit</label>
                <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900">{{ strtoupper($item->unit) }} (cannot be changed after registration)</p>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-800">Classification</label>
                <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900">{{ $item->is_raw ? 'Raw material' : 'Finished item' }} (cannot be changed after registration)</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="suggested_price" class="mb-2 block text-sm font-semibold text-slate-800">Suggested selling price (₦)</label>
                <input id="suggested_price" name="suggested_price" type="number" min="0" step="0.01"
                       value="{{ old('suggested_price', $item->suggested_price) }}"
                       class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                @error('suggested_price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="current_market_price" class="mb-2 block text-sm font-semibold text-slate-800">Current market price (₦)</label>
                <input id="current_market_price" name="current_market_price" type="number" min="0" step="0.01"
                       value="{{ old('current_market_price', $item->current_market_price) }}"
                       class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                @error('current_market_price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Existing photos</h2>
                    <p class="mt-1 text-xs text-slate-500">Photos already saved against this stock item.</p>
                </div>

                <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">
                    {{ $item->photos->count() }} / 10
                </span>
            </div>

            @if ($item->photos->isNotEmpty())
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 sm:gap-3 md:grid-cols-5 lg:grid-cols-6">
                    @foreach ($item->photos as $photo)
                        <a href="{{ route('stock.items.photos.show', [$item, $photo]) }}"
                           target="_blank"
                           rel="noopener"
                           class="group block overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md"
                           aria-label="View stock photo">
                            <div class="aspect-square overflow-hidden bg-slate-100">
                                <img
                                    src="{{ route('stock.items.photos.show', [$item, $photo]) }}"
                                    alt="Stock photo"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                >
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-white px-4 py-7 text-center">
                    <svg class="h-8 w-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                        <circle cx="8.5" cy="9" r="1.5"/>
                        <path d="m21 15-5-5L5 20"/>
                    </svg>
                    <p class="mt-2 text-sm font-semibold text-slate-700">No photos have been added</p>
                    <p class="mt-1 text-xs text-slate-500">Uploaded photos will appear here.</p>
                </div>
            @endif
        </div>

        @if ($item->photos->count() < 10)
            <div>
                <label for="photos" class="mb-2 block text-sm font-semibold text-slate-800">Add photos ({{ 10 - $item->photos->count() }} remaining)</label>
                <input id="photos" name="photos[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple
                       data-stock-photo-input data-existing-count="{{ $item->photos->count() }}" data-max-photos="10"
                       class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                <p class="mt-1 text-xs text-slate-500">Existing photos are retained. Add JPG, PNG or WebP images; maximum 4 MB each. Preview and remove new selections before submitting.</p>
                <p data-stock-photo-count class="mt-2 text-sm font-medium text-slate-700" aria-live="polite">0 / {{ 10 - $item->photos->count() }} additional photos selected</p>
                <div data-stock-photo-errors class="mt-2 text-sm text-red-600" role="alert"></div>
                <div data-stock-photo-preview class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"></div>
                @error('photos')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('photos.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        @else
            <p class="text-sm text-slate-500">This stock item has reached the 10-photo limit.</p>
        @endif

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a href="{{ route('stock.index') }}" class="oy-btn oy-btn-secondary">Cancel</a>
            <button type="submit" class="oy-btn oy-btn-primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
