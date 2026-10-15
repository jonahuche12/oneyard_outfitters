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
                    <p class="text-sm" data-no-account>
                        Add a <a href="{{ route('wallet.payout-account') }}" class="oy-link">payout account</a> first.
                    </p>
                @else
                    <p class="text-sm mb-3">To: {{ $account->bank_name }} · ••••{{ $account->account_last_four ?? $account->last_four }}</p>
                    @error('account') <p class="text-sm text-red-600 mb-2">{{ $message }}</p> @enderror
                    @error('withdrawal') <p class="text-sm text-red-600 mb-2">{{ $message }}</p> @enderror
                    <form method="POST" action="{{ route('wallet.withdrawals.store') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label for="amount" class="oy-meta">Amount (minimum ₦{{ number_format($minimum, 2) }})</label>
                            <input id="amount" name="amount" inputmode="decimal" value="{{ old('amount') }}" class="oy-input w-full" required>
                            @error('amount') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="oy-btn oy-btn-primary">Request withdrawal</button>
                    </form>
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
