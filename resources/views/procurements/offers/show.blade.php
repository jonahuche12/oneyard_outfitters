@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-title-row">
            <div>
                <h1 class="oy-page-title">
                    Supplier Offer
                </h1>

                <p class="oy-page-description">
                    View the details of this supplier offer and its procurement requirement.
                </p>
            </div>

            <div class="oy-page-actions">
                @can('update', $offer)
                    <a
                        href="{{ route('procurements.offers.edit', $offer) }}"
                        class="oy-btn oy-btn-primary"
                    >
                        Edit Offer
                    </a>
                @endcan

                @can('withdraw', $offer)
                    <form
                        method="POST"
                        action="{{ route('procurements.offers.withdraw', $offer) }}"
                        onsubmit="return confirm('Withdraw this offer?');"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="oy-btn oy-btn-secondary"
                        >
                            Withdraw
                        </button>
                    </form>
                @endcan

                <a
                    href="{{ route('procurements.show', $offer->procurement) }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Back to Procurement
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="oy-alert oy-alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Offer Details --}}
        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">
                    Offer Details
                </h2>

                <p class="oy-card-description">
                    Pricing and quantity submitted for this procurement.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                    <div>
                        <div class="oy-meta-label">Quantity</div>
                        <div class="oy-meta-value">
                            {{ number_format((float) $offer->quantity, 2) }}
                            {{ $offer->procurement->unit }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Unit Price</div>
                        <div class="oy-meta-value">
                            ₦{{ number_format((float) $offer->unit_price, 2) }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Total Price</div>
                        <div class="oy-meta-value">
                            ₦{{ number_format((float) $offer->total_price, 2) }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Status</div>
                        <div class="oy-meta-value">
                            {{ ucwords(str_replace('_', ' ', $offer->status)) }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Submission --}}
        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">
                    Submission
                </h2>

                <p class="oy-card-description">
                    Information about the staff member who submitted the offer.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                    <div class="sm:col-span-2">
                        <div class="oy-meta-label">Submitted By</div>
                        <div class="oy-meta-value">
                            {{ $offer->user?->name ?? 'Unknown user' }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Submitted At</div>
                        <div class="oy-meta-value">
                            {{ $offer->submitted_at?->format('d M Y, g:i A') ?? 'Not recorded' }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Procurement</div>
                        <div class="oy-meta-value">
                            {{ $offer->procurement->item_name }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    @if($offer->notes)
        <div class="oy-card mt-6">
            <div class="oy-card-header">
                <h2 class="oy-card-title">
                    Notes
                </h2>
            </div>

            <div class="oy-card-body">
                <div class="whitespace-pre-line text-sm text-slate-700">
                    {{ $offer->notes }}
                </div>
            </div>
        </div>
    @endif

    <div class="oy-card mt-6">
        <div class="oy-card-header">
            <h2 class="oy-card-title">
                Procurement Requirement
            </h2>

            <p class="oy-card-description">
                The requirement this offer was submitted against.
            </p>
        </div>

        <div class="oy-card-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                <div class="sm:col-span-2">
                    <div class="oy-meta-label">Item</div>
                    <div class="oy-meta-value">
                        {{ $offer->procurement->item_name }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta-label">Required Quantity</div>
                    <div class="oy-meta-value">
                        {{ $offer->procurement->quantity }}
                        {{ $offer->procurement->unit }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta-label">Maximum Unit Price</div>
                    <div class="oy-meta-value">
                        ₦{{ number_format((float) $offer->procurement->maximum_unit_price, 2) }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta-label">Required By</div>
                    <div class="oy-meta-value">
                        {{ $offer->procurement->required_by?->format('d M Y') ?? 'Not specified' }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta-label">Offer Deadline</div>
                    <div class="oy-meta-value">
                        {{ $offer->procurement->offer_deadline?->format('d M Y, g:i A') ?? 'Not specified' }}
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
