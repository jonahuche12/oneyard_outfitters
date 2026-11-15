@php($isEdit = isset($store))

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="store-name" class="block text-sm font-medium text-slate-700">Store name</label>
        <input
            id="store-name"
            name="name"
            type="text"
            maxlength="120"
            required
            value="{{ old('name', $store->name ?? '') }}"
            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
        >
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="store-code" class="block text-sm font-medium text-slate-700">Code</label>
        <input
            id="store-code"
            name="code"
            type="text"
            maxlength="20"
            required
            value="{{ old('code', $store->code ?? '') }}"
            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
        >
        <p class="mt-1 text-xs text-slate-500">Letters, numbers and hyphens, for example IKJ-01. Saved in capitals.</p>
        @error('code')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="store-type" class="block text-sm font-medium text-slate-700">Type</label>

        @if ($isEdit)
            <p id="store-type" class="mt-1 text-sm text-slate-900">
                {{ $store->isWarehouse() ? 'General warehouse' : 'Store' }}
                <span class="text-slate-500">(cannot be changed)</span>
            </p>
        @else
            <select
                id="store-type"
                name="type"
                required
                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
            >
                <option value="store" @selected(old('type', 'store') === 'store')>Store</option>
                <option value="warehouse" @selected(old('type') === 'warehouse') @disabled($warehouseExists)>General warehouse</option>
            </select>

            @if ($warehouseExists)
                <p class="mt-1 text-xs text-slate-500">A general warehouse already exists.</p>
            @endif

            @error('type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        @endif
    </div>

    <div>
        <label for="store-phone" class="block text-sm font-medium text-slate-700">Phone <span class="font-normal text-slate-400">(optional)</span></label>
        <input
            id="store-phone"
            name="phone"
            type="text"
            maxlength="30"
            value="{{ old('phone', $store->phone ?? '') }}"
            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
        >
        @error('phone')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="store-address" class="block text-sm font-medium text-slate-700">Address <span class="font-normal text-slate-400">(optional)</span></label>
        <textarea
            id="store-address"
            name="address"
            rows="3"
            maxlength="500"
            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
        >{{ old('address', $store->address ?? '') }}</textarea>
        @error('address')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
