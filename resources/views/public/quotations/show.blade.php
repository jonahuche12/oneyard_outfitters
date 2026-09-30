@extends('layouts.public.app')

@section('content') <div class="oy-page mx-auto w-full max-w-4xl space-y-6">


    {{-- Quotation Header --}}
    <div class="px-2 text-center sm:px-0">
        <p class="text-sm font-semibold tracking-wide text-slate-500">
            Oneyard Outfitters
        </p>

        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Quotation {{ $quotation->quotation_number }}
        </h1>

        <p class="mt-2 text-sm text-slate-600">
            Prepared for
            <span class="font-medium text-slate-900">
                {{ $quotation->organization->name }}
            </span>
        </p>
    </div>

    {{-- Quotation Information --}}
    <div class="oy-card">
        <div class="grid gap-5 sm:grid-cols-3">
            <div class="text-center sm:text-left">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Quotation Date
                </p>

                <p class="mt-1.5 text-sm font-medium text-slate-900">
                    {{ optional($quotation->quotation_date)->format('d M Y') }}
                </p>
            </div>

            <div class="text-center sm:text-left">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Valid Until
                </p>

                <p class="mt-1.5 text-sm font-medium text-slate-900">
                    {{ optional($quotation->valid_until)->format('d M Y') }}
                </p>
            </div>

            <div class="text-center sm:text-left">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Expected Delivery
                </p>

                <p class="mt-1.5 text-sm font-medium text-slate-900">
                    {{ $quotation->expected_delivery_days }} calendar days
                </p>
            </div>

            <div class="text-center sm:text-left">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Recipient
                </p>

                <p class="mt-1.5 text-sm font-medium text-slate-900">
                    {{ $recipient->contact->first_name }}
                    {{ $recipient->contact->last_name }}
                </p>
            </div>
        </div>
    </div>

    {{-- Quotation Items --}}
    <div class="oy-card overflow-hidden">
        <div class="oy-card-header px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">
                Quotation Items
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Review the products, quantities and pricing below.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Item
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Quantity
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Unit
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Unit Price
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Amount
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach ($quotation->items as $item)
                        <tr>
                            <td class="px-6 py-4 text-sm text-slate-900">
                                <div class="font-medium">
                                    {{ $item->item_name }}
                                </div>

                                @if ($item->description)
                                    <div class="mt-1 max-w-md text-xs leading-5 text-slate-500">
                                        {{ $item->description }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ number_format((float) $item->quantity, 2) }}
                            </td>

                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ $item->unit }}
                            </td>

                            <td class="px-6 py-4 text-right text-sm text-slate-700">
                                ₦{{ number_format((float) $item->unit_price, 2) }}
                            </td>

                            <td class="px-6 py-4 text-right text-sm font-semibold text-slate-900">
                                ₦{{ number_format((float) $item->line_total, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot class="bg-slate-50">
                    <tr>
                        <td colspan="4" class="px-6 py-3 text-right text-sm text-slate-600">
                            Subtotal
                        </td>

                        <td class="px-6 py-3 text-right text-sm font-medium text-slate-900">
                            ₦{{ number_format((float) $quotation->subtotal, 2) }}
                        </td>
                    </tr>

                    @if ((float) $quotation->discount > 0)
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right text-sm text-slate-600">
                                Discount
                            </td>

                            <td class="px-6 py-3 text-right text-sm text-slate-900">
                                -₦{{ number_format((float) $quotation->discount, 2) }}
                            </td>
                        </tr>
                    @endif

                    @if ((float) $quotation->additional_charges > 0)
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right text-sm text-slate-600">
                                Additional Charges
                            </td>

                            <td class="px-6 py-3 text-right text-sm text-slate-900">
                                ₦{{ number_format((float) $quotation->additional_charges, 2) }}
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td colspan="4" class="px-6 py-4 text-right text-base font-semibold text-slate-900">
                            Total
                        </td>

                        <td class="px-6 py-4 text-right text-base font-bold text-slate-900">
                            ₦{{ number_format((float) $quotation->total, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Response --}}
    <div class="oy-card">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">
                Your Response
            </h2>

            @if ($recipient->response_status === 'pending' && $quotation->status === \App\Models\Quotation::STATUS_SENT)

                <p class="mt-1.5 text-sm leading-6 text-slate-600">
                    Please review the quotation and choose whether you accept or reject it.
                </p>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <button
                        type="button"
                        onclick="document.getElementById('acceptQuotationModal').classList.remove('hidden')"
                        class="oy-btn oy-btn-primary w-full sm:w-auto"
                    >
                        Accept Quotation
                    </button>

                    <button
                        type="button"
                        onclick="document.getElementById('rejectQuotationModal').classList.remove('hidden')"
                        class="oy-btn oy-btn-secondary w-full sm:w-auto"
                    >
                        Reject Quotation
                    </button>
                </div>

                {{-- Accept Modal --}}
                <div
                    id="acceptQuotationModal"
                    class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/75 p-4 backdrop-blur-sm"
                >
                    <div class="mx-auto mt-10 w-full max-w-lg sm:mt-20">
                        <div class="oy-card border-0 p-6 shadow-2xl sm:p-8">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">
                                        Accept Quotation
                                    </h3>

                                    <p class="mt-1 text-sm leading-5 text-slate-600">
                                        Choose the percentage you want to pay as your initial payment.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onclick="document.getElementById('acceptQuotationModal').classList.add('hidden')"
                                    class="oy-btn oy-btn-ghost h-9 w-9 rounded-full p-0 text-xl"
                                    aria-label="Close"
                                >
                                    &times;
                                </button>
                            </div>

                            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                                <div class="flex items-center justify-between gap-4 text-sm">
                                    <span class="text-slate-600">
                                        Quotation Total
                                    </span>

                                    <strong class="text-base text-slate-900">
                                        ₦{{ number_format((float) $quotation->total, 2) }}
                                    </strong>
                                </div>
                            </div>

                            <form
                                method="POST"
                                action="{{ route('public.quotations.accept', $recipient->access_token) }}"
                                class="mt-6"
                            >
                                @csrf

                                <fieldset>
                                    <legend class="text-sm font-semibold text-slate-900">
                                        Select Payment Option
                                    </legend>

                                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        @foreach ([30, 60, 80] as $percentage)
                                            <label class="cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="payment_percentage"
                                                    value="{{ $percentage }}"
                                                    class="peer sr-only"
                                                    data-payment-percentage="{{ $percentage }}"
                                                    required
                                                >

                                                <span class="block rounded-xl border border-slate-300 bg-white p-4 text-center text-slate-900 shadow-sm transition hover:border-slate-400 peer-checked:border-[var(--oy-primary)] peer-checked:bg-[var(--oy-primary)] peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-[var(--oy-primary)]">
                                                    <span class="block text-xl font-bold tracking-tight">
                                                        {{ $percentage }}%
                                                    </span>

                                                    <span class="mt-2 block text-sm font-medium opacity-80">
                                                        ₦{{ number_format((float) $quotation->total * $percentage / 100, 2) }}
                                                    </span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>

                                <div class="mt-5 rounded-xl border border-[var(--oy-primary-border)] bg-[var(--oy-primary-soft)] p-5">
                                    <p class="text-sm font-medium text-slate-600">
                                        Selected Payment Amount
                                    </p>

                                    <p
                                        id="selectedPaymentAmount"
                                        class="mt-1.5 text-2xl font-bold tracking-tight text-slate-900"
                                    >
                                        Select a payment option
                                    </p>
                                </div>

                                <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                                    <button
                                        type="button"
                                        onclick="document.getElementById('acceptQuotationModal').classList.add('hidden')"
                                        class="oy-btn oy-btn-secondary w-full sm:w-auto"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="submit"
                                        class="oy-btn oy-btn-primary w-full sm:w-auto"
                                    >
                                        Confirm &amp; Continue
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Reject Modal --}}
                <div
                    id="rejectQuotationModal"
                    class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/75 p-4 backdrop-blur-sm"
                >
                    <div class="mx-auto mt-10 w-full max-w-lg sm:mt-20">
                        <div class="oy-card border-0 p-6 shadow-2xl sm:p-8">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">
                                        Reject Quotation
                                    </h3>

                                    <p class="mt-1 text-sm leading-5 text-slate-600">
                                        Please explain why you are rejecting this quotation.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onclick="document.getElementById('rejectQuotationModal').classList.add('hidden')"
                                    class="oy-btn oy-btn-ghost h-9 w-9 rounded-full p-0 text-xl"
                                    aria-label="Close"
                                >
                                    &times;
                                </button>
                            </div>

                            <form
                                method="POST"
                                action="{{ route('public.quotations.reject', $recipient->access_token) }}"
                                class="mt-6 space-y-5"
                            >
                                @csrf

                                <div>
                                    <label
                                        for="rejection_feedback"
                                        class="block text-sm font-semibold text-slate-700"
                                    >
                                        Feedback
                                    </label>

                                    <textarea
                                        id="rejection_feedback"
                                        name="rejection_feedback"
                                        rows="5"
                                        required
                                        minlength="5"
                                        maxlength="5000"
                                        class="oy-textarea mt-1.5 block w-full"
                                        placeholder="Please tell us what needs to be changed."
                                    >{{ old('rejection_feedback') }}</textarea>

                                    @error('rejection_feedback')
                                        <p class="mt-1.5 text-sm text-red-700">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                                    <button
                                        type="button"
                                        onclick="document.getElementById('rejectQuotationModal').classList.add('hidden')"
                                        class="oy-btn oy-btn-secondary w-full sm:w-auto"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="submit"
                                        class="oy-btn oy-btn-danger w-full sm:w-auto"
                                    >
                                        Confirm Rejection
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <script>
                    document.querySelectorAll('[data-payment-percentage]').forEach(function (radio) {
                        radio.addEventListener('change', function () {
                            const percentage = Number(this.dataset.paymentPercentage);
                            const total = {{ (float) $quotation->total }};
                            const amount = total * percentage / 100;

                            document.getElementById('selectedPaymentAmount').textContent =
                                '₦' + amount.toLocaleString('en-NG', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                        });
                    });
                </script>

            @elseif ($recipient->response_status === 'accepted')

                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
                    <p>
                        You accepted this quotation
                        @if ($recipient->responded_at)
                            on {{ $recipient->responded_at->format('d M Y H:i') }}.
                        @endif
                    </p>

                    @if ($recipient->payment_percentage !== null)
                        <div class="mt-3 space-y-1">
                            <p>
                                Payment selected:
                                <strong>{{ $recipient->payment_percentage }}%</strong>
                                —
                                <strong>
                                    ₦{{ number_format((float) $recipient->payment_amount, 2) }}
                                </strong>
                            </p>

                            <p>
                                Amount paid:
                                <strong>
                                    ₦{{ number_format((float) $recipient->amount_paid, 2) }}
                                </strong>
                            </p>
                        </div>
                    @endif
                </div>

            @elseif ($recipient->response_status === 'rejected')

                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-900">
                    <p>
                        You rejected this quotation
                        @if ($recipient->responded_at)
                            on {{ $recipient->responded_at->format('d M Y H:i') }}.
                        @endif
                    </p>

                    @if ($recipient->rejection_feedback)
                        <p class="mt-2">
                            <strong>Your feedback:</strong>
                            {{ $recipient->rejection_feedback }}
                        </p>
                    @endif
                </div>

            @elseif ($recipient->response_status === 'pending' && $quotation->status === \App\Models\Quotation::STATUS_ACCEPTED)

                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
                    This quotation has already been accepted and is no longer available for a new response.
                </div>

            @elseif ($recipient->response_status === 'pending' && $quotation->status === \App\Models\Quotation::STATUS_REJECTED)

                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-900">
                    This quotation has been rejected and is no longer available for a new response.
                </div>

            @else

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700">
                    This quotation is no longer available for response.
                </div>

            @endif
        </div>
    </div>

</div>


@endsection
