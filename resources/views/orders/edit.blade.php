@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">Update Order</h1>
            </div>

            <p class="oy-page-description">
                {{ $order->order_number }}
                · {{ $order->organization->name }}
            </p>
        </div>

        <div class="oy-page-actions">
            <a href="{{ route('orders.show', $order) }}" class="oy-btn oy-btn-secondary">
                Cancel
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="oy-card max-w-2xl">
        <div class="oy-card-header">
            <h2 class="oy-card-title">Delivery Estimate</h2>
            <p class="oy-card-description">
                Update the expected fulfillment timeline for this order.
            </p>
        </div>

        <div class="oy-card-body">
            <form method="POST" action="{{ route('orders.update', $order) }}">
                @csrf
                @method('PUT')

                <div class="grid gap-6 sm:grid-cols-2">

                    <div>
                        <label for="expected_delivery_days" class="oy-label">
                            Expected Delivery Days
                        </label>

                        <input
                            id="expected_delivery_days"
                            name="expected_delivery_days"
                            type="number"
                            min="1"
                            max="365"
                            required
                            value="{{ old('expected_delivery_days', $order->expected_delivery_days) }}"
                            class="oy-input mt-1 w-full"
                        >

                        @error('expected_delivery_days')
                            <div class="oy-error mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="expected_delivery_date" class="oy-label">
                            Expected Delivery Date
                        </label>

                        <input
                            id="expected_delivery_date"
                            name="expected_delivery_date"
                            type="date"
                            required
                            value="{{ old('expected_delivery_date', $order->expected_delivery_date?->format('Y-m-d')) }}"
                            class="oy-input mt-1 w-full"
                        >

                        @error('expected_delivery_date')
                            <div class="oy-error mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    Updating the delivery estimate does not change the order status,
                    quotation, payment records, or order items.
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="oy-btn oy-btn-primary">
                        Save Delivery Estimate
                    </button>

                    <a href="{{ route('orders.show', $order) }}" class="oy-btn oy-btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
