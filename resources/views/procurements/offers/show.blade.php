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
                    <button
                        type="button"
                        class="oy-btn oy-btn-secondary"
                        data-withdraw-offer
                        data-offer-id="{{ $offer->id }}"
                        data-offer-label="Offer #{{ $offer->id }}"
                        data-withdraw-action="{{ route('procurements.offers.withdraw', $offer) }}"
                    >
                        Withdraw
                    </button>
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

    @if($offer->withdrawal_reason)
        <div class="oy-card mt-6">
            <div class="oy-card-header">
                <h2 class="oy-card-title">
                    Withdrawal Reason
                </h2>

                <p class="oy-card-description">
                    The reason recorded when this offer was withdrawn.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="whitespace-pre-line text-sm text-slate-700">
                    {{ $offer->withdrawal_reason }}
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
    <div
        id="procurement-offer-withdrawal-modal"
        class="{{ $errors->has('withdrawal_reason') ? '' : 'hidden' }} fixed inset-0 z-50 overflow-y-auto"
        data-reopen="{{ $errors->has('withdrawal_reason') && old('withdrawal_offer_id') ? 'true' : 'false' }}"
        aria-labelledby="procurement-offer-withdrawal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="fixed inset-0 bg-slate-900/50"
                data-withdrawal-modal-close
                aria-hidden="true"
            ></div>

            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <h2
                            id="procurement-offer-withdrawal-title"
                            class="text-lg font-semibold text-slate-900"
                        >
                            Withdraw Offer
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Provide a reason for withdrawing this offer.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="text-2xl leading-none text-slate-400 hover:text-slate-600"
                        data-withdrawal-modal-close
                        aria-label="Close withdrawal dialog"
                    >
                        &times;
                    </button>
                </div>

                <form
                    id="procurement-offer-withdrawal-form"
                    method="POST"
                    action="{{ old('withdrawal_offer_id') ? route('procurements.offers.withdraw', old('withdrawal_offer_id')) : '#' }}"
                >
                    @csrf

                    <input
                        type="hidden"
                        id="procurement-offer-withdrawal-offer-id"
                        name="withdrawal_offer_id"
                        value="{{ old('withdrawal_offer_id', $offer->id) }}"
                    >

                    <div class="px-6 py-5">
                        <label
                            for="procurement-offer-withdrawal-reason"
                            class="block text-sm font-medium text-slate-700"
                        >
                            Withdrawal reason
                        </label>

                        <textarea
                            id="procurement-offer-withdrawal-reason"
                            name="withdrawal_reason"
                            rows="4"
                            maxlength="1000"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Why are you withdrawing this offer?"
                        >{{ old('withdrawal_reason') }}</textarea>

                        @error('withdrawal_reason')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                        <p class="mt-2 text-xs text-slate-500">
                            Maximum 1000 characters.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="oy-btn oy-btn-secondary"
                            data-withdrawal-modal-close
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="oy-btn oy-btn-secondary"
                        >
                            Confirm Withdrawal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
