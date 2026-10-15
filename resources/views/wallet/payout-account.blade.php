@extends('layouts.app')

@section('content')
<div class="oy-page">

    <a href="{{ route('wallet.index') }}" class="oy-link">← Back to My Wallet</a>

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Payout Account</h1>
            <p class="oy-page-description">The bank account your withdrawals are sent to.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="oy-alert oy-alert-success mb-5">{{ session('success') }}</div>
    @endif

    @if ($account)
        <div class="oy-card mb-5" data-current-account>
            <div class="oy-card-header"><h2 class="oy-card-title">Current account</h2></div>
            <div class="oy-card-body">
                <p>{{ $account->account_name }}</p>
                <p>{{ $account->bank_name }} · ••••{{ $account->last_four }}</p>
                @if ($account->isUsable())
                    <p class="text-sm opacity-70 mt-2">Ready for withdrawals.</p>
                @else
                    <p class="text-sm opacity-70 mt-2" data-waiting>
                        Usable for withdrawals from {{ $account->usable_from->format('d M Y H:i') }}.
                    </p>
                @endif
            </div>
        </div>
    @endif

    <div class="oy-card">
        <div class="oy-card-header">
            <h2 class="oy-card-title">{{ $account ? 'Change account' : 'Add account' }}</h2>
        </div>
        <form method="POST" action="{{ route('wallet.payout-account.store') }}" class="oy-card-body space-y-4">
            @csrf
            @if ($account)
                <p class="text-sm opacity-70">
                    Changing your account starts a new 24-hour wait before it can be used for withdrawals.
                </p>
            @endif
            <div>
                <label for="bank_code" class="oy-meta">Bank</label>
                <select id="bank_code" name="bank_code" class="oy-input w-full" required>
                    <option value="">Select bank</option>
                    @foreach ($banks as $code => $name)
                        <option value="{{ $code }}" @selected(old('bank_code') === (string) $code)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('bank_code') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="account_name" class="oy-meta">Account name</label>
                <input id="account_name" name="account_name" value="{{ old('account_name') }}" class="oy-input w-full" required>
                @error('account_name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="account_number" class="oy-meta">Account number (10 digits)</label>
                <input id="account_number" name="account_number" inputmode="numeric" maxlength="10" autocomplete="off" class="oy-input w-full" required>
                @error('account_number') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="oy-btn oy-btn-primary">Save account</button>
        </form>
    </div>

</div>
@endsection
