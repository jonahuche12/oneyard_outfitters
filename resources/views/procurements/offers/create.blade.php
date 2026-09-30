@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <a
                href="{{ route('procurements.show', $procurement) }}"
                class="text-sm text-gray-600 hover:text-gray-900"
            >
                ← Back to Procurement
            </a>

            <h1 class="mt-3 text-2xl font-semibold text-gray-900">
                Submit Supplier Offer
            </h1>

            <p class="mt-1 text-sm text-gray-600">
                Submit your available quantity and unit price for this procurement requirement.
            </p>
        </div>

        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">
                    {{ $procurement->item_name }}
                </h2>

                <p class="oy-card-description">
                    Required quantity:
                    {{ number_format((float) $procurement->quantity, 2) }}
                    {{ $procurement->unit }}
                </p>
            </div>

            <div class="p-6">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">
                            Maximum Unit Price
                        </dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">
                            ₦{{ number_format((float) $procurement->maximum_unit_price, 2) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">
                            Required By
                        </dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">
                            {{ $procurement->required_by?->format('d M Y') ?? 'Not specified' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('procurements.offers.store', $procurement) }}"
            class="oy-card"
        >
            @csrf

            <div class="p-6 space-y-6">
                @if($errors->has('procurement'))
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        {{ $errors->first('procurement') }}
                    </div>
                @endif

                <div>
                    <label
                        for="quantity"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Quantity Available
                    </label>

                    <input
                        id="quantity"
                        name="quantity"
                        type="number"
                        step="0.01"
                        min="0.01"
                        max="{{ (float) $procurement->quantity }}"
                        value="{{ old('quantity') }}"
                        required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >

                    @error('quantity')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="unit_price"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Unit Price
                    </label>

                    <div class="relative mt-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                            ₦
                        </span>

                        <input
                            id="unit_price"
                            name="unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            max="{{ (float) $procurement->maximum_unit_price }}"
                            value="{{ old('unit_price') }}"
                            required
                            class="block w-full rounded-lg border-gray-300 pl-8 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    @error('unit_price')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="notes"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Description / Note
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="5"
                        maxlength="5000"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Add any relevant information about your offer..."
                    >{{ old('notes') }}</textarea>

                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <a
                        href="{{ route('procurements.show', $procurement) }}"
                        class="oy-btn oy-btn-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="oy-btn oy-btn-primary"
                    >
                        Submit Offer
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
