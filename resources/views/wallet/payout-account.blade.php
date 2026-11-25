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
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-white/15 bg-white/10 text-sky-300">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V10M9 21V10M15 21V10M19 21V10"/><path d="m3 10 9-6 9 6Z"/></svg>
                </span>
                <div class="min-w-0">
                    <h1 class="break-words text-2xl font-bold tracking-tight sm:text-3xl">Payout Account</h1>
                    <p class="mt-1 text-xs text-slate-400 sm:text-sm">The bank account your withdrawals are sent to.</p>
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

        {{-- Current account --}}
        @if ($account)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-current-account>
                <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 px-4 py-4 sm:px-6">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 ring-1 ring-sky-200">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V10M9 21V10M15 21V10M19 21V10"/><path d="m3 10 9-6 9 6Z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-slate-900 sm:text-lg">Current account</h2>
                        <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Where your withdrawals are paid.</p>
                    </div>
                </div>

                <div class="space-y-3 p-4 sm:p-6">
                    <div class="rounded-xl border border-sky-100 bg-gradient-to-br from-sky-50 to-white p-3 shadow-sm sm:p-4">
                        <p class="flex items-center gap-1.5 text-xs font-bold text-sky-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1"/></svg>
                            Account name
                        </p>
                        <p class="mt-1.5 break-words text-base font-bold text-sky-950 sm:text-lg">{{ $account->account_name }}</p>
                        <p class="mt-2 break-words text-sm font-semibold text-slate-700">{{ $account->bank_name }} · ••••{{ $account->last_four }}</p>
                    </div>

                    @if ($account->isUsable())
                        <p class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                            Ready for withdrawals.
                        </p>
                    @else
                        <p class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800 sm:text-sm" data-waiting>
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            <span>Usable for withdrawals from {{ $account->usable_from->format('d M Y H:i') }}.</span>
                        </p>
                    @endif
                </div>
            </section>
        @endif

        {{-- Add / change account --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm {{ $account ? '' : 'lg:col-span-2' }}">
            <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-emerald-50 via-white to-blue-50 px-4 py-4 sm:px-6">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                    @if ($account)
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m16 4 4 4L9 19H5v-4L16 4Z"/><path d="m14 6 4 4"/></svg>
                    @else
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    @endif
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-slate-900 sm:text-lg">{{ $account ? 'Change account' : 'Add account' }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Enter the bank details for your payouts.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('wallet.payout-account.store') }}" class="grid gap-4 p-4 sm:p-6">
                @csrf

                @if ($account)
                    <p class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800 sm:text-sm">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        <span>Changing your account starts a new 24-hour wait before it can be used for withdrawals.</span>
                    </p>
                @endif

                <div>
                    <label for="bank_code" class="block text-sm font-semibold text-sky-800">Bank</label>
                    <select id="bank_code" name="bank_code"
                        aria-invalid="{{ $errors->has('bank_code') ? 'true' : 'false' }}"
                        class="mt-1.5 block w-full rounded-lg border {{ $errors->has('bank_code') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" required>
                        <option value="">Select bank</option>
                        @foreach ($banks as $code => $name)
                            <option value="{{ $code }}" @selected(old('bank_code') === (string) $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('bank_code') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="account_name" class="block text-sm font-semibold text-emerald-800">Account name</label>
                    <input id="account_name" name="account_name" value="{{ old('account_name') }}"
                        aria-invalid="{{ $errors->has('account_name') ? 'true' : 'false' }}"
                        class="mt-1.5 block w-full rounded-lg border {{ $errors->has('account_name') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" required>
                    @error('account_name') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="account_number" class="block text-sm font-semibold text-indigo-800">Account number (10 digits)</label>
                    <input id="account_number" name="account_number" inputmode="numeric" maxlength="10" autocomplete="off"
                        aria-invalid="{{ $errors->has('account_number') ? 'true' : 'false' }}"
                        class="mt-1.5 block w-full rounded-lg border {{ $errors->has('account_number') ? 'border-red-300' : 'border-slate-300' }} bg-white px-3 py-2.5 text-sm tabular-nums tracking-wide text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" required>
                    @error('account_number') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end">
                    <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">Save account</button>
                </div>
            </form>
        </section>
    </div>

</div>
@endsection