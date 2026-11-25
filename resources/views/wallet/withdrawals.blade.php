@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Page hero --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
            <a href="{{ route('wallet.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-white/60 sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
                Back to My Wallet
            </a>

            <div class="mt-3 flex items-center gap-3 sm:mt-4 sm:gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-white/15 bg-white/10 text-cyan-300">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                </span>
                <div class="min-w-0">
                    <h1 class="break-words text-2xl font-bold tracking-tight sm:text-3xl">Withdrawals</h1>
                    <p class="mt-1 text-xs text-slate-400 sm:text-sm">Request a transfer of your earnings to your payout account.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Feedback --}}
    @if (session('success'))
        <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5L16 9.5"/></svg>
            <span class="min-w-0 break-words">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid gap-5 sm:gap-6 lg:grid-cols-2 lg:items-start">

        {{-- Balances --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-indigo-50 via-white to-emerald-50 px-4 py-4 sm:px-6">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-slate-900 sm:text-lg">Balances</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">What you hold and what you can withdraw.</p>
                </div>
            </div>

            @php $ledgerNegative = (float) $ledger < 0; @endphp
            <div class="grid gap-3 p-4 sm:gap-4 sm:p-6">
                <div class="rounded-xl border {{ $ledgerNegative ? 'border-rose-100 from-rose-50' : 'border-indigo-100 from-indigo-50' }} bg-gradient-to-br to-white p-3 shadow-sm sm:p-4">
                    <p class="flex items-center gap-1.5 text-xs font-bold {{ $ledgerNegative ? 'text-rose-700' : 'text-indigo-700' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                        Wallet balance
                    </p>
                    <div class="mt-2 text-xl font-bold tabular-nums tracking-tight {{ $ledgerNegative ? 'text-rose-950' : 'text-indigo-950' }} sm:text-2xl">{{ (float) $ledger < 0 ? '-' : '' }}₦{{ number_format(abs((float) $ledger), 2) }}</div>
                </div>

                <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-3 shadow-sm sm:p-4">
                    <p class="flex items-center gap-1.5 text-xs font-bold text-emerald-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                        Available to withdraw
                    </p>
                    <div class="mt-2 text-xl font-bold tabular-nums tracking-tight text-emerald-950 sm:text-2xl" data-available-balance>₦{{ number_format(max(0, (float) $available), 2) }}</div>
                </div>

                <p class="flex items-start gap-2 text-xs text-slate-500">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                    <span>Available excludes any request still being processed.</span>
                </p>
            </div>
        </section>

        {{-- Request a withdrawal --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-emerald-50 via-white to-blue-50 px-4 py-4 sm:px-6">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-slate-900 sm:text-lg">Request a withdrawal</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Enter an amount to withdraw from your available balance.</p>
                </div>
            </div>

            <div class="p-4 sm:p-6">
                @unless ($account)
                    <div class="rounded-xl border border-dashed border-amber-200 bg-amber-50/40 px-4 py-6 text-center" data-no-account>
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V10M9 21V10M15 21V10M19 21V10"/><path d="m3 10 9-6 9 6Z"/></svg>
                        </span>
                        <p class="mt-3 text-sm font-bold text-slate-700">No payout account yet</p>
                        <p class="mt-1 text-xs text-slate-500">Add a payout account to start requesting withdrawals.</p>
                        <div class="mt-4">
                            <a href="{{ route('wallet.payout-account') }}" class="oy-btn oy-btn-primary inline-flex w-full items-center justify-center gap-2 sm:w-auto">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                <span>Add payout account</span>
                            </a>
                        </div>
                    </div>
                @else
                    {{-- Destination account --}}
                    <div class="flex items-center gap-3 rounded-xl border border-sky-100 bg-sky-50/70 p-3 sm:p-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V10M9 21V10M15 21V10M19 21V10"/><path d="m3 10 9-6 9 6Z"/></svg>
                        </span>
                        <p class="min-w-0 break-words text-sm font-semibold text-slate-900">To: {{ $account->bank_name }} · ••••{{ $account->account_last_four ?? $account->last_four }}</p>
                    </div>

                    {{-- Errors --}}
                    <div class="space-y-2 empty:hidden">
                        @error('account')
                            <div class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                                <p class="min-w-0 break-words">{{ $message }}</p>
                            </div>
                        @enderror
                        @error('withdrawal')
                            <div class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                                <p class="min-w-0 break-words">{{ $message }}</p>
                            </div>
                        @enderror
                        @error('otp')
                            <div class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                                <p class="min-w-0 break-words">{{ $message }}</p>
                            </div>
                        @enderror
                    </div>

                    @if ($pendingOtp)
                        <section id="withdrawal-otp-panel"
                            data-expires-at="{{ $pendingOtp->expires_at->timestamp * 1000 }}"
                            data-resend-at="{{ $pendingOtp->created_at->timestamp * 1000 + (60 * 1000) }}"
                            class="mt-4 space-y-4 rounded-xl border border-indigo-100 bg-indigo-50/60 p-3 sm:p-5">
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                                </span>
                                <div class="min-w-0 space-y-1.5">
                                    <p class="text-sm text-slate-800">
                                        A confirmation code was emailed to you for
                                        <strong class="tabular-nums text-slate-900">₦{{ number_format($pendingOtp->amount_cents / 100, 2) }}</strong>.
                                        Enter that code below to submit your withdrawal request.
                                    </p>

                                    <p class="flex items-center gap-1.5 text-sm text-slate-700">
                                        <svg class="h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                        <span>
                                            Code expires in
                                            <strong id="withdrawal-otp-countdown" class="tabular-nums text-amber-700" aria-live="polite">10:00</strong>.
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('wallet.withdrawals.confirm') }}" class="space-y-3">
                                @csrf
                                <div>
                                    <label for="withdrawal_code" class="block text-sm font-semibold text-indigo-800">Email confirmation code</label>
                                    <input id="withdrawal_code" name="code" inputmode="numeric"
                                        autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}"
                                        class="mt-1.5 block w-full rounded-lg border {{ $errors->has('code') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-center text-lg font-semibold tracking-[0.5em] tabular-nums text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" required>
                                    @error('code') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">Confirm withdrawal</button>
                            </form>

                            <form method="POST" action="{{ route('wallet.withdrawals.store') }}" class="border-t border-indigo-100 pt-4">
                                @csrf
                                <input type="hidden" name="amount"
                                    value="{{ number_format($pendingOtp->amount_cents / 100, 2, '.', '') }}">
                                <button id="withdrawal-otp-resend" type="submit"
                                    class="oy-btn oy-btn-secondary w-full justify-center sm:w-auto" disabled>
                                    Resend code
                                </button>
                                <p id="withdrawal-otp-resend-status" class="mt-2 text-xs text-slate-500">
                                    You can request another code in 60 seconds.
                                </p>
                            </form>
                        </section>
                    @endif

                    <form id="withdrawal-request-panel"
                        data-withdrawal-request-form
                        method="POST"
                        action="{{ route('wallet.withdrawals.store') }}"
                        class="mt-4 space-y-4 {{ $pendingOtp ? 'hidden' : '' }}">
                        @csrf
                        <div>
                            <label for="amount" class="block text-sm font-semibold text-emerald-800">Amount (minimum ₦{{ number_format($minimum, 2) }})</label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500" aria-hidden="true">₦</span>
                                <input id="amount" name="amount" inputmode="decimal"
                                    value="{{ old('amount', $pendingOtp ? number_format($pendingOtp->amount_cents / 100, 2, '.', '') : '') }}"
                                    aria-invalid="{{ $errors->has('amount') ? 'true' : 'false' }}"
                                    class="block w-full rounded-lg border {{ $errors->has('amount') ? 'border-red-300' : 'border-slate-300' }} bg-white py-2.5 pl-8 pr-3 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" required>
                            </div>
                            @error('amount') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">Request withdrawal</button>
                    </form>

                    <script>
                    (() => {
                        const panel = document.getElementById('withdrawal-otp-panel');
                        if (!panel) return;

                        const expiry = Number(panel.dataset.expiresAt);
                        const resendAt = Number(panel.dataset.resendAt);
                        const countdown = document.getElementById('withdrawal-otp-countdown');
                        const resend = document.getElementById('withdrawal-otp-resend');
                        const resendStatus = document.getElementById('withdrawal-otp-resend-status');
                        const requestPanel = document.getElementById('withdrawal-request-panel');

                        const updateCountdowns = () => {
                            const now = Date.now();
                            const remaining = Math.max(0, Math.ceil((expiry - now) / 1000));
                            const minutes = Math.floor(remaining / 60);
                            const seconds = remaining % 60;

                            countdown.textContent =
                                String(minutes).padStart(2, '0') + ':' +
                                String(seconds).padStart(2, '0');

                            const resendRemaining = Math.max(0, Math.ceil((resendAt - now) / 1000));
                            resend.disabled = resendRemaining > 0;

                            resendStatus.textContent = resendRemaining > 0
                                ? 'You can request another code in ' + resendRemaining + ' seconds.'
                                : 'You can request a new code. The previous code will be invalidated when the new code is issued.';

                            if (remaining <= 0) {
                                panel.classList.add('hidden');
                                requestPanel.classList.remove('hidden');
                                window.clearInterval(timer);
                            }
                        };

                        updateCountdowns();
                        const timer = window.setInterval(updateCountdowns, 1000);
                    })();
                    </script>
<script>
document.querySelectorAll(
    '[data-withdrawal-request-form], form[action="{{ route('wallet.withdrawals.confirm') }}"]'
).forEach(function (form) {
    form.addEventListener('submit', function (event) {
        const button = event.submitter || this.querySelector('button[type="submit"], input[type="submit"]');

        if (!button || button.disabled) {
            event.preventDefault();
            return;
        }

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.classList.add('opacity-75', 'cursor-wait');

        const label = this.matches('[action="{{ route('wallet.withdrawals.confirm') }}"]')
            ? 'Submitting withdrawal...'
            : 'Processing...';

        button.innerHTML = '<span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true"></span> <span>' + label + '</span>';
    });
});
</script>

                @endunless
            </div>
        </section>
    </div>

    {{-- Withdrawal history --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-amber-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div class="min-w-0">
                <h2 class="text-base font-bold text-slate-900 sm:text-lg">Withdrawal history</h2>
                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Your withdrawal requests and their status.</p>
            </div>
        </div>

        @if ($withdrawals->isEmpty())
            <div class="p-4 sm:p-6">
                <div class="rounded-xl border border-dashed border-amber-200 bg-amber-50/40 px-4 py-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-bold text-slate-700">No withdrawal requests yet.</p>
                </div>
            </div>
        @else
            @php
                $statusClass = fn ($status) => match ($status) {
                    'requested' => 'bg-amber-50 text-amber-700 ring-amber-200',
                    'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
                    'cancelled', 'canceled' => 'bg-slate-100 text-slate-600 ring-slate-200',
                    'approved', 'paid', 'completed', 'processed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    default => 'bg-blue-50 text-blue-700 ring-blue-200',
                };
            @endphp

            {{-- Mobile history cards --}}
            <div class="space-y-3 bg-slate-50/50 p-3 md:hidden">
                @foreach ($withdrawals as $w)
                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-900">{{ $w->requested_at?->format('d M Y H:i') }}</h3>
                            <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClass($w->status) }}">{{ ucfirst($w->status) }}</span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-lg border border-emerald-100 bg-emerald-50/70 p-2.5">
                                <p class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                    Amount
                                </p>
                                <p class="mt-1 text-sm font-bold tabular-nums text-emerald-900">₦{{ number_format((float) $w->amount, 2) }}</p>
                            </div>
                            <div class="min-w-0 rounded-lg border border-sky-100 bg-sky-50/70 p-2.5">
                                <p class="inline-flex items-center gap-1 text-xs font-semibold text-sky-700">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V10M9 21V10M15 21V10M19 21V10"/><path d="m3 10 9-6 9 6Z"/></svg>
                                    Account
                                </p>
                                <p class="mt-1 break-words text-sm font-semibold text-sky-900">{{ $w->bank_name }} ••••{{ $w->account_last_four }}</p>
                            </div>
                        </div>

                        @if ($w->status === 'rejected' && $w->reject_reason)
                            <div class="mt-3 rounded-lg border border-rose-100 bg-rose-50/70 p-2.5 text-xs text-rose-800">
                                <span class="font-semibold">Reason:</span> {{ $w->reject_reason }}
                            </div>
                        @endif

                        @if ($w->status === 'requested')
                            <form method="POST" action="{{ route('wallet.withdrawals.cancel', $w) }}" class="mt-3 border-t border-slate-100 pt-3">
                                @csrf
                                <button type="submit" class="oy-btn oy-btn-secondary w-full justify-center" data-cancel-withdrawal>Cancel</button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </div>

            {{-- Tablet and desktop history table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-gradient-to-r from-slate-50 via-white to-indigo-50">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Requested</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-emerald-700">Amount</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Account</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Status</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($withdrawals as $w)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ $w->requested_at?->format('d M Y H:i') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-emerald-800">₦{{ number_format((float) $w->amount, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-800">{{ $w->bank_name }} ••••{{ $w->account_last_four }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClass($w->status) }}">{{ ucfirst($w->status) }}</span>
                                    @if ($w->status === 'rejected' && $w->reject_reason)
                                        <div class="mt-1 max-w-xs text-xs text-slate-500">{{ $w->reject_reason }}</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    @if ($w->status === 'requested')
                                        <form method="POST" action="{{ route('wallet.withdrawals.cancel', $w) }}">
                                            @csrf
                                            <button type="submit" class="oy-btn oy-btn-secondary" data-cancel-withdrawal>Cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-4 py-4 sm:px-6">{{ $withdrawals->links() }}</div>
        @endif
    </section>

</div>
@endsection