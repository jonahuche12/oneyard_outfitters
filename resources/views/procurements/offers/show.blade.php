@extends('layouts.app')

@section('content')
@php
    $offerStatusBadge = match ($offer->status) {
        'accepted' => 'oy-badge-success',
        'rejected' => 'oy-badge-danger',
        'withdrawn' => 'oy-badge-neutral',
        default => 'oy-badge-warning',
    };

    $requiredQuantity = rtrim(rtrim(number_format((float) $offer->procurement->quantity, 2), '0'), '.');
@endphp

<div class="oy-page">

    <a
        href="{{ route('procurements.show', $offer->procurement) }}"
        class="mb-4 inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
    >
        ← Back to Procurement
    </a>

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">Supplier Offer</h1>

                <span class="oy-badge {{ $offerStatusBadge }}">
                    <span class="oy-status-dot"></span>
                    {{ ucwords(str_replace('_', ' ', $offer->status)) }}
                </span>
            </div>

            <p class="oy-page-description">
                View the details of this supplier offer and its procurement requirement.
            </p>
        </div>

        <div class="oy-page-actions">
            @can('update', $offer)
                <a href="{{ route('procurements.offers.edit', $offer) }}" class="oy-btn oy-btn-primary">
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
        </div>
    </div>

    @foreach (['status', 'success'] as $flashKey)
        @if (session($flashKey))
            <div class="oy-alert oy-alert-success mb-6">
                {{ session($flashKey) }}
            </div>
        @endif
    @endforeach

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Offer Details --}}
        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Offer Details</h2>
                <p class="oy-card-description">Pricing and quantity submitted for this procurement.</p>
            </div>

            <div class="oy-card-body">
                <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quantity</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ number_format((float) $offer->quantity, 2) }}
                            {{ $offer->procurement->unit }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unit Price</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            ₦{{ number_format((float) $offer->unit_price, 2) }}
                        </dd>
                    </div>

                    <div class="sm:col-span-2">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Price</dt>
                                <dd class="text-xl font-bold tracking-tight text-slate-900">
                                    ₦{{ number_format((float) $offer->total_price, 2) }}
                                </dd>
                            </div>
                        </div>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Submission --}}
        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Submission</h2>
                <p class="oy-card-description">Information about the staff member who submitted the offer.</p>
            </div>

            <div class="oy-card-body">
                <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Submitted By</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ $offer->user?->name ?? 'Unknown user' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Submitted At</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ $offer->submitted_at?->format('d M Y, g:i A') ?? 'Not recorded' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Procurement</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ $offer->procurement->item_name }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

    </div>

    @if ($offer->notes)
        <div class="oy-card mt-6">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Notes</h2>
            </div>

            <div class="oy-card-body">
                <div class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ $offer->notes }}</div>
            </div>
        </div>
    @endif

    @if ($offer->withdrawal_reason)
        <div class="oy-card mt-6">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Withdrawal Reason</h2>
                <p class="oy-card-description">The reason recorded when this offer was withdrawn.</p>
            </div>

            <div class="oy-card-body">
                <div class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ $offer->withdrawal_reason }}</div>
            </div>
        </div>
    @endif

    {{-- Delivery confirmation and audit details --}}
    @if ($offer->status === \App\Models\ProcurementOffer::STATUS_DELIVERED)
        <div class="oy-card mt-6">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Delivery Confirmation</h2>
                <p class="oy-card-description">
                    Recorded delivery details for this accepted offer.
                </p>
            </div>

            <div class="oy-card-body">
                <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Delivered Quantity
                        </dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ number_format((float) $offer->delivered_quantity, 2) }}
                            {{ $offer->procurement->unit }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Confirmed By
                        </dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ $offer->deliveredBy?->name ?? 'Unknown user' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Confirmed At
                        </dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ $offer->delivered_at?->format('d M Y, g:i A') ?? 'Not recorded' }}
                        </dd>
                    </div>

                    @if ($offer->delivery_notes)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Delivery Notes
                            </dt>
                            <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                                {{ $offer->delivery_notes }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    @elseif ($offer->status === \App\Models\ProcurementOffer::STATUS_ACCEPTED)
        @can('deliver', $offer)
            <div class="oy-card mt-6">
                <div class="oy-card-header">
                    <h2 class="oy-card-title">Confirm Delivery</h2>
                    <p class="oy-card-description">
                        Record the quantity actually delivered. This action can only be completed once.
                    </p>
                </div>

                <div class="oy-card-body">
                    <form
                        method="POST"
                        action="{{ route('procurements.offers.deliver', $offer) }}"
                        class="space-y-5"
                    >
                        @csrf

                        <div>
                            <label for="delivered_quantity" class="oy-form-label">
                                Delivered Quantity <span class="oy-form-required">*</span>
                            </label>
                            <input
                                id="delivered_quantity"
                                name="delivered_quantity"
                                type="number"
                                min="0.01"
                                max="{{ (float) $offer->quantity }}"
                                step="0.01"
                                required
                                value="{{ old('delivered_quantity') }}"
                                class="oy-input @error('delivered_quantity') is-invalid @enderror"
                            >
                            <p class="oy-form-help">
                                Maximum accepted quantity:
                                {{ number_format((float) $offer->quantity, 2) }}
                                {{ $offer->procurement->unit }}.
                            </p>
                            @error('delivered_quantity')
                                <p class="oy-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="delivery_notes" class="oy-form-label">
                                Delivery Notes
                            </label>
                            <textarea
                                id="delivery_notes"
                                name="delivery_notes"
                                rows="3"
                                maxlength="1000"
                                class="oy-textarea @error('delivery_notes') is-invalid @enderror"
                            >{{ old('delivery_notes') }}</textarea>
                            @error('delivery_notes')
                                <p class="oy-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="text-sm text-slate-600">
                            Confirmation records delivery only. Commission is handled separately.
                        </p>

                        <button type="submit" class="oy-btn oy-btn-primary">
                            Confirm Delivery
                        </button>
                    </form>
                </div>
            </div>
        @endcan
    @endif

    {{-- Procurement requirement --}}
    <div class="oy-card mt-6">
        <div class="oy-card-header">
            <h2 class="oy-card-title">Procurement Requirement</h2>
            <p class="oy-card-description">The requirement this offer was submitted against.</p>
        </div>

        <div class="oy-card-body">
            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Item</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        {{ $offer->procurement->item_name }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Required Quantity</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        {{ $requiredQuantity }} {{ $offer->procurement->unit }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Maximum Unit Price</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        ₦{{ number_format((float) $offer->procurement->maximum_unit_price, 2) }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Required By</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        {{ $offer->procurement->required_by?->format('d M Y') ?? 'Not specified' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Offer Deadline</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        {{ $offer->procurement->offer_deadline?->format('d M Y, g:i A') ?? 'Not specified' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

</div>

{{-- Withdrawal modal (wired by resources/js/procurements/offers.js) --}}
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
                class="oy-form"
            >
                @csrf

                <input
                    type="hidden"
                    id="procurement-offer-withdrawal-offer-id"
                    name="withdrawal_offer_id"
                    value="{{ old('withdrawal_offer_id', $offer->id) }}"
                >

                <div class="px-6 py-5">
                    <label for="procurement-offer-withdrawal-reason" class="oy-form-label">
                        Withdrawal reason <span class="oy-form-required">*</span>
                    </label>

                    <textarea
                        id="procurement-offer-withdrawal-reason"
                        name="withdrawal_reason"
                        rows="4"
                        maxlength="1000"
                        required
                        class="oy-textarea @error('withdrawal_reason') is-invalid @enderror"
                        placeholder="Why are you withdrawing this offer?"
                    >{{ old('withdrawal_reason') }}</textarea>

                    @error('withdrawal_reason')
                        <p class="oy-error">{{ $message }}</p>
                    @enderror

                    <p class="oy-form-help">Maximum 1000 characters.</p>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" class="oy-btn oy-btn-secondary" data-withdrawal-modal-close>
                        Cancel
                    </button>

                    <button type="submit" class="oy-btn oy-btn-danger">
                        Confirm Withdrawal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
