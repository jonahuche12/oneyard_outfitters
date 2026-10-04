<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Delivery Activation</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">

    <main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">

        <div class="mb-8 text-center">
            <p class="text-sm font-semibold uppercase tracking-wider text-emerald-700">
                {{ config('app.name') }}
            </p>

            <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">
                Activate Your Delivery
            </h1>

            <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-600">
                Hello {{ $recipient->contact->first_name }}.
                Your order has reached the delivery stage. Please select
                how you would like to settle the outstanding balance.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if(session('info'))
            <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                {{ session('info') }}
            </div>
        @endif

        <div class="space-y-6">

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">
                    Order Information
                </h2>

                <dl class="mt-5 grid gap-5 sm:grid-cols-2">

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Organization
                        </dt>

                        <dd class="mt-1 font-medium text-slate-900">
                            {{ $recipient->delivery->order->organization->name }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Order
                        </dt>

                        <dd class="mt-1 font-medium text-slate-900">
                            {{ $recipient->delivery->order->order_number }}
                        </dd>
                    </div>

                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Outstanding Balance
                        </p>

                        <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">
                            ₦{{ number_format($balanceDue, 2) }}
                        </p>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            This is the remaining balance on your order after
                            all completed payments have been applied.
                        </p>
                    </div>
                </div>
            </section>

            @if($recipient->status === \App\Models\DeliveryActivationRecipient::STATUS_ACTIVATED)

                <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">
                            ✓
                        </div>

                        <div>
                            <h2 class="font-semibold text-emerald-900">
                                Delivery Activated
                            </h2>

                            @if($recipient->delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_NOW)
                                <p class="mt-2 text-sm leading-6 text-emerald-800">
                                    Your delivery has been activated and your
                                    online payment is being processed through Paystack.
                                </p>
                            @else
                                <p class="mt-2 text-sm leading-6 text-emerald-800">
                                    Your delivery has been activated successfully.
                                    The outstanding balance will be settled when
                                    the order is delivered.
                                </p>
                            @endif
                        </div>
                    </div>
                </section>

            @elseif(
                $recipient->delivery->payment_arrangement === \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
                && $recipient->delivery->offline_payment_status === \App\Models\Delivery::OFFLINE_PAYMENT_STATUS_PENDING
            )

                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-500 text-sm font-bold text-white">
                            !
                        </div>

                        <div>
                            <h2 class="font-semibold text-amber-900">
                                Offline Payment Awaiting Verification
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-amber-800">
                                Your offline payment claim has been submitted successfully
                                and is currently being reviewed by our Admin team.
                                Your delivery will remain pending until the claim is confirmed.
                            </p>

                            <p class="mt-3 text-sm font-medium leading-6 text-amber-900">
                                You do not need to submit another payment arrangement.
                                Please wait for the verification to be completed.
                            </p>
                        </div>
                    </div>
                </section>

            @else

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">
                            Choose Your Payment Arrangement
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Select the option that works best for you.
                            Your delivery can proceed once your selection is confirmed.
                        </p>
                    </div>

                    <div class="mt-6 grid gap-5 md:grid-cols-3">

                        <form
                            method="POST"
                            action="{{ route('public.deliveries.pay-now', $recipient->access_token) }}"
                            class="flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            @csrf

                            <h3 class="text-base font-semibold text-slate-900">
                                Complete Payment Now
                            </h3>

                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-600">
                                Complete your outstanding balance online before delivery.
                            </p>

                            <div class="mt-4 rounded-lg bg-slate-100 px-4 py-3">
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                    Amount to Pay
                                </p>

                                <p class="mt-1 text-xl font-bold text-slate-900">
                                    ₦{{ number_format($balanceDue, 2) }}
                                </p>
                            </div>

                            <button
                                type="submit"
                                @disabled($balanceDue <= 0.01)
                                class="mt-6 w-full rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Pay ₦{{ number_format($balanceDue, 2) }} with Paystack
                            </button>

                        </form>

                        <form
                            method="POST"
                            action="{{ route('public.deliveries.activate.store', $recipient->access_token) }}"
                            class="flex flex-col rounded-xl border-2 border-emerald-200 bg-emerald-50/50 p-5 shadow-sm"
                        >
                            @csrf

                            <input
                                type="hidden"
                                name="payment_arrangement"
                                value="pay_on_delivery"
                            >

                            <h3 class="text-base font-semibold text-slate-900">
                                Pay on Delivery
                            </h3>

                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-600">
                                Activate your delivery now and settle the outstanding
                                balance when your order is delivered.
                            </p>

                            <div class="mt-4 rounded-lg bg-emerald-100 px-4 py-3">
                                <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">
                                    Amount Due at Delivery
                                </p>

                                <p class="mt-1 text-xl font-bold text-emerald-900">
                                    ₦{{ number_format($balanceDue, 2) }}
                                </p>
                            </div>

                            <button
                                type="submit"
                                @disabled($balanceDue <= 0.01)
                                class="mt-6 w-full rounded-lg bg-emerald-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Activate Delivery
                            </button>

                        </form>

                        <form
                            method="POST"
                            action="{{ route('public.deliveries.activate.store', $recipient->access_token) }}"
                            class="flex flex-col rounded-xl border-2 border-amber-200 bg-amber-50/50 p-5 shadow-sm"
                        >
                            @csrf

                            <input
                                type="hidden"
                                name="payment_arrangement"
                                value="paid_offline"
                            >

                            <h3 class="text-base font-semibold text-slate-900">
                                Already Paid Offline
                            </h3>

                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-600">
                                If you have already paid the outstanding balance
                                outside this system, submit your claim for
                                Admin verification.
                            </p>

                            <div class="mt-4 rounded-lg bg-amber-100 px-4 py-3">
                                <p class="text-xs font-medium uppercase tracking-wide text-amber-700">
                                    Amount Claimed as Paid
                                </p>

                                <p class="mt-1 text-xl font-bold text-amber-900">
                                    ₦{{ number_format($balanceDue, 2) }}
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-slate-950 px-5 py-4 text-sm font-bold text-white shadow-lg ring-1 ring-slate-800 transition duration-200 hover:bg-slate-900 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-slate-950 focus:ring-offset-2"
                            >
                                Submit Offline Payment Claim
                            </button>

                        </form>

                    </div>

                    @error('payment_arrangement')
                        <p class="mt-4 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </section>

            @endif

        </div>
    </main>
</body>
</html>
