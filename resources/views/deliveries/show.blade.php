@extends('layouts.app')

@php
    use App\Models\Delivery;
@endphp

@section('content')
@php
    $deliveryOrder = $delivery->order;
    $organization = $deliveryOrder->organization;

    $activeContacts = $organization->contacts->filter(fn ($contact) => $contact->is_active);
    $primaryContact = $organization->contacts->firstWhere('is_primary', true);
    $otherContacts = $activeContacts->reject(fn ($contact) => $contact->is_primary);
    $activationContacts = $activeContacts->filter(fn ($contact) => filled($contact->email));

    $isConfirmed = $delivery->status === Delivery::STATUS_CONFIRMED;
@endphp

<div class="oy-page mx-auto max-w-6xl">

    {{-- Feedback --}}
    @foreach (['status', 'success'] as $flashKey)
        @if (session($flashKey))
            <div class="oy-alert oy-alert-success mb-5">
                {{ session($flashKey) }}
            </div>
        @endif
    @endforeach

    @if ($errors->any())
        <div class="oy-alert oy-alert-danger mb-5">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <a
        href="{{ route('deliveries.index') }}"
        class="mb-4 inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
    >
        ← Back to Deliveries
    </a>

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-meta">Delivery</div>

            <div class="oy-page-title-row">
                <h1 class="oy-page-title">{{ $deliveryOrder->order_number }}</h1>

                @if ($isConfirmed)
                    <span class="oy-badge oy-badge-success">
                        <span class="oy-status-dot"></span>
                        Delivered
                    </span>
                @else
                    <span class="oy-badge oy-badge-warning">
                        <span class="oy-status-dot"></span>
                        Pending Delivery
                    </span>
                @endif
            </div>

            <p class="oy-page-description">{{ $organization->name }}</p>
        </div>

        @can('view', $deliveryOrder)
            <div class="oy-page-actions">
                <a href="{{ route('orders.show', $deliveryOrder) }}" class="oy-btn oy-btn-secondary">
                    View Order
                </a>
            </div>
        @endcan
    </div>

    <div class="space-y-6">

        {{-- Delivery Information --}}
        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Delivery Information</h2>
            </div>

            <div class="oy-card-body">
                <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="oy-meta">Order</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ $deliveryOrder->order_number }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Organization</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $organization->name }}</dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Planned Delivery Date</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ $delivery->delivery_date?->format('d M Y') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Created By</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ $delivery->creator?->name ?? '—' }}
                        </dd>
                    </div>

                    @if ($delivery->confirmed_at)
                        <div>
                            <dt class="oy-meta">Confirmed At</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ $delivery->confirmed_at->format('d M Y H:i') }}
                            </dd>
                        </div>

                        <div>
                            <dt class="oy-meta">Confirmed By</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ $delivery->confirmer?->name ?? '—' }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Organization + Payment --}}
        <div class="grid gap-6 xl:grid-cols-2">

            {{-- Organization --}}
            <section class="oy-card">
                <div class="oy-card-header">
                    <h2 class="oy-card-title">Organization</h2>
                    <p class="oy-card-description">Customer information for this delivery</p>
                </div>

                <div class="oy-card-body space-y-6">

                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="oy-meta">Organization</div>
                        <div class="mt-1 text-base font-semibold text-slate-900">
                            {{ $organization->name }}
                        </div>

                        @if ($organization->organization_code)
                            <div class="oy-code mt-1">{{ $organization->organization_code }}</div>
                        @endif
                    </div>

                    <div>
                        <div class="oy-meta mb-2">Delivery Address</div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-sm leading-6 text-slate-700">
                                {{ $organization->address ?: 'Address not provided' }}

                                @if ($organization->city)
                                    <br>{{ $organization->city }}
                                @endif

                                @if ($organization->state)
                                    , {{ $organization->state }}
                                @endif

                                @if ($organization->country)
                                    <br>{{ $organization->country }}
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($organization->phone)
                        <div class="rounded-lg border border-slate-200 px-4 py-3">
                            <div class="oy-meta">Organization Phone</div>
                            <div class="mt-1 text-sm font-medium text-slate-800">
                                <a href="tel:{{ $organization->phone }}" class="hover:underline">
                                    {{ $organization->phone }}
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Primary contact --}}
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <div class="oy-meta">Primary Contact</div>
                            <span class="oy-badge oy-badge-info">Primary</span>
                        </div>

                        @if ($primaryContact)
                            <div class="rounded-lg border border-sky-100 bg-sky-50/50 p-4">
                                <div class="flex items-start gap-3">
                                    <span class="oy-avatar oy-avatar-round">
                                        {{ strtoupper(substr($primaryContact->first_name, 0, 1)) }}
                                    </span>

                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-900">
                                            {{ trim($primaryContact->first_name . ' ' . $primaryContact->last_name) }}
                                        </div>

                                        @if ($primaryContact->position)
                                            <div class="mt-0.5 text-sm text-slate-600">
                                                {{ $primaryContact->position }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <dl class="mt-4 grid gap-2 border-t border-sky-100 pt-3 text-sm">
                                    @if ($primaryContact->phone)
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-slate-500">Phone</dt>
                                            <dd class="font-medium text-slate-800">{{ $primaryContact->phone }}</dd>
                                        </div>
                                    @endif

                                    @if ($primaryContact->email)
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-slate-500">Email</dt>
                                            <dd class="break-all text-right font-medium text-slate-800">
                                                {{ $primaryContact->email }}
                                            </dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @else
                            <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                                No primary contact recorded.
                            </div>
                        @endif
                    </div>

                    {{-- Other contacts --}}
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <div class="oy-meta">Other Active Contacts</div>
                            <span class="oy-badge oy-badge-neutral">{{ $otherContacts->count() }}</span>
                        </div>

                        <div class="space-y-3">
                            @forelse ($otherContacts as $contact)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="font-medium text-slate-900">
                                        {{ trim($contact->first_name . ' ' . $contact->last_name) }}
                                    </div>

                                    @if ($contact->position)
                                        <div class="mt-0.5 text-sm text-slate-500">{{ $contact->position }}</div>
                                    @endif

                                    <div class="mt-3 space-y-1 text-sm text-slate-600">
                                        @if ($contact->phone)
                                            <div>{{ $contact->phone }}</div>
                                        @endif

                                        @if ($contact->email)
                                            <div class="break-all">{{ $contact->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                                    No other active contacts recorded.
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </section>

            {{-- Payment Summary --}}
            <section class="oy-card">
                <div class="oy-card-header flex items-start justify-between gap-4">
                    <div>
                        <h2 class="oy-card-title">Payment Summary</h2>
                        <p class="oy-card-description">Payment position for this Order</p>
                    </div>

                    @if ($balanceDue > 0)
                        <span class="oy-badge oy-badge-warning">Balance Due</span>
                    @else
                        <span class="oy-badge oy-badge-success">Fully Paid</span>
                    @endif
                </div>

                <div class="oy-card-body space-y-6">

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <div class="oy-meta">Order Total</div>
                            <div class="mt-2 text-xl font-bold tracking-tight text-slate-900">
                                ₦{{ number_format($orderTotal, 2) }}
                            </div>
                        </div>

                        <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-5">
                            <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                                Amount Paid
                            </div>
                            <div class="mt-2 text-xl font-bold tracking-tight text-emerald-800">
                                ₦{{ number_format($amountPaid, 2) }}
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-5">
                        <div class="flex items-start justify-between gap-6">
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wide text-amber-700">
                                    Outstanding Balance
                                </div>
                                <p class="mt-1 text-sm leading-5 text-amber-800">
                                    Amount remaining against the Order.
                                </p>
                            </div>

                            <div class="text-right text-2xl font-bold tracking-tight text-amber-950 sm:text-3xl">
                                ₦{{ number_format($balanceDue, 2) }}
                            </div>
                        </div>
                    </div>

                    @if ($delivery->payment_arrangement === Delivery::PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY)
                        <div class="rounded-lg border-2 border-sky-100 bg-sky-50 p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-sm font-bold uppercase tracking-wide text-sky-800">
                                    Pay on Delivery
                                </h3>

                                <span class="oy-badge oy-badge-info">Collection Required</span>
                            </div>

                            <div class="mt-3 text-3xl font-bold tracking-tight text-sky-950">
                                ₦{{ number_format($amountToCollect, 2) }}
                            </div>

                            <p class="mt-2 max-w-md text-sm leading-5 text-sky-800">
                                This is the amount the Delivery staff should collect from the customer
                                before completing the delivery.
                            </p>
                        </div>
                    @endif

                    <div class="rounded-lg border border-slate-200 px-4 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <span class="oy-meta">Payment Arrangement</span>

                            @switch($delivery->payment_arrangement)
                                @case(Delivery::PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY)
                                    <span class="oy-badge oy-badge-info">Pay on Delivery</span>
                                    @break

                                @case(Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE)
                                    <span class="oy-badge oy-badge-neutral">Paid Offline</span>
                                    @break

                                @case(Delivery::PAYMENT_ARRANGEMENT_PAY_NOW)
                                    <span class="oy-badge oy-badge-success">Pay Now</span>
                                    @break

                                @default
                                    <span class="oy-badge oy-badge-neutral">Not Selected</span>
                            @endswitch
                        </div>

                        @if (
                            $delivery->payment_arrangement === Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
                            && filled($delivery->offline_payment_status)
                        )
                            <div class="mt-3 flex items-center justify-between gap-4 border-t border-slate-100 pt-3">
                                <span class="oy-meta">Offline Claim</span>
                                <span class="text-sm font-medium text-slate-800">
                                    {{ ucwords(str_replace('_', ' ', $delivery->offline_payment_status)) }}
                                </span>
                            </div>
                        @endif
                    </div>

                </div>
            </section>

        </div>

        {{-- Order Breakdown --}}
        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Order Breakdown</h2>
                <p class="oy-card-description">Items being delivered under this Order.</p>
            </div>

            <div class="oy-table-scroll">
                <table class="oy-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th class="text-right">Line Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($deliveryOrder->items as $item)
                            <tr>
                                <td class="oy-table-primary">{{ $item->item_name }}</td>
                                <td>{{ $item->description ?: '—' }}</td>
                                <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                <td>{{ $item->unit ?: '—' }}</td>
                                <td class="text-right font-medium text-slate-900">
                                    ₦{{ number_format((float) $item->line_total, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-slate-500">
                                    No Order items recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right font-semibold text-slate-700">Order Total</td>
                            <td class="text-right font-bold text-slate-900">
                                ₦{{ number_format($orderTotal, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Delivery Activation --}}
        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Delivery Activation</h2>
                <p class="oy-card-description">
                    Select the active organization contacts who should
                    receive a secure delivery activation link.
                    The customer will choose between paying now and
                    paying on delivery from that secure page.
                </p>
            </div>

            <div class="oy-card-body">

                @if ($isConfirmed)

                    <div class="oy-alert oy-alert-success">
                        This Delivery has been completed. Customer activation
                        links are no longer available.
                    </div>

                @elseif ($delivery->activated_at !== null)

                    <div class="oy-alert oy-alert-success">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold">Delivery Activated</span>
                            <span class="oy-badge oy-badge-success">Activation Complete</span>
                        </div>

                        <p class="mt-2">
                            This Delivery has already been activated by the customer.
                            Additional activation links can no longer be sent.
                        </p>
                    </div>

                @elseif ($activationContacts->isNotEmpty())

                    <form
                        method="POST"
                        action="{{ route('deliveries.activation-invitations.store', $delivery) }}"
                        class="oy-form"
                    >
                        @csrf

                        <div class="space-y-3">
                            @foreach ($activationContacts as $contact)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 transition hover:bg-slate-50">
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

                                            @if ($contact->is_primary)
                                                <span class="oy-badge oy-badge-success">Primary</span>
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
                            <p class="oy-error mt-3">{{ $message }}</p>
                        @enderror

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="oy-btn oy-btn-primary">
                                Send Activation Link(s)
                            </button>
                        </div>
                    </form>

                @else

                    <div class="oy-alert oy-alert-warning">
                        There are no active organization contacts with
                        an email address available for delivery activation.
                    </div>

                @endif

            </div>
        </div>

        {{-- Offline payment claims require Admin/Super Admin review. --}}
        @if (
            $delivery->payment_arrangement === Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
            && $delivery->offline_payment_status === Delivery::OFFLINE_PAYMENT_STATUS_PENDING
        )
            @can('confirmOfflinePayment', $delivery)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-amber-900">
                                Offline Payment Claim — Awaiting Review
                            </h3>

                            <p class="mt-1 text-sm leading-6 text-amber-800">
                                The customer has claimed that this order was already paid offline.
                                Review the claim before completing delivery.
                            </p>

                            @if ($delivery->offline_payment_claimed_at)
                                <p class="mt-2 text-xs text-amber-700">
                                    Claim submitted
                                    {{ $delivery->offline_payment_claimed_at->format('d M Y, h:i A') }}
                                </p>
                            @endif

                            @if ($delivery->offline_payment_review_notes)
                                <div class="mt-3 rounded-lg border border-amber-200 bg-white px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Claim Note
                                    </p>

                                    <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                        {{ $delivery->offline_payment_review_notes }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                            <button
                                type="button"
                                onclick="document.getElementById('confirm-offline-payment-modal').classList.remove('hidden')"
                                class="oy-btn oy-btn-primary"
                            >
                                Confirm Claim
                            </button>

                            <button
                                type="button"
                                onclick="document.getElementById('reject-offline-payment-modal').classList.remove('hidden')"
                                class="oy-btn oy-btn-danger"
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
                                class="oy-form mt-6"
                            >
                                @csrf

                                <label for="confirm_offline_payment_notes" class="oy-form-label">
                                    Review Note
                                    <span class="font-normal text-slate-400">(optional)</span>
                                </label>

                                <textarea
                                    id="confirm_offline_payment_notes"
                                    name="notes"
                                    rows="4"
                                    maxlength="5000"
                                    class="oy-textarea"
                                    placeholder="Add a short confirmation note..."
                                ></textarea>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onclick="document.getElementById('confirm-offline-payment-modal').classList.add('hidden')"
                                        class="oy-btn oy-btn-secondary"
                                    >
                                        Cancel
                                    </button>

                                    <button type="submit" class="oy-btn oy-btn-primary">
                                        Confirm Payment &amp; Complete Delivery
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
                                class="oy-form mt-6"
                            >
                                @csrf

                                <label for="reject_offline_payment_notes" class="oy-form-label">
                                    Rejection Reason
                                    <span class="oy-form-required">*</span>
                                </label>

                                <textarea
                                    id="reject_offline_payment_notes"
                                    name="notes"
                                    rows="4"
                                    maxlength="5000"
                                    required
                                    class="oy-textarea"
                                    placeholder="Explain why the offline payment claim is being rejected..."
                                ></textarea>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onclick="document.getElementById('reject-offline-payment-modal').classList.add('hidden')"
                                        class="oy-btn oy-btn-secondary"
                                    >
                                        Cancel
                                    </button>

                                    <button type="submit" class="oy-btn oy-btn-danger">
                                        Reject Claim
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        @endif

        {{-- Completion --}}
        @if ($isConfirmed)

            <div class="oy-card">
                <div class="oy-card-body">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-emerald-900">Delivery Completed</h2>

                            <span class="oy-badge oy-badge-success">Balance Collected</span>
                            <span class="oy-badge oy-badge-success">Order Delivered</span>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-emerald-800">
                            The customer activated the Delivery, the outstanding
                            balance was collected, and the Order has been completed.
                        </p>
                    </div>
                </div>
            </div>

        @elseif ($delivery->activated_at)

            @can('confirm', $delivery)

                <div class="oy-card">
                    <div class="oy-card-header">
                        <h2 class="oy-card-title">Complete Delivery</h2>
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
                            Confirm Delivery &amp; Complete Order
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
                    <div class="absolute inset-0 bg-slate-900/50" data-close-delivery-modal></div>

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
                                class="oy-form"
                            >
                                @csrf

                                <div class="space-y-5 px-6 py-6">

                                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                                        Please confirm that any outstanding balance
                                        has been settled and the Order has been
                                        physically delivered.
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
                                        <label for="delivery_completion_notes" class="oy-form-label">
                                            Completion Note
                                        </label>

                                        <textarea
                                            id="delivery_completion_notes"
                                            name="notes"
                                            rows="4"
                                            maxlength="5000"
                                            class="oy-textarea"
                                            placeholder="Record any relevant payment or delivery confirmation note."
                                        >{{ old('notes') }}</textarea>
                                    </div>

                                </div>

                                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 px-6 py-5 sm:flex-row sm:justify-end">
                                    <button type="button" data-close-delivery-modal class="oy-btn oy-btn-secondary">
                                        Cancel
                                    </button>

                                    <button type="submit" class="oy-btn oy-btn-primary">
                                        Confirm Delivery &amp; Close Order
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const modal = document.getElementById('delivery-completion-modal');
                        const openButton = document.getElementById('open-delivery-completion-modal');
                        const closeButtons = document.querySelectorAll('[data-close-delivery-modal]');

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
                            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                                modal.classList.add('hidden');
                                document.body.classList.remove('overflow-hidden');
                            }
                        });
                    });
                </script>

            @else

                <div class="oy-card">
                    <div class="oy-card-body">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-amber-900">Delivery Activated</h2>
                                <span class="oy-badge oy-badge-warning">Awaiting Completion</span>
                            </div>

                            <p class="mt-2 text-sm leading-6 text-amber-800">
                                The customer has activated the Delivery.
                                The outstanding balance and physical delivery
                                must now be completed by staff.
                            </p>

                            @if ($delivery->payment_arrangement === Delivery::PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY)
                                <p class="mt-3 text-sm font-medium text-amber-900">
                                    Payment arrangement: Pay on Delivery
                                </p>
                            @elseif ($delivery->payment_arrangement === Delivery::PAYMENT_ARRANGEMENT_PAY_NOW)
                                <p class="mt-3 text-sm font-medium text-amber-900">
                                    Payment arrangement: Pay Now
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

            @endcan

        @else

            @can('confirm', $delivery)
                <div class="oy-card">
                    <div class="oy-card-body">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <h2 class="font-semibold text-slate-900">Awaiting Customer Activation</h2>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                The customer must activate the Delivery before
                                staff can confirm payment and complete the Order.
                            </p>
                        </div>
                    </div>
                </div>
            @endcan

        @endif

        {{-- Delivery Notes --}}
        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Delivery Notes</h2>
            </div>

            <div class="oy-card-body">
                <p class="whitespace-pre-line text-sm leading-6 text-slate-700">
                    {{ $delivery->notes ?: 'No delivery notes recorded.' }}
                </p>
            </div>
        </div>

    </div>
</div>
@endsection
