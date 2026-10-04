@extends('layouts.app')

@php
    use App\Models\Delivery;
@endphp

@section('content')
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-slate-500">
                    Delivery
                </p>
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ $delivery->order->order_number }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $delivery->order->organization->name }}
                </p>
            </div>

            <div>
                @if($delivery->status === \App\Models\Delivery::STATUS_CONFIRMED)
                    <span class="oy-badge oy-badge-success">
                        Delivered
                    </span>
                @else
                    <span class="oy-badge oy-badge-warning">
                        Pending Delivery
                    </span>
                @endif
            </div>
        </div>

        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">
                    Delivery Information
                </h2>
            </div>

            <div class="oy-card-body grid gap-6 sm:grid-cols-2">
                <div>
                    <div class="oy-meta">Order</div>
                    <div class="mt-1 text-sm font-medium text-slate-900">
                        {{ $delivery->order->order_number }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Organization</div>
                    <div class="mt-1 text-sm text-slate-900">
                        {{ $delivery->order->organization->name }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Planned Delivery Date</div>
                    <div class="mt-1 text-sm text-slate-900">
                        {{ $delivery->delivery_date?->format('d M Y') ?? '—' }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Created By</div>
                    <div class="mt-1 text-sm text-slate-900">
                        {{ $delivery->creator->name }}
                    </div>
                </div>

                @if($delivery->confirmed_at)
                    <div>
                        <div class="oy-meta">Confirmed At</div>
                        <div class="mt-1 text-sm text-slate-900">
                            {{ $delivery->confirmed_at->format('d M Y H:i') }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta">Confirmed By</div>
                        <div class="mt-1 text-sm text-slate-900">
                            {{ $delivery->confirmer?->name ?? '—' }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="oy-card oy-section">
            <div class="oy-card-body">
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">

                    <div>
                        <h2 class="font-semibold text-blue-900">
                            Delivery Activation
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-blue-800">
                            Select the active organization contacts who should
                            receive a secure delivery activation link.
                            The customer will choose between paying now and
                            paying on delivery from that secure page.
                        </p>
                    </div>

                    @php
                        $activationContacts = $delivery->order->organization->contacts
                            ->where('is_active', true)
                            ->filter(fn ($contact) => filled($contact->email));
                    @endphp

                    @if($delivery->status === \App\Models\Delivery::STATUS_CONFIRMED)

                        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <p class="text-sm leading-6 text-emerald-800">
                                This Delivery has been completed. Customer activation
                                links are no longer available.
                            </p>
                        </div>

                    @elseif($delivery->activated_at !== null)

                        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-emerald-900">
                                    Delivery Activated
                                </p>

                                <span class="oy-badge oy-badge-success">
                                    Activation Complete
                                </span>
                            </div>

                            <p class="mt-2 text-sm leading-6 text-emerald-800">
                                This Delivery has already been activated by the customer.
                                Additional activation links can no longer be sent.
                            </p>
                        </div>

                    @elseif($activationContacts->isNotEmpty())

                        <form
                            method="POST"
                            action="{{ route('deliveries.activation-invitations.store', $delivery) }}"
                            class="mt-5"
                        >
                            @csrf

                            <div class="space-y-3">

                                @foreach($activationContacts as $contact)

                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-blue-200 bg-white p-4 transition hover:border-blue-400">

                                        <input
                                            type="checkbox"
                                            name="contact_ids[]"
                                            value="{{ $contact->id }}"
                                            class="mt-1 rounded border-slate-300"
                                        >

                                        <span class="min-w-0">

                                            <span class="flex flex-wrap items-center gap-2 font-medium text-slate-900">
                                                {{ $contact->first_name }}
                                                {{ $contact->last_name }}

                                                @if($contact->is_primary)
                                                    <span class="oy-badge oy-badge-success">
                                                        Primary
                                                    </span>
                                                @endif
                                            </span>

                                            <span class="mt-1 block text-sm text-slate-600">
                                                {{ $contact->position ?: 'Contact' }}
                                                ·
                                                {{ $contact->email }}
                                            </span>

                                        </span>

                                    </label>

                                @endforeach

                            </div>

                            @error('contact_ids')
                                <p class="mt-3 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                            <div class="mt-5 flex justify-end">
                                <button
                                    type="submit"
                                    class="oy-btn oy-btn-primary disabled:cursor-not-allowed disabled:opacity-50"
                                    @disabled(
                                        $delivery->status === \App\Models\Delivery::STATUS_CONFIRMED
                                        || $delivery->activated_at !== null
                                    )
                                >
                                    @if($delivery->status === \App\Models\Delivery::STATUS_CONFIRMED)
                                        Delivery Completed
                                    @else
                                        Send Activation Link(s)
                                    @endif
                                </button>
                            </div>

                        </form>

                    @else

                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm leading-6 text-amber-800">
                                There are no active organization contacts with
                                an email address available for delivery activation.
                            </p>
                        </div>

                    @endif

                </div>
            </div>
        </div>

        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">
                    Delivery Notes
                </h2>
            </div>

            <div class="oy-card-body">
                <p class="whitespace-pre-line text-sm leading-6 text-slate-700">
                    {{ $delivery->notes ?: 'No delivery notes recorded.' }}
                </p>
            </div>
        </div>


        {{-- Offline payment claims require Admin/Super Admin review. --}}
        @if(
            $delivery->payment_arrangement === Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
            && $delivery->offline_payment_status === Delivery::OFFLINE_PAYMENT_STATUS_PENDING
        )
            @can('confirmOfflinePayment', $delivery)
                <div class="mb-6 rounded-xl border border-slate-700 bg-slate-950 p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-white">
                                Offline Payment Claim — Awaiting Review
                            </h3>
        
                            <p class="mt-1 text-sm leading-6 text-slate-200">
                                The customer has claimed that this order was already paid offline.
                                Review the claim before completing delivery.
                            </p>
        
                            @if($delivery->offline_payment_claimed_at)
                                <p class="mt-2 text-xs text-slate-300">
                                    Claim submitted
                                    {{ $delivery->offline_payment_claimed_at->format('d M Y, h:i A') }}
                                </p>
                            @endif
        
                            @if($delivery->offline_payment_review_notes)
                                <div class="mt-3 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-300">
                                        Claim Note
                                    </p>
        
                                    <p class="mt-1 whitespace-pre-line text-sm text-slate-200">
                                        {{ $delivery->offline_payment_review_notes }}
                                    </p>
                                </div>
                            @endif
                        </div>
        
                        <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                            <button
                                type="button"
                                onclick="document.getElementById('confirm-offline-payment-modal').classList.remove('hidden')"
                                class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                            >
                                Confirm Claim
                            </button>
        
                            <button
                                type="button"
                                onclick="document.getElementById('reject-offline-payment-modal').classList.remove('hidden')"
                                class="rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                            >
                                Reject Claim
                            </button>
                        </div>
                    </div>
                </div>
        
                <div
                    id="confirm-offline-payment-modal"
                    class="fixed inset-0 z-50 hidden overflow-y-auto"
                    aria-labelledby="confirm-offline-payment-title"
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        class="flex min-h-screen items-center justify-center bg-slate-900/50 px-4 py-8"
                        onclick="if (event.target === this) this.parentElement.classList.add('hidden')"
                    >
                        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2
                                        id="confirm-offline-payment-title"
                                        class="text-lg font-semibold text-slate-900"
                                    >
                                        Confirm Offline Payment
                                    </h2>
        
                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        Confirm that the customer has already paid the outstanding
                                        balance offline. This will complete the delivery and mark
                                        the order as delivered.
                                    </p>
                                </div>
        
                                <button
                                    type="button"
                                    onclick="document.getElementById('confirm-offline-payment-modal').classList.add('hidden')"
                                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                    aria-label="Close"
                                >
                                    &times;
                                </button>
                            </div>
        
                            <form
                                method="POST"
                                action="{{ route('deliveries.offline-payment.confirm', $delivery) }}"
                                class="mt-6"
                            >
                                @csrf
        
                                <label
                                    for="confirm_offline_payment_notes"
                                    class="block text-sm font-medium text-slate-200"
                                >
                                    Review Note
                                    <span class="font-normal text-slate-400">(optional)</span>
                                </label>
        
                                <textarea
                                    id="confirm_offline_payment_notes"
                                    name="notes"
                                    rows="4"
                                    maxlength="5000"
                                    class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    placeholder="Add a short confirmation note..."
                                ></textarea>
        
                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onclick="document.getElementById('confirm-offline-payment-modal').classList.add('hidden')"
                                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-50"
                                    >
                                        Cancel
                                    </button>
        
                                    <button
                                        type="submit"
                                        class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800"
                                    >
                                        Confirm Payment & Complete Delivery
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
        
                <div
                    id="reject-offline-payment-modal"
                    class="fixed inset-0 z-50 hidden overflow-y-auto"
                    aria-labelledby="reject-offline-payment-title"
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        class="flex min-h-screen items-center justify-center bg-slate-900/50 px-4 py-8"
                        onclick="if (event.target === this) this.parentElement.classList.add('hidden')"
                    >
                        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2
                                        id="reject-offline-payment-title"
                                        class="text-lg font-semibold text-slate-900"
                                    >
                                        Reject Offline Payment Claim
                                    </h2>
        
                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        Provide a reason for rejecting this claim. The delivery will
                                        remain pending.
                                    </p>
                                </div>
        
                                <button
                                    type="button"
                                    onclick="document.getElementById('reject-offline-payment-modal').classList.add('hidden')"
                                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                    aria-label="Close"
                                >
                                    &times;
                                </button>
                            </div>
        
                            <form
                                method="POST"
                                action="{{ route('deliveries.offline-payment.reject', $delivery) }}"
                                class="mt-6"
                            >
                                @csrf
        
                                <label
                                    for="reject_offline_payment_notes"
                                    class="block text-sm font-medium text-slate-200"
                                >
                                    Rejection Reason
                                    <span class="text-red-600">*</span>
                                </label>
        
                                <textarea
                                    id="reject_offline_payment_notes"
                                    name="notes"
                                    rows="4"
                                    maxlength="5000"
                                    required
                                    class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                                    placeholder="Explain why the offline payment claim is being rejected..."
                                ></textarea>
        
                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onclick="document.getElementById('reject-offline-payment-modal').classList.add('hidden')"
                                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-50"
                                    >
                                        Cancel
                                    </button>
        
                                    <button
                                        type="submit"
                                        class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700"
                                    >
                                        Reject Claim
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        @endif

        @if($delivery->activated_at)

            @if($delivery->status === \App\Models\Delivery::STATUS_CONFIRMED)

                <div class="oy-card">
                    <div class="oy-card-body">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-semibold text-emerald-900">
                                            Delivery Completed
                                        </h2>

                                        <span class="oy-badge oy-badge-success">
                                            Balance Collected
                                        </span>

                                        <span class="oy-badge oy-badge-success">
                                            Order Delivered
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm leading-6 text-emerald-800">
                                        The customer activated the Delivery, the outstanding
                                        balance was collected, and the Order has been completed.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            @can('confirm', $delivery)

                <div class="oy-card">
                    <div class="oy-card-header">
                        <h2 class="oy-card-title">
                            Complete Delivery
                        </h2>

                        <p class="oy-card-description">
                            Confirm that any outstanding balance has been settled
                            and the Order has physically been delivered.
                        </p>
                    </div>

                    <div class="oy-card-body">

                        <button
                            type="button"
                            id="open-delivery-completion-modal"
                            class="oy-btn oy-btn-primary"
                        >
                            Confirm Delivery & Complete Order
                        </button>

                    </div>
                </div>

                <div
                    id="delivery-completion-modal"
                    class="fixed inset-0 z-50 hidden"
                    aria-labelledby="delivery-completion-modal-title"
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        class="absolute inset-0 bg-slate-900/50"
                        data-close-delivery-modal
                    ></div>

                    <div class="relative flex min-h-full items-center justify-center p-4">
                        <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">

                            <div class="border-b border-slate-200 px-6 py-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h2
                                            id="delivery-completion-modal-title"
                                            class="text-lg font-semibold text-slate-900"
                                        >
                                            Confirm Delivery
                                        </h2>

                                        <p class="mt-1 text-sm text-slate-600">
                                            This action will complete the Delivery
                                            and close the Order.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        data-close-delivery-modal
                                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                        aria-label="Close"
                                    >
                                        &times;
                                    </button>
                                </div>
                            </div>

                            <form
                                method="POST"
                                action="{{ route('deliveries.confirm', $delivery) }}"
                            >
                                @csrf

                                <div class="space-y-5 px-6 py-6">

                                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                        <p class="text-sm leading-6 text-amber-800">
                                            Please confirm that any outstanding balance
                                            has been settled and the Order has been
                                            physically delivered.
                                        </p>
                                    </div>

                                    <label class="flex cursor-pointer items-start gap-3">
                                        <input
                                            type="checkbox"
                                            name="confirmation"
                                            value="1"
                                            required
                                            class="mt-1 rounded border-slate-300"
                                        >

                                        <span class="text-sm leading-6 text-slate-700">
                                            I confirm that any outstanding balance has
                                            been settled and the Order has been
                                            physically delivered to the customer.
                                        </span>
                                    </label>

                                    <div>
                                        <label
                                            for="delivery_completion_notes"
                                            class="oy-label"
                                        >
                                            Completion Note
                                        </label>

                                        <textarea
                                            id="delivery_completion_notes"
                                            name="notes"
                                            rows="4"
                                            maxlength="5000"
                                            class="mt-2 block w-full rounded-lg border border-slate-300 p-3 text-sm text-slate-700"
                                            placeholder="Record any relevant payment or delivery confirmation note."
                                        >{{ old('notes') }}</textarea>
                                    </div>

                                </div>

                                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 px-6 py-5 sm:flex-row sm:justify-end">

                                    <button
                                        type="button"
                                        data-close-delivery-modal
                                        class="oy-btn oy-btn-secondary"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="submit"
                                        class="oy-btn oy-btn-primary"
                                    >
                                        Confirm Delivery & Close Order
                                    </button>

                                </div>
                            </form>

                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const modal = document.getElementById(
                            'delivery-completion-modal'
                        );

                        const openButton = document.getElementById(
                            'open-delivery-completion-modal'
                        );

                        const closeButtons = document.querySelectorAll(
                            '[data-close-delivery-modal]'
                        );

                        if (!modal || !openButton) {
                            return;
                        }

                        openButton.addEventListener('click', function () {
                            modal.classList.remove('hidden');
                            document.body.classList.add('overflow-hidden');
                        });

                        closeButtons.forEach(function (button) {
                            button.addEventListener('click', function () {
                                modal.classList.add('hidden');
                                document.body.classList.remove('overflow-hidden');
                            });
                        });

                        document.addEventListener('keydown', function (event) {
                            if (
                                event.key === 'Escape'
                                && !modal.classList.contains('hidden')
                            ) {
                                modal.classList.add('hidden');
                                document.body.classList.remove('overflow-hidden');
                            }
                        });
                    });
                </script>

            @endcan

            @else

                <div class="oy-card">
                    <div class="oy-card-body">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-amber-900">
                                    Delivery Activated
                                </h2>

                                <span class="oy-badge oy-badge-warning">
                                    Awaiting Completion
                                </span>
                            </div>

                            <p class="mt-2 text-sm leading-6 text-amber-800">
                                The customer has activated the Delivery.
                                The outstanding balance and physical delivery
                                must now be completed by staff.
                            </p>

                            


@if($delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY)
                                <p class="mt-3 text-sm font-medium text-amber-900">
                                    Payment arrangement:
                                    Pay on Delivery
                                </p>
                            @elseif($delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_NOW)
                                <p class="mt-3 text-sm font-medium text-amber-900">
                                    Payment arrangement:
                                    Pay Now
                                </p>
                            @endif

                        </div>
                    </div>
                </div>

            @endif

        @else

            @can('confirm', $delivery)
                <div class="oy-card">
                    <div class="oy-card-body">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <h2 class="font-semibold text-slate-900">
                                Awaiting Customer Activation
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                The customer must activate the Delivery before
                                staff can confirm payment and complete the Order.
                            </p>
                        </div>
                    </div>
                </div>
            @endcan

        @endif

        <div>
            <a
                href="{{ route('orders.show', $delivery->order) }}"
                class="oy-btn oy-btn-secondary"
            >
                Back to Order
            </a>
        </div>
    </div>
@endsection
