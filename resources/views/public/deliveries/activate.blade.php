@extends('layouts.public')

@php
    $title = 'Delivery Activation | Oneyard Outfitters';
    $description = 'Review your order balance and activate your delivery with Oneyard Outfitters.';
    $order = $recipient->delivery->order;
    $delivery = $recipient->delivery;
@endphp

@section('content')
<div class="bg-gradient-to-b from-white via-slate-50 to-slate-100">
    <div class="mx-auto w-full max-w-6xl space-y-6 px-4 py-8 sm:space-y-7 sm:px-6 sm:py-12 lg:px-8">

        {{-- Page heading --}}
        <header class="relative overflow-hidden rounded-2xl bg-slate-950 px-5 py-7 text-white shadow-lg sm:px-9 sm:py-9">
            <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 right-1/3 h-48 w-48 rounded-full bg-indigo-400/10 blur-3xl"></div>

            <div class="relative">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span>
                    Oneyard Outfitters · Delivery activation
                </div>

                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl lg:text-4xl">
                            Activate Your Delivery
                        </h1>
                        <p class="mt-2 text-sm leading-6 text-slate-300 sm:text-base">
                            Hello {{ $recipient->contact->first_name }}.
                            Review your order and confirm how the outstanding balance will be settled.
                        </p>
                    </div>

                    <div class="shrink-0">
                        @if($recipient->status === \App\Models\DeliveryActivationRecipient::STATUS_ACTIVATED)
                            <span class="inline-flex items-center rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1.5 text-xs font-semibold text-emerald-300">
                                Delivery activated
                            </span>
                        @elseif($isFullyPaid)
                            <span class="inline-flex items-center rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1.5 text-xs font-semibold text-emerald-300">
                                Payment complete
                            </span>
                        @elseif(
                            $delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
                            && $delivery->offline_payment_status === \App\Models\Delivery::OFFLINE_PAYMENT_STATUS_PENDING
                        )
                            <span class="inline-flex items-center rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1.5 text-xs font-semibold text-amber-300">
                                Payment under review
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full border border-blue-400/30 bg-blue-400/10 px-3 py-1.5 text-xs font-semibold text-blue-300">
                                Action required
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mt-7 grid gap-4 border-t border-white/10 pt-5 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Order number</p>
                        <p class="mt-1.5 break-words text-sm font-semibold text-white">
                            {{ $order->order_number }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Organization</p>
                        <p class="mt-1.5 break-words text-sm font-semibold text-white">
                            {{ $order->organization->name }}
                        </p>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-sm font-semibold text-emerald-900">Success</p>
                <p class="mt-1 text-sm leading-6 text-emerald-800">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-900">Something needs your attention</p>
                <p class="mt-1 text-sm leading-6 text-red-800">{{ session('error') }}</p>
            </div>
        @endif

        @if(session('info'))
            <div role="status" class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                <p class="text-sm font-semibold text-blue-900">Information</p>
                <p class="mt-1 text-sm leading-6 text-blue-800">{{ session('info') }}</p>
            </div>
        @endif

        @if($errors->has('payment_arrangement'))
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ $errors->first('payment_arrangement') }}
            </div>
        @endif

        {{-- Balance summary --}}
        <section class="oy-card overflow-hidden border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-0 md:grid-cols-2">
                <div class="p-5 sm:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-500">
                        Outstanding balance
                    </p>

                    <p class="mt-2 text-3xl font-bold tracking-tight tabular-nums sm:text-4xl {{ $isFullyPaid ? 'text-emerald-700' : 'text-slate-950' }}">
                        ₦{{ number_format($balanceDue, 2) }}
                    </p>

                    @if($isFullyPaid)
                        <p class="mt-2 text-sm leading-6 text-emerald-700">
                            Your completed payments cover the full order total. No additional payment is required.
                        </p>
                    @else
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Remaining amount after completed payments have been applied to your order.
                        </p>
                    @endif
                </div>

                <div class="border-t border-slate-100 bg-slate-50/70 p-5 sm:p-7 md:border-l md:border-t-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-500">
                        Delivery arrangement
                    </p>

                    <p class="mt-2 text-base font-semibold text-slate-900">
                        @if($isFullyPaid)
                            Fully paid order
                        @elseif($delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_NOW)
                            Online payment
                        @elseif($delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY)
                            Pay on delivery
                        @elseif($delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE)
                            Offline payment
                        @else
                            Not yet selected
                        @endif
                    </p>

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        @if($isFullyPaid)
                            Your payment is complete. Continue below to activate the delivery if it has not already been activated.
                        @elseif($delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE)
                            Offline payment claims require verification before the delivery can proceed.
                        @elseif($delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY)
                            The remaining balance is scheduled to be settled when the order is delivered.
                        @else
                            Choose the payment option that suits your order.
                        @endif
                    </p>
                </div>
            </div>
        </section>

        {{-- Activated state --}}
        @if($recipient->status === \App\Models\DeliveryActivationRecipient::STATUS_ACTIVATED)

            <section class="oy-card overflow-hidden border border-emerald-200 bg-white shadow-sm">
                <div class="border-b border-emerald-100 bg-emerald-50/70 px-5 py-5 sm:px-7">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-emerald-950">Delivery activated</h2>
                            <p class="mt-1 text-sm leading-6 text-emerald-800">
                                Your delivery activation has been completed successfully.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-7">
                    @if($isFullyPaid)
                        <p class="text-sm leading-6 text-slate-600">
                            Your order is fully paid. No further payment is required.
                        </p>
                    @elseif($delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_NOW)
                        <p class="text-sm leading-6 text-slate-600">
                            Your online payment arrangement has been selected. Check your payment confirmation for the latest status.
                        </p>
                    @else
                        <p class="text-sm leading-6 text-slate-600">
                            Your outstanding balance will be settled when the order is delivered.
                        </p>
                    @endif
                </div>
            </section>

        {{-- Fully paid, but not activated --}}
        @elseif($isFullyPaid)

            <section class="oy-card overflow-hidden border border-emerald-200 bg-white shadow-sm">
                <div class="border-b border-emerald-100 bg-emerald-50/70 px-5 py-5 sm:px-7">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.15em] text-emerald-700">
                                Payment complete
                            </p>
                            <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">
                                Your order is fully paid
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                There is nothing more to pay. Activate your delivery below to complete this step.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-7">
                    <div class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Ready to activate</p>
                            <p class="mt-1 text-sm leading-5 text-slate-600">
                                No payment arrangement is required for a fully paid order.
                            </p>
                        </div>
                        <p class="text-lg font-bold tabular-nums text-emerald-700">
                            ₦{{ number_format($balanceDue, 2) }} due
                        </p>
                    </div>

                    <form method="POST" action="{{ route('public.deliveries.activate.store', $recipient->access_token) }}" class="mt-6">
                        @csrf
                        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:w-auto">
                            Activate Delivery
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7 7 7-7 7"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </section>

        {{-- Offline claim pending --}}
        @elseif(
            $delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
            && $delivery->offline_payment_status === \App\Models\Delivery::OFFLINE_PAYMENT_STATUS_PENDING
        )

            <section class="oy-card overflow-hidden border border-amber-200 bg-white shadow-sm">
                <div class="border-b border-amber-100 bg-amber-50 px-5 py-5 sm:px-7">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.15em] text-amber-700">
                                Verification in progress
                            </p>
                            <h2 class="mt-1 text-xl font-bold tracking-tight text-amber-950">
                                Offline payment awaiting verification
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-amber-900">
                                Your offline payment claim has been submitted and is being reviewed by our Admin team.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-7">
                    <p class="text-sm leading-6 text-slate-600">
                        You do not need to submit another payment arrangement. Your delivery will remain pending until the claim is confirmed.
                    </p>
                </div>
            </section>

        {{-- Choose an arrangement --}}
        @else

            <section>
                <div class="mb-5">
                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-blue-700">
                        Next step
                    </p>
                    <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">
                        Choose your payment arrangement
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        Select one of the options below. If you have already paid outside this system, submit a claim for verification instead of paying again.
                    </p>
                </div>

                <div class="grid items-stretch gap-5 lg:grid-cols-3">

                    {{-- Pay online --}}
                    <form method="POST"
                          action="{{ route('public.deliveries.pay-now', $recipient->access_token) }}"
                          class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md sm:p-6">
                        @csrf

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18m-14 5h3"/>
                            </svg>
                        </div>

                        <h3 class="mt-4 text-base font-bold text-slate-950">Pay online</h3>
                        <p class="mt-2 flex-1 text-sm leading-6 text-slate-600">
                            Pay the outstanding balance securely through Paystack before delivery.
                        </p>

                        <div class="mt-5 rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount to pay</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-slate-950">
                                ₦{{ number_format($balanceDue, 2) }}
                            </p>
                        </div>

                        <button type="submit"
                                @disabled($balanceDue <= 0.01)
                                class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            Pay with Paystack
                        </button>
                    </form>

                    {{-- Pay on delivery --}}
                    <form method="POST"
                          action="{{ route('public.deliveries.activate.store', $recipient->access_token) }}"
                          class="flex h-full flex-col rounded-2xl border-2 border-emerald-200 bg-white p-5 shadow-sm transition hover:shadow-md sm:p-6">
                        @csrf
                        <input type="hidden" name="payment_arrangement" value="pay_on_delivery">

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/>
                                <circle cx="7.5" cy="18" r="1.5"/>
                                <circle cx="17.5" cy="18" r="1.5"/>
                            </svg>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-2">
                            <h3 class="text-base font-bold text-slate-950">Pay on delivery</h3>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                                Flexible
                            </span>
                        </div>

                        <p class="mt-2 flex-1 text-sm leading-6 text-slate-600">
                            Activate your delivery now and settle the outstanding balance when your order arrives.
                        </p>

                        <div class="mt-5 rounded-xl bg-emerald-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Due at delivery</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-emerald-900">
                                ₦{{ number_format($balanceDue, 2) }}
                            </p>
                        </div>

                        <button type="submit"
                                @disabled($balanceDue <= 0.01)
                                class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            Activate Delivery
                        </button>
                    </form>

                    {{-- Offline payment --}}
                    <form method="POST"
                          action="{{ route('public.deliveries.activate.store', $recipient->access_token) }}"
                          class="flex h-full flex-col rounded-2xl border border-amber-200 bg-white p-5 shadow-sm transition hover:shadow-md sm:p-6">
                        @csrf
                        <input type="hidden" name="payment_arrangement" value="paid_offline">

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m-5-14h7a3 3 0 0 1 0 6H9a3 3 0 0 0 0 6h8"/>
                            </svg>
                        </div>

                        <h3 class="mt-4 text-base font-bold text-slate-950">Already paid offline?</h3>
                        <p class="mt-2 flex-1 text-sm leading-6 text-slate-600">
                            If you have already paid outside this system, submit a claim for Admin verification. Do not use this option for an unpaid balance.
                        </p>

                        <div class="mt-5 rounded-xl bg-amber-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Claim amount</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-amber-900">
                                ₦{{ number_format($balanceDue, 2) }}
                            </p>
                        </div>

                        <button type="submit"
                                @disabled($balanceDue <= 0.01)
                                class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            Submit payment claim
                        </button>
                    </form>

                </div>

                <p class="mt-5 text-center text-xs leading-5 text-slate-500">
                    For help with your order or payment, contact your Oneyard Outfitters representative.
                </p>
            </section>

        @endif
    </div>
</div>
@endsection
