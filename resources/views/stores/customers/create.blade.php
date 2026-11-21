@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div>
        <a href="{{ $returnToSale ? route('stores.sales.create', $store) : route('stores.customers.index', $store) }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">
            ← {{ $returnToSale ? 'Back to sale' : 'Customers' }}
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Register customer</h1>
        <p class="mt-1 text-sm text-slate-500">Add a customer to {{ $store->name }}. Registration does not require a sale.</p>
    </div>

    <form method="POST" action="{{ route('stores.customers.store', $store) }}"
          class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if ($returnToSale)
            <input type="hidden" name="return_to" value="sale">
        @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Customer name *</label>
                <input id="name" name="name" value="{{ old('name') }}" required maxlength="200"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone number *</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" required maxlength="50"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="alternate_phone" class="mb-1 block text-sm font-medium text-slate-700">Alternative phone</label>
                <input id="alternate_phone" name="alternate_phone" value="{{ old('alternate_phone') }}" maxlength="50"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                @error('alternate_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                <textarea id="address" name="address" rows="3"
                          class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">{{ old('address') }}</textarea>
                @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">{{ old('notes') }}</textarea>
                @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ $returnToSale ? route('stores.sales.create', $store) : route('stores.customers.index', $store) }}" class="oy-btn oy-btn-secondary">Cancel</a>
            <button type="submit" class="oy-btn oy-btn-primary">Save customer</button>
        </div>
    </form>
</div>
@endsection
