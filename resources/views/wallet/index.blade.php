@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Page hero --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-white/15 bg-white/10 text-cyan-300">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h1 class="break-words text-2xl font-bold tracking-tight sm:text-3xl">My Wallet</h1>
                        <p class="mt-1 text-xs text-slate-400 sm:text-sm">Your earnings from delivered orders, with any adjustments.</p>
                    </div>
                </div>

                <div class="grid w-full grid-cols-2 gap-2 sm:w-auto sm:min-w-[320px] sm:gap-3">
                    <a href="{{ route('wallet.withdrawals') }}" class="flex min-h-12 items-center justify-center gap-2 rounded-xl bg-emerald-500 px-3 py-2.5 text-center text-emerald-950 shadow-sm transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 sm:px-4">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg>
                        <span class="text-xs font-bold leading-tight">Withdrawals</span>
                    </a>
                    <a href="{{ route('wallet.payout-account') }}" class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/10 px-3 py-2.5 text-center transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60 sm:px-4">
                        <svg class="h-5 w-5 shrink-0 text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V10M9 21V10M15 21V10M19 21V10"/><path d="m3 10 9-6 9 6Z"/></svg>
                        <span class="text-xs font-bold leading-tight text-white">Payout account</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Balances --}}
    @php $balanceNegative = (float) $balance < 0; @endphp
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-indigo-50 via-white to-emerald-50 px-4 py-4 sm:px-6">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            </span>
            <div class="min-w-0">
                <h2 class="text-base font-bold text-slate-900 sm:text-lg">Balances</h2>
                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">What you hold and what you can withdraw.</p>
            </div>
        </div>

        <div class="grid gap-3 p-4 sm:grid-cols-2 sm:gap-4 sm:p-6">
            <div class="rounded-xl border {{ $balanceNegative ? 'border-rose-100 from-rose-50' : 'border-indigo-100 from-indigo-50' }} bg-gradient-to-br to-white p-3 shadow-sm sm:p-4">
                <p class="flex items-center gap-1.5 text-xs font-bold {{ $balanceNegative ? 'text-rose-700' : 'text-indigo-700' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                    Current balance
                </p>
                <div class="mt-2 text-2xl font-bold tabular-nums tracking-tight {{ $balanceNegative ? 'text-rose-950' : 'text-indigo-950' }} sm:text-3xl" data-wallet-balance>
                    {{ (float) $balance < 0 ? '-' : '' }}₦{{ number_format(abs((float) $balance), 2) }}
                </div>
                @if ((float) $balance < 0)
                    <p class="mt-2 flex items-start gap-1.5 text-xs text-rose-800 sm:text-sm">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                        <span>A negative balance will be recovered from your future earnings.</span>
                    </p>
                @endif
            </div>

            <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-3 shadow-sm sm:p-4">
                <p class="flex items-center gap-1.5 text-xs font-bold text-emerald-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                    Available to withdraw
                </p>
                <div class="mt-2 text-2xl font-bold tabular-nums tracking-tight text-emerald-950 sm:text-3xl" data-available-balance>₦{{ number_format(max(0, (float) $available), 2) }}</div>
            </div>
        </div>
    </section>

    {{-- Wallet entries --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-amber-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/></svg>
            </span>
            <div class="min-w-0">
                <h2 class="text-base font-bold text-slate-900 sm:text-lg">Wallet entries</h2>
                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Earnings, adjustments and other movements on your wallet.</p>
            </div>
        </div>

        @if ($entries->isEmpty())
            <div class="p-4 sm:p-6">
                <div class="rounded-xl border border-dashed border-amber-200 bg-amber-50/40 px-4 py-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-bold text-slate-700">No wallet entries yet.</p>
                    <p class="mx-auto mt-1 max-w-md text-xs text-slate-500">
                        Earnings appear here after finance finalizes the earnings pool for a delivered order you worked on.
                    </p>
                </div>
            </div>
        @else
            @php
                $typeClass = fn ($type) => match (strtolower((string) $type)) {
                    'earning', 'earnings', 'credit', 'payout', 'share' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    'withdrawal', 'debit', 'reversal' => 'bg-rose-50 text-rose-700 ring-rose-200',
                    'adjustment', 'correction' => 'bg-amber-50 text-amber-700 ring-amber-200',
                    default => 'bg-slate-100 text-slate-600 ring-slate-200',
                };
            @endphp

            {{-- Mobile entry cards --}}
            <div class="space-y-3 bg-slate-50/50 p-3 md:hidden">
                @foreach ($entries as $entry)
                    @php $entryNegative = (float) $entry->amount < 0; @endphp
                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-900">{{ $entry->created_at?->format('d M Y') }}</h3>
                            <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $typeClass($entry->type) }}">{{ ucfirst($entry->type) }}</span>
                        </div>

                        <div class="mt-3 rounded-lg border {{ $entryNegative ? 'border-rose-100 bg-rose-50/70' : 'border-emerald-100 bg-emerald-50/70' }} p-2.5">
                            <p class="inline-flex items-center gap-1 text-xs font-semibold {{ $entryNegative ? 'text-rose-700' : 'text-emerald-700' }}">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                Amount
                            </p>
                            <p class="mt-1 text-sm font-bold tabular-nums {{ $entryNegative ? 'text-rose-900' : 'text-emerald-900' }}">
                                {{ (float) $entry->amount < 0 ? '-' : '' }}₦{{ number_format(abs((float) $entry->amount), 2) }}
                            </p>
                        </div>

                        <div class="mt-3 space-y-2 border-t border-slate-100 pt-3 text-xs">
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-slate-500">Order</span>
                                <span class="break-words text-right font-medium text-slate-800">{{ $entry->pool?->order?->order_number ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-slate-500">Note</span>
                                <span class="min-w-0 break-words text-right font-medium text-slate-800">{{ $entry->reason ?? '—' }}</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Tablet and desktop entry table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-gradient-to-r from-slate-50 via-white to-indigo-50">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Date</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Type</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Order</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Note</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($entries as $entry)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ $entry->created_at?->format('d M Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $typeClass($entry->type) }}">{{ ucfirst($entry->type) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-800">{{ $entry->pool?->order?->order_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600"><span class="block max-w-xs break-words">{{ $entry->reason ?? '—' }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ (float) $entry->amount < 0 ? 'text-rose-800' : 'text-emerald-800' }}">
                                    {{ (float) $entry->amount < 0 ? '-' : '' }}₦{{ number_format(abs((float) $entry->amount), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-4 py-4 sm:px-6">
                {{ $entries->links() }}
            </div>
        @endif
    </section>

</div>
@endsection