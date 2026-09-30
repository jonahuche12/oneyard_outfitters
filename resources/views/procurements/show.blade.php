@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-title-row">
            <div>
                <h1 class="oy-page-title">
                    Procurement Requirement
                </h1>

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

    @if(session('success'))
        <div class="oy-alert oy-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="oy-alert oy-alert-error">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
                        <div class="oy-meta-label">Item</div>
                        <div class="oy-meta-value">
                            {{ $procurement->item_name }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Quantity</div>
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
                        <div class="oy-meta-label">Commission per Unit</div>
                        <div class="oy-meta-value">
                            ₦{{ number_format((float) $procurement->commission_per_unit, 2) }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Required By</div>
                        <div class="oy-meta-value">
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
                        <div class="oy-meta-label">Status</div>
                        <div class="oy-meta-value">
                            {{ ucwords(str_replace('_', ' ', $procurement->status)) }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Priority</div>
                        <div class="oy-meta-value">
                            {{ ucfirst($procurement->priority) }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Offer Deadline</div>
                        <div class="oy-meta-value">
                            {{ $procurement->offer_deadline?->format('d M Y H:i') ?? 'Not specified' }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Offers</div>
                        <div class="oy-meta-value">
                            {{ $procurement->offers->count() }}
                            {{ \Illuminate\Support\Str::plural('offer', $procurement->offers->count()) }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Created By</div>
                        <div class="oy-meta-value">
                            {{ $procurement->createdBy?->name ?? 'Unknown' }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta-label">Last Updated By</div>
                        <div class="oy-meta-value">
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
                <div class="oy-meta">
                    <div>
                        <div class="oy-meta-label">Order Number</div>
                        <div class="oy-meta-value">
                            {{ $procurement->order->order_number }}
                        </div>
                    </div>

                    @if($procurement->order->organization)
                        <div>
                            <div class="oy-meta-label">Organization</div>
                            <div class="oy-meta-value">
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
                        <div class="oy-meta-label">Description</div>
                        <div class="mt-1 whitespace-pre-line">
                            {{ $procurement->description }}
                        </div>
                    </div>
                @endif

                @if($procurement->notes)
                    <div>
                        <div class="oy-meta-label">Notes</div>
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
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                        {{ ucwords(str_replace('_', ' ', $offer->status)) }}
                                    </span>

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
                                    <div class="oy-meta-label">Quantity</div>
                                    <div class="oy-meta-value">
                                        {{ number_format((float) $offer->quantity, 2) }}
                                        {{ $procurement->unit }}
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
                            </div>

                            @if($offer->notes)
                                <div class="mt-4 border-t border-slate-100 pt-4">
                                    <div class="oy-meta-label">Notes</div>

                                    <div class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                        {{ $offer->notes }}
                                    </div>
                                </div>
                            @endif

                            @if($offer->withdrawal_reason)
                                <div class="mt-4 border-t border-slate-100 pt-4">
                                    <div class="oy-meta-label">Withdrawal Reason</div>

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

    <div class="oy-card mt-6">
        <div class="oy-card-header">
            <h2 class="oy-card-title">
                Reference Photos
            </h2>

            <p class="oy-card-description">
                Photos attached to help staff understand the procurement requirement.
            </p>
        </div>

        <div class="oy-card-body">
            @php
                $photoMimeTypes = [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/webp',
                ];

                $photos = $procurement->attachments
                    ->filter(function ($attachment) use ($photoMimeTypes) {
                        return $attachment->product_specification_artifact_id === null
                            && in_array($attachment->mime_type, $photoMimeTypes, true);
                    })
                    ->values();
            @endphp

            @if($photos->isEmpty())
                <p class="text-sm text-slate-500">
                    No reference photos have been uploaded.
                </p>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach($photos as $photo)
                        <a
                            href="{{ route('procurements.attachments.show', [$procurement, $photo]) }}"
                            target="_blank"
                            rel="noopener"
                            class="group block overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
                        >
                            <div class="aspect-square bg-slate-100">
                                <img
                                    src="{{ route('procurements.attachments.show', [$procurement, $photo]) }}"
                                    alt="{{ $photo->original_name ?: 'Procurement reference photo' }}"
                                    class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                >
                            </div>

                            <div class="truncate px-3 py-2 text-xs text-slate-600">
                                {{ $photo->original_name ?: 'Reference photo' }}
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
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
