@extends('layouts.app')

@section('content')
@php
    $requiredQuantity = rtrim(rtrim(number_format((float) $procurement->quantity, 2), '0'), '.');
@endphp

<div class="oy-page mx-auto max-w-3xl">

    <a
        href="{{ route('procurements.show', $procurement) }}"
        class="mb-4 inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
    >
        ← Back to Procurement
    </a>

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Submit Supplier Offer</h1>
            <p class="oy-page-description">
                Submit your available quantity and unit price for this procurement requirement.
            </p>
        </div>
    </div>

    {{-- Requirement summary --}}
    <div class="oy-card mb-6">
        <div class="oy-card-header">
            <h2 class="oy-card-title">{{ $procurement->item_name }}</h2>
            <p class="oy-card-description">The requirement you are submitting an offer against.</p>
        </div>

        <div class="oy-card-body">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Required Quantity</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-900">
                        {{ $requiredQuantity }} {{ $procurement->unit }}
                    </dd>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Maximum Unit Price</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-900">
                        ₦{{ number_format((float) $procurement->maximum_unit_price, 2) }}
                    </dd>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Required By</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-900">
                        {{ $procurement->required_by?->format('d M Y') ?? 'Not specified' }}
                    </dd>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Offer Deadline</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-900">
                        {{ $procurement->offer_deadline?->format('d M Y, g:i A') ?? 'Not specified' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Offer form --}}
    <form
        method="POST"
        action="{{ route('procurements.offers.store', $procurement) }}"
        class="oy-card oy-form"
    >
        @csrf

        <div class="oy-card-header">
            <h2 class="oy-card-title">Your Offer</h2>
            <p class="oy-card-description">
                Your offer cannot exceed the required quantity or the maximum unit price.
            </p>
        </div>

        <div class="oy-card-body space-y-6">

            @if ($errors->has('procurement'))
                <div class="oy-alert oy-alert-danger">
                    {{ $errors->first('procurement') }}
                </div>
            @endif

            <div class="oy-form-group">
                <label for="quantity" class="oy-form-label">
                    Quantity Available <span class="oy-form-required">*</span>
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
                    class="oy-input @error('quantity') is-invalid @enderror"
                >

                <p class="oy-form-help">
                    Maximum {{ $requiredQuantity }} {{ $procurement->unit }}.
                </p>

                @error('quantity')
                    <p class="oy-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="oy-form-group">
                <label for="unit_price" class="oy-form-label">
                    Unit Price <span class="oy-form-required">*</span>
                </label>

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-500">
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
                        style="padding-left: 2rem;"
                        class="oy-input @error('unit_price') is-invalid @enderror"
                    >
                </div>

                <p class="oy-form-help">
                    Maximum ₦{{ number_format((float) $procurement->maximum_unit_price, 2) }} per {{ $procurement->unit }}.
                </p>

                @error('unit_price')
                    <p class="oy-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-sm font-medium text-slate-600">Offer total (estimate)</span>
                    <span id="offer-total-preview" class="text-lg font-bold text-slate-900">₦0.00</span>
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    Quantity × unit price. The final total is calculated by the system when you submit.
                </p>
            </div>

            <div class="oy-form-group">
                <label for="notes" class="oy-form-label">Description / Note</label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="5"
                    maxlength="5000"
                    class="oy-textarea @error('notes') is-invalid @enderror"
                    placeholder="Add any relevant information about your offer..."
                >{{ old('notes') }}</textarea>

                @error('notes')
                    <p class="oy-error">{{ $message }}</p>
                @enderror
            </div>

        </div>

        <div class="oy-card-footer flex flex-wrap justify-end gap-3">
            <a href="{{ route('procurements.show', $procurement) }}" class="oy-btn oy-btn-secondary">
                Cancel
            </a>

            <button type="submit" class="oy-btn oy-btn-primary">
                Submit Offer
            </button>
        </div>
    </form>

</div>

<script>
    (function () {
        var quantity = document.getElementById('quantity');
        var price = document.getElementById('unit_price');
        var total = document.getElementById('offer-total-preview');

        if (!quantity || !price || !total) {
            return;
        }

        function update() {
            var value = (parseFloat(quantity.value) || 0) * (parseFloat(price.value) || 0);

            total.textContent = '₦' + value.toLocaleString('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        quantity.addEventListener('input', update);
        price.addEventListener('input', update);
        update();
    })();
</script>
@endsection
