@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-title-row">
            <div>
                <h1 class="oy-page-title">
                    Edit Supplier Offer
                </h1>

                <p class="oy-page-description">
                    Update the quantity, pricing, or notes for this supplier offer.
                </p>
            </div>

            <div class="oy-page-actions">
                <a
                    href="{{ route('procurements.offers.show', $offer) }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Cancel
                </a>

                <a
                    href="{{ route('procurements.show', $procurement) }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Back to Procurement
                </a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="oy-alert oy-alert-error">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="oy-card">
        <div class="oy-card-header">
            <h2 class="oy-card-title">
                Offer Information
            </h2>

            <p class="oy-card-description">
                Procurement: {{ $procurement->item_name }}
            </p>
        </div>

        <div class="oy-card-body">

            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                    <div>
                        <div class="oy-meta-label">Required Quantity</div>
                        <div class="oy-meta-value">
                            {{ $procurement->quantity }}
                            {{ $procurement->unit }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Maximum Unit Price</div>
                        <div class="oy-meta-value">
                            ₦{{ number_format((float) $procurement->maximum_unit_price, 2) }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Current Status</div>
                        <div class="oy-meta-value">
                            {{ ucwords(str_replace('_', ' ', $offer->status)) }}
                        </div>
                    </div>

                </div>
            </div>

            <form
                method="POST"
                action="{{ route('procurements.offers.update', $offer) }}"
                class="space-y-6"
            >
                @csrf
                @method('PUT')

                <div>
                    <label
                        for="quantity"
                        class="block text-sm font-medium text-slate-700"
                    >
                        Quantity Available
                    </label>

                    <input
                        id="quantity"
                        name="quantity"
                        type="number"
                        min="0.01"
                        step="0.01"
                        max="{{ (float) $procurement->quantity }}"
                        value="{{ old('quantity', $offer->quantity) }}"
                        required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
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
                        class="block text-sm font-medium text-slate-700"
                    >
                        Unit Price (₦)
                    </label>

                    <input
                        id="unit_price"
                        name="unit_price"
                        type="number"
                        min="0"
                        step="0.01"
                        max="{{ (float) $procurement->maximum_unit_price }}"
                        value="{{ old('unit_price', $offer->unit_price) }}"
                        required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >

                    @error('unit_price')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="notes"
                        class="block text-sm font-medium text-slate-700"
                    >
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="5"
                        maxlength="5000"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >{{ old('notes', $offer->notes) }}</textarea>

                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6">
                    <a
                        href="{{ route('procurements.offers.show', $offer) }}"
                        class="oy-btn oy-btn-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="oy-btn oy-btn-primary"
                    >
                        Update Offer
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
