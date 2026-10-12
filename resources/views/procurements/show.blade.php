@extends('layouts.app')

@section('content')
@php
    $procurementStatusBadge = match ($procurement->status) {
        'fulfilled' => 'oy-badge-success',
        'cancelled' => 'oy-badge-danger',
        'ready', 'in_progress' => 'oy-badge-warning',
        default => 'oy-badge-neutral',
    };

    $procurementPriorityBadge = match ($procurement->priority) {
        'urgent' => 'oy-badge-danger',
        'high' => 'oy-badge-warning',
        default => 'oy-badge-neutral',
    };
@endphp
<div class="oy-page">

    <div class="oy-page-header">
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="oy-page-title-row">
                    <h1 class="oy-page-title">Procurement Requirement</h1>

                    <span class="oy-badge {{ $procurementStatusBadge }}">
                        <span class="oy-status-dot"></span>
                        {{ ucwords(str_replace('_', ' ', $procurement->status)) }}
                    </span>

                    <span class="oy-badge {{ $procurementPriorityBadge }}">
                        {{ ucfirst($procurement->priority) }} priority
                    </span>
                </div>

                <p class="oy-page-description">
                    View procurement requirement details, reference photos,
                    and supplier offers.
                </p>
            </div>

            <div class="oy-page-actions">
                @if(
                    $procurement->status === \App\Models\Procurement::STATUS_READY
                    && (
                        $procurement->offer_deadline === null
                        || $procurement->offer_deadline->isFuture()
                    )
                )
                    @can('submitOffer', $procurement)
                        <a
                            href="{{ route('procurements.offers.create', $procurement) }}"
                            class="oy-btn oy-btn-primary"
                        >
                            Submit Offer
                        </a>
                    @endcan
                @endif

                @can('update', $procurement)
                    <a
                        href="{{ route('procurements.edit', $procurement) }}"
                        class="oy-btn oy-btn-secondary"
                    >
                        Edit Procurement
                    </a>
                @endcan

                <a
                    href="{{ route('procurements.index') }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Back to Procurement Center
                </a>
            </div>
        </div>
    </div>

    @foreach (['status', 'success'] as $flashKey)
        @if (session($flashKey))
            <div class="oy-alert oy-alert-success mb-6">
                {{ session($flashKey) }}
            </div>
        @endif
    @endforeach

    @if($errors->any())
        <div class="oy-alert oy-alert-danger mb-6">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $referencePhotos = $procurement->attachments
            ->filter(function ($attachment) {
                return $attachment->product_specification_artifact_id === null
                    && in_array(
                        $attachment->mime_type,
                        [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/webp',
                        ],
                        true
                    );
            })
            ->values();

        $referencePhotoData = $referencePhotos->map(function ($photo) use ($procurement) {
            return [
                'src' => route('procurements.attachments.show', [$procurement, $photo]),
                'alt' => $photo->original_name ?: 'Procurement reference photo',
            ];
        })->values();
    @endphp

    {{-- Reference Photos --}}
    @if($referencePhotos->isNotEmpty())
        <div class="oy-card mb-6" data-procurement-photo-section>
            <button
                type="button"
                class="oy-card-header flex w-full items-center justify-between gap-4 text-left"
                data-procurement-photo-toggle
                aria-expanded="false"
                aria-controls="procurement-reference-photo-gallery"
            >
                <div>
                    <h2 class="oy-card-title">
                        Reference Photos
                    </h2>

                    <p class="oy-card-description">
                        {{ $referencePhotos->count() }}
                        {{ \Illuminate\Support\Str::plural('reference photo', $referencePhotos->count()) }}
                        attached to this procurement requirement.
                    </p>
                </div>

                <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition-transform duration-200"
                    data-procurement-photo-chevron
                    aria-hidden="true"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </span>
            </button>

            <div
                id="procurement-reference-photo-gallery"
                class="hidden border-t border-slate-100"
                data-procurement-photo-gallery
            >
                <div class="oy-card-body">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach($referencePhotos as $photo)
                            <button
                                type="button"
                                class="group relative aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                                data-procurement-photo-modal
                                data-photo-index="{{ $loop->index }}"
                                data-photo-images='@json($referencePhotoData)'
                                aria-label="View {{ $photo->original_name ?: 'reference photo' }}"
                            >
                                <img
                                    src="{{ route('procurements.attachments.show', [$procurement, $photo]) }}"
                                    alt="{{ $photo->original_name ?: 'Procurement reference photo' }}"
                                    class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                                >

                                <span class="absolute inset-x-0 bottom-0 bg-slate-950/60 px-3 py-2 text-left text-xs font-medium text-white opacity-0 transition group-hover:opacity-100">
                                    View photo
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Requirement --}}
        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">
                    Requirement
                </h2>

                <p class="oy-card-description">
                    Core details of the requested item.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                    <div class="sm:col-span-2">
                        <div class="oy-meta">Item</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            {{ $procurement->item_name }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta">Quantity</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            {{ rtrim(rtrim(number_format((float) $procurement->quantity, 2), '0'), '.') }}
                            {{ $procurement->unit }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta">Maximum Unit Price</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            ₦{{ number_format((float) $procurement->maximum_unit_price, 2) }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta">Commission per Unit</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            ₦{{ number_format((float) $procurement->commission_per_unit, 2) }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta">Required By</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            {{ $procurement->required_by?->format('d M Y') ?? 'Not specified' }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Procurement Status --}}
        <div class="oy-card">
            <div class="oy-card-header">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="oy-card-title">
                            Procurement Status
                        </h2>

                        <p class="oy-card-description">
                            Current status, offer window, and procurement activity.
                        </p>
                    </div>


                </div>
            </div>

            <div class="oy-card-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                    <div>
                        <div class="oy-meta">Status</div>
                        <div class="mt-1"><span class="oy-badge {{ $procurementStatusBadge }}">{{ ucwords(str_replace('_', ' ', $procurement->status)) }}</span></div>
                    </div>

                    <div>
                        <div class="oy-meta">Priority</div>
                        <div class="mt-1"><span class="oy-badge {{ $procurementPriorityBadge }}">{{ ucfirst($procurement->priority) }}</span></div>
                    </div>

                    <div>
                        <div class="oy-meta">Offer Deadline</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            {{ $procurement->offer_deadline?->format('d M Y H:i') ?? 'Not specified' }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta">Offers</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            {{ $procurement->offers->count() }}
                            {{ \Illuminate\Support\Str::plural('offer', $procurement->offers->count()) }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta">Created By</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            {{ $procurement->createdBy?->name ?? 'Unknown' }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta">Last Updated By</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            {{ $procurement->updatedBy?->name ?? 'Not updated' }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    @if($procurement->order)
        <div class="oy-card mt-6">
            <div class="oy-card-header">
                <h2 class="oy-card-title">
                    Order
                </h2>
            </div>

            <div class="oy-card-body">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <div class="oy-meta">Order Number</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            @can('view', $procurement->order)
                                <a href="{{ route('orders.show', $procurement->order) }}" class="hover:underline">
                                    {{ $procurement->order->order_number }}
                                </a>
                            @else
                                {{ $procurement->order->order_number }}
                            @endcan
                        </div>
                    </div>

                    @if($procurement->order->organization)
                        <div>
                            <div class="oy-meta">Organization</div>
                            <div class="mt-1 text-sm font-medium text-slate-900">
                                {{ $procurement->order->organization->name }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($procurement->description || $procurement->notes)
        <div class="oy-card mt-6">
            <div class="oy-card-header">
                <h2 class="oy-card-title">
                    Additional Information
                </h2>
            </div>

            <div class="oy-card-body space-y-5">

                @if($procurement->description)
                    <div>
                        <div class="oy-meta">Description</div>
                        <div class="mt-1 whitespace-pre-line">
                            {{ $procurement->description }}
                        </div>
                    </div>
                @endif

                @if($procurement->notes)
                    <div>
                        <div class="oy-meta">Notes</div>
                        <div class="mt-1 whitespace-pre-line">
                            {{ $procurement->notes }}
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @endif

    {{-- Supplier Offers --}}
    <div class="oy-card mt-6">
        <div class="oy-card-header">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="oy-card-title">
                        Supplier Offers
                    </h2>

                    <p class="oy-card-description">
                        Offers submitted for this procurement requirement.
                    </p>
                </div>

                @if(
                    $procurement->status === \App\Models\Procurement::STATUS_READY
                    && (
                        $procurement->offer_deadline === null
                        || $procurement->offer_deadline->isFuture()
                    )
                )
                    @can('submitOffer', $procurement)
                        <a
                            href="{{ route('procurements.offers.create', $procurement) }}"
                            class="oy-btn oy-btn-primary"
                        >
                            Submit Offer
                        </a>
                    @endcan
                @endif
            </div>
        </div>

        <div class="oy-card-body">
            @if($procurement->offers->isEmpty())
                <p class="text-sm text-slate-500">
                    No supplier offers have been submitted yet.
                </p>
            @else
                <div class="space-y-4">
                    @foreach($procurement->offers as $offer)
                        @php
                            $offerStatusBadge = match ($offer->status) {
                                'accepted' => 'oy-badge-success',
                                'rejected' => 'oy-badge-danger',
                                'withdrawn' => 'oy-badge-neutral',
                                default => 'oy-badge-warning',
                            };
                        @endphp
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

                                <div class="min-w-0">
                                    <div class="font-medium text-slate-900">
                                        {{ $offer->user?->name ?? 'Unknown user' }}

                                        @if($offer->user_id === auth()->id())
                                            <span class="ml-2 text-xs font-normal text-slate-500">
                                                Your offer
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-1 text-sm text-slate-500">
                                        Submitted
                                        {{ $offer->submitted_at?->format('d M Y, g:i A') ?? 'Not recorded' }}
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="oy-badge {{ $offerStatusBadge }}">{{ ucwords(str_replace('_', ' ', $offer->status)) }}</span>

                                    @can('view', $offer)
                                        <a
                                            href="{{ route('procurements.offers.show', $offer) }}"
                                            class="oy-btn oy-btn-secondary"
                                        >
                                            View
                                        </a>
                                    @endcan

                                    @can('update', $offer)
                                        <a
                                            href="{{ route('procurements.offers.edit', $offer) }}"
                                            class="oy-btn oy-btn-secondary"
                                        >
                                            Edit
                                        </a>
                                    @endcan

                                    @can('accept', $offer)
                                        <button
                                            type="button"
                                            class="oy-btn oy-btn-primary"
                                            data-accept-offer
                                            data-offer-id="{{ $offer->id }}"
                                            data-offer-label="Offer #{{ $offer->id }}"
                                            data-accept-action="{{ route('procurements.offers.accept', $offer) }}"
                                        >
                                            Accept Offer
                                        </button>
                                    @endcan

                                    @can('reject', $offer)
                                        <button
                                            type="button"
                                            class="oy-btn oy-btn-secondary"
                                            data-reject-offer
                                            data-offer-id="{{ $offer->id }}"
                                            data-offer-label="Offer #{{ $offer->id }}"
                                            data-reject-action="{{ route('procurements.offers.reject', $offer) }}"
                                        >
                                            Reject Offer
                                        </button>
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

                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <div class="oy-meta">Quantity</div>
                                    <div class="mt-1 text-sm font-medium text-slate-900">
                                        {{ number_format((float) $offer->quantity, 2) }}
                                        {{ $procurement->unit }}
                                    </div>
                                </div>

                                <div>
                                    <div class="oy-meta">Unit Price</div>
                                    <div class="mt-1 text-sm font-medium text-slate-900">
                                        ₦{{ number_format((float) $offer->unit_price, 2) }}
                                    </div>
                                </div>

                                <div>
                                    <div class="oy-meta">Total Price</div>
                                    <div class="mt-1 text-sm font-medium text-slate-900">
                                        ₦{{ number_format((float) $offer->total_price, 2) }}
                                    </div>
                                </div>
                            </div>

                            @if($offer->notes)
                                <div class="mt-4 border-t border-slate-100 pt-4">
                                    <div class="oy-meta">Notes</div>

                                    <div class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                        {{ $offer->notes }}
                                    </div>
                                </div>
                            @endif

                            @if($offer->withdrawal_reason)
                                <div class="mt-4 border-t border-slate-100 pt-4">
                                    <div class="oy-meta">Withdrawal Reason</div>

                                    <div class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                        {{ $offer->withdrawal_reason }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div
        id="procurement-offer-reject-modal"
        class="hidden fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="procurement-offer-reject-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="fixed inset-0 bg-slate-900/50"
                data-reject-modal-close
                aria-hidden="true"
            ></div>

            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <h2
                            id="procurement-offer-reject-title"
                            class="text-lg font-semibold text-slate-900"
                        >
                            Reject Offer
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Confirm that you want to reject this procurement offer.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="text-2xl leading-none text-slate-400 hover:text-slate-600"
                        data-reject-modal-close
                        aria-label="Close rejection dialog"
                    >
                        &times;
                    </button>
                </div>

                <form
                    id="procurement-offer-reject-form"
                    method="POST"
                    action="#"
                >
                    @csrf

                    <input
                        type="hidden"
                        id="procurement-offer-reject-offer-id"
                        name="reject_offer_id"
                        value=""
                    >

                    <div class="px-6 py-5">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-medium text-amber-900">
                                This action will reject this offer.
                            </p>
                            <p class="mt-1 text-sm text-amber-800">
                                The offer will no longer be available for acceptance.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="oy-btn oy-btn-secondary"
                            data-reject-modal-cancel
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="oy-btn oy-btn-primary"
                        >
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div
        id="procurement-offer-accept-modal"
        class="hidden fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="procurement-offer-accept-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="fixed inset-0 bg-slate-900/50"
                data-accept-modal-close
                aria-hidden="true"
            ></div>

            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <h2
                            id="procurement-offer-accept-title"
                            class="text-lg font-semibold text-slate-900"
                        >
                            Accept Offer
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Confirm that you want to accept this procurement offer.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="text-2xl leading-none text-slate-400 hover:text-slate-600"
                        data-accept-modal-close
                        aria-label="Close acceptance dialog"
                    >
                        &times;
                    </button>
                </div>

                <form
                    id="procurement-offer-accept-form"
                    method="POST"
                    action="#"
                >
                    @csrf

                    <input
                        type="hidden"
                        id="procurement-offer-accept-offer-id"
                        name="accept_offer_id"
                        value=""
                    >

                    <div class="px-6 py-5">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-medium text-amber-900">
                                This action will select this offer.
                            </p>

                            <p class="mt-1 text-sm text-amber-800">
                                Other submitted offers for this procurement
                                requirement will be rejected.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="oy-btn oy-btn-secondary"
                            data-accept-modal-cancel
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="oy-btn oy-btn-primary"
                        >
                            Confirm Acceptance
                        </button>
                    </div>
                </form>
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
                        value="{{ old('withdrawal_offer_id') }}"
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
                            class="oy-textarea mt-1"
                            placeholder="Why are you withdrawing this offer?"
                        >{{ old('withdrawal_reason') }}</textarea>

                        @error('withdrawal_reason')
                            <p class="oy-error">
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


    {{-- Procurement Reference Photo Modal --}}
    <div
        id="procurement-photo-modal"
        class="fixed inset-0 z-50 hidden"
        aria-hidden="true"
    >
        <div
            class="absolute inset-0 bg-slate-950/80"
            data-procurement-photo-close
        ></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
            <div
                class="relative w-full max-w-5xl rounded-2xl bg-white p-3 shadow-2xl sm:p-5"
                role="dialog"
                aria-modal="true"
                aria-labelledby="procurement-photo-modal-title"
            >
                <div class="mb-3 flex items-center justify-between gap-4">
                    <div>
                        <h2
                            id="procurement-photo-modal-title"
                            class="text-sm font-semibold text-slate-900"
                        >
                            Reference Photo
                        </h2>

                        <p
                            id="procurement-photo-modal-count"
                            class="mt-1 text-xs text-slate-500"
                        ></p>
                    </div>

                    <button
                        type="button"
                        class="oy-btn oy-btn-secondary"
                        data-procurement-photo-close
                        aria-label="Close photo viewer"
                    >
                        Close
                    </button>
                </div>

                <div class="overflow-hidden rounded-xl bg-slate-100">
                    <img
                        id="procurement-photo-modal-image"
                        src=""
                        alt=""
                        class="mx-auto max-h-[75vh] w-auto max-w-full object-contain"
                    >
                </div>

                <div
                    id="procurement-photo-modal-thumbnails"
                    class="mt-3 flex gap-2 overflow-x-auto"
                ></div>
            </div>
        </div>
    </div>

@endsection
