@extends('layouts.app')

@section('content')
<div class="oy-page">

    <a href="{{ route('wallet.index') }}" class="oy-link">← Back to My Wallet</a>

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Withdrawals</h1>
            <p class="oy-page-description">Request a transfer of your earnings to your payout account.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="oy-alert oy-alert-success mb-5">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2 mb-6">
        <div class="oy-card"><div class="oy-card-body">
            <div class="text-sm opacity-70">Wallet balance</div>
            <div class="text-2xl font-semibold">{{ (float) $ledger < 0 ? '-' : '' }}₦{{ number_format(abs((float) $ledger), 2) }}</div>
            <div class="text-sm opacity-70 mt-4">Available to withdraw</div>
            <div class="text-2xl font-semibold" data-available-balance>₦{{ number_format(max(0, (float) $available), 2) }}</div>
            <p class="text-xs opacity-70 mt-2">Available excludes any request still being processed.</p>
        </div></div>

        <div class="oy-card">
            <div class="oy-card-header"><h2 class="oy-card-title">Request a withdrawal</h2></div>
            <div class="oy-card-body">
                @unless ($account)
                    <div class="mb-4" data-no-account>
                        <a href="{{ route('wallet.payout-account') }}" class="oy-btn oy-btn-primary inline-flex items-center justify-center">
                            <span aria-hidden="true">+</span>
                            <span>Add payout account</span>
                        </a>
                    </div>
                @else
                    <p class="text-sm mb-3">To: {{ $account->bank_name }} · ••••{{ $account->account_last_four ?? $account->last_four }}</p>
                    @error('account') <p class="text-sm text-red-600 mb-2">{{ $message }}</p> @enderror
                    @error('withdrawal') <p class="text-sm text-red-600 mb-2">{{ $message }}</p> @enderror
                    @error('otp') <p class="text-sm text-red-600 mb-2">{{ $message }}</p> @enderror

                    @if ($pendingOtp)
                        <section id="withdrawal-otp-panel"
                            data-expires-at="{{ $pendingOtp->expires_at->timestamp * 1000 }}"
                            data-resend-at="{{ $pendingOtp->created_at->timestamp * 1000 + (60 * 1000) }}"
                            class="space-y-3 mb-5">
                            <p class="text-sm">
                                A confirmation code was emailed to you for
                                <strong>₦{{ number_format($pendingOtp->amount_cents / 100, 2) }}</strong>.
                                Enter that code below to submit your withdrawal request.
                            </p>

                            <p class="text-sm">
                                Code expires in
                                <strong id="withdrawal-otp-countdown" aria-live="polite">10:00</strong>.
                            </p>

                            <form method="POST" action="{{ route('wallet.withdrawals.confirm') }}" class="space-y-3">
                                @csrf
                                <div>
                                    <label for="withdrawal_code" class="oy-meta">Email confirmation code</label>
                                    <input id="withdrawal_code" name="code" inputmode="numeric"
                                        autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}"
                                        class="oy-input w-full" required>
                                    @error('code') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <button type="submit" class="oy-btn oy-btn-primary">Confirm withdrawal</button>
                            </form>

                            <form method="POST" action="{{ route('wallet.withdrawals.store') }}">
                                @csrf
                                <input type="hidden" name="amount"
                                    value="{{ number_format($pendingOtp->amount_cents / 100, 2, '.', '') }}">
                                <button id="withdrawal-otp-resend" type="submit"
                                    class="oy-btn oy-btn-secondary" disabled>
                                    Resend code
                                </button>
                                <p id="withdrawal-otp-resend-status" class="text-xs opacity-70 mt-1">
                                    You can request another code in 60 seconds.
                                </p>
                            </form>
                        </section>
                    @endif

                    <form id="withdrawal-request-panel"
                        data-withdrawal-request-form
                        method="POST"
                        action="{{ route('wallet.withdrawals.store') }}"
                        class="space-y-3 {{ $pendingOtp ? 'hidden' : '' }}">
                        @csrf
                        <div>
                            <label for="amount" class="oy-meta">Amount (minimum ₦{{ number_format($minimum, 2) }})</label>
                            <input id="amount" name="amount" inputmode="decimal"
                                value="{{ old('amount', $pendingOtp ? number_format($pendingOtp->amount_cents / 100, 2, '.', '') : '') }}"
                                class="oy-input w-full" required>
                            @error('amount') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="oy-btn oy-btn-primary">Request withdrawal</button>
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
        </div>
    </div>

    @if ($withdrawals->isEmpty())
        <div class="oy-card"><div class="oy-card-body"><div class="oy-empty-state">
            <div class="oy-empty-state-title">No withdrawal requests yet.</div>
        </div></div></div>
    @else
        <div class="oy-table-wrapper"><div class="oy-table-scroll">
            <table class="oy-table">
                <thead>
                    <tr>
                        <th scope="col">Requested</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Account</th>
                        <th scope="col">Status</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($withdrawals as $w)
                        <tr>
                            <td>{{ $w->requested_at?->format('d M Y H:i') }}</td>
                            <td>₦{{ number_format((float) $w->amount, 2) }}</td>
                            <td>{{ $w->bank_name }} ••••{{ $w->account_last_four }}</td>
                            <td>
                                {{ ucfirst($w->status) }}
                                @if ($w->status === 'rejected' && $w->reject_reason)
                                    <div class="text-xs opacity-70">{{ $w->reject_reason }}</div>
                                @endif
                            </td>
                            <td>
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
        </div></div>
        <div class="mt-5">{{ $withdrawals->links() }}</div>
    @endif

</div>
@endsection
