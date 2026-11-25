@extends('layouts.app')

@section('content')
@php
    $backUrl = $returnToSale ? route('stores.sales.create', $store) : route('stores.customers.index', $store);
    $backLabel = $returnToSale ? 'Back to sale' : 'Customers';

    $inputClass = fn (string $field) => 'block min-h-10 w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:ring-2 sm:min-h-12 sm:py-3 sm:text-base '
        .($errors->has($field)
            ? 'border-red-300 focus:border-red-500 focus:ring-red-100'
            : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100');

    $labelClass = 'mb-1 block text-xs font-semibold text-slate-700 sm:mb-1.5 sm:text-sm';
    $errorClass = 'mt-1 text-xs text-red-600 sm:text-sm';
@endphp

<div class="mx-auto max-w-3xl space-y-5 px-4 py-5 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Hero --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-4 py-5 sm:px-7 sm:py-6">
            <a href="{{ $backUrl }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition hover:text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m15 18-6-6 6-6"/>
                    <path d="M20 12H9"/>
                </svg>
                {{ $backLabel }}
            </a>

            <div class="mt-4 min-w-0 sm:mt-5">
                <h1 class="break-words text-xl font-bold tracking-tight text-white sm:text-3xl">Register customer</h1>
                <p class="mt-1.5 text-xs leading-5 text-slate-300 sm:mt-2 sm:text-sm sm:leading-6">
                    Add a customer to {{ $store->name }}. Registration does not require a sale.
                </p>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('stores.customers.store', $store) }}"
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @csrf
        @if ($returnToSale)
            <input type="hidden" name="return_to" value="sale">
        @endif

        <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 sm:h-10 sm:w-10">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 21a8 8 0 0 1 16 0"/>
                </svg>
            </span>
            <div>
                <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Customer details</h2>
                <p class="mt-0.5 text-xs text-slate-500 sm:mt-1 sm:text-sm">Fields marked * are required.</p>
            </div>
        </div>

        <div class="grid gap-3 p-4 sm:grid-cols-2 sm:gap-4 sm:p-6">
            <div class="sm:col-span-2">
                <label for="name" class="{{ $labelClass }}">Customer name <span class="text-red-600">*</span></label>
                <input id="name" name="name" value="{{ old('name') }}" required maxlength="200"
                       class="{{ $inputClass('name') }}">
                @error('name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="{{ $labelClass }}">Phone number <span class="text-red-600">*</span></label>
                <input id="phone" name="phone" value="{{ old('phone') }}" required maxlength="50"
                       class="{{ $inputClass('phone') }}">
                @error('phone') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="alternate_phone" class="{{ $labelClass }}">Alternative phone</label>
                <input id="alternate_phone" name="alternate_phone" value="{{ old('alternate_phone') }}" maxlength="50"
                       class="{{ $inputClass('alternate_phone') }}">
                @error('alternate_phone') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="email" class="{{ $labelClass }}">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255"
                       class="{{ $inputClass('email') }}">
                @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="address" class="{{ $labelClass }}">Address</label>
                <textarea id="address" name="address" rows="3"
                          class="{{ $inputClass('address') }}">{{ old('address') }}</textarea>
                @error('address') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="notes" class="{{ $labelClass }}">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="{{ $inputClass('notes') }}">{{ old('notes') }}</textarea>
                @error('notes') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/70 px-4 py-4 sm:flex-row sm:justify-end sm:gap-3 sm:px-6">
            <a href="{{ $backUrl }}"
               class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:min-h-12 sm:py-3">Cancel</a>
            <button type="submit"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:min-h-12 sm:py-3">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
                Save customer
            </button>
        </div>
    </form>
</div>
@endsection