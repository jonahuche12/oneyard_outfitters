@extends('layouts.app')

@section('content')
@php
    $inputClass = 'block min-h-10 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 sm:min-h-12 sm:py-3 sm:text-base';
    $labelClass = 'mb-1 block text-xs font-semibold text-slate-700 sm:mb-1.5 sm:text-sm';
    $errorClass = 'mt-1 text-xs text-red-600 sm:text-sm';
    $btnPrimary = 'inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:min-h-12 sm:py-3';
    $btnSecondary = 'inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:min-h-12 sm:py-3';
    $btnGlass = 'inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-5 py-2 text-sm font-semibold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/70 sm:min-h-12 sm:w-auto sm:py-3';
    $btnWhite = 'inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl bg-white px-5 py-2 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-white/70 sm:min-h-12 sm:w-auto sm:py-3';
    $btnRowDanger = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1';
    $tile = 'rounded-xl border border-slate-100 bg-slate-50/80 p-3 sm:p-4';
    $dt = 'text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs';
    $dd = 'mt-1.5 break-words text-xs font-semibold text-slate-900 sm:text-sm';
    $overlay = 'hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 p-4';
    $modalCard = 'mx-auto mt-10 max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:mt-16';
    $modalHead = 'flex items-start justify-between gap-4 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5';
    $modalTitle = 'text-base font-bold tracking-tight text-slate-950 sm:text-lg';
    $closeBtn = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 sm:h-10 sm:w-10';
@endphp

<div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Hero --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-4 py-5 sm:px-7 sm:py-6">
            <a href="{{ route('earnings.pools.index') }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition hover:text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
                Back to Earnings
            </a>

            <div class="mt-4 flex flex-col gap-4 sm:mt-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold tracking-wide text-slate-200 sm:text-xs">Earnings pool</span>
                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold sm:text-xs {{ $pool->status === 'finalized' ? 'bg-emerald-400/15 text-emerald-300' : 'bg-amber-400/15 text-amber-300' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $pool->status === 'finalized' ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                            {{ ucfirst($pool->status) }}
                        </span>
                    </div>
                    <h1 class="mt-3 break-words text-xl font-bold tracking-tight text-white sm:text-3xl">{{ $pool->order?->order_number }}</h1>
                    <p class="mt-1.5 inline-flex items-center gap-2 text-xs font-medium text-slate-300 sm:mt-2 sm:text-sm">
                        <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                        {{ $pool->order?->organization?->name }}
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:gap-3 lg:justify-end">
                    @can('deriveContributions', $pool)
                        <form method="POST" action="{{ route('earnings.pools.refresh-contributions', $pool) }}" class="w-full sm:w-auto">
                            @csrf
                            <button type="submit" class="{{ $btnGlass }}" data-refresh-contributions>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.3-5.7L20 8"/><path d="M20 3v5h-5"/></svg>
                                Refresh from workflow
                            </button>
                        </form>
                    @endcan
                    @can('assignContribution', $pool)
                        <button type="button" class="{{ $btnGlass }}" data-open-pool-modal="contribution-modal">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            Add contribution
                        </button>
                    @endcan
                    @can('finalize', $pool)
                        <button type="button" class="{{ $btnWhite }}" data-open-pool-modal="finalize-modal">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                            Finalize pool
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Feedback --}}
    @foreach (['status_message', 'success'] as $flashKey)
        @if (session($flashKey))
            <div role="status" class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs leading-5 text-emerald-800 sm:text-sm sm:leading-6">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                </span>
                <span>{{ session($flashKey) }}</span>
            </div>
        @endif
    @endforeach

    @if (session('pool_held'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900 sm:text-sm sm:leading-6" data-pool-held>{{ session('pool_held') }}</div>
    @endif

    @if (session('derivation_skipped'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900 sm:text-sm sm:leading-6" data-derivation-skipped>
            <strong>Not credited from workflow:</strong>
            <ul class="ml-5 mt-2 list-disc space-y-1">
                @foreach (session('derivation_skipped') as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-5 sm:space-y-6">

        <div class="grid gap-5 sm:gap-6 xl:grid-cols-2">
            {{-- Pool --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 sm:h-10 sm:w-10">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Z"/><path d="M4 6h16M8 10h8m-8 4h6"/></svg>
                    </span>
                    <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Pool</h2>
                </div>
                <div class="p-3 sm:p-6">
                    <dl class="grid grid-cols-2 gap-2.5 sm:gap-4">
                        <div class="{{ $tile }}"><dt class="{{ $dt }}">Quotation</dt><dd class="{{ $dd }}">{{ $pool->quotation_number }}</dd></div>
                        <div class="{{ $tile }}"><dt class="{{ $dt }}">Source amount</dt><dd class="{{ $dd }} tabular-nums">₦{{ number_format((float) $pool->source_amount, 2) }}</dd></div>
                        <div class="{{ $tile }}"><dt class="{{ $dt }}">Rate</dt><dd class="{{ $dd }} tabular-nums">{{ rtrim(rtrim((string) $pool->rate_percent, '0'), '.') }}%</dd></div>
                        <div class="rounded-xl border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-3 sm:p-4"><dt class="text-[10px] font-semibold uppercase tracking-wide text-violet-800 sm:text-xs">Pool amount</dt><dd class="mt-1.5 break-words text-sm font-bold tabular-nums text-violet-950 sm:text-base" data-pool-amount>₦{{ number_format((float) $pool->pool_amount, 2) }}</dd></div>
                        <div class="{{ $tile }}"><dt class="{{ $dt }}">Delivered</dt><dd class="{{ $dd }}">{{ $pool->delivered_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                        @if ($pool->finalized_at)
                            <div class="{{ $tile }}"><dt class="{{ $dt }}">Finalized</dt><dd class="{{ $dd }}">{{ $pool->finalized_at->format('d M Y H:i') }}</dd></div>
                        @endif
                    </dl>
                </div>
            </section>

            {{-- Payment --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700 sm:h-10 sm:w-10">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M15 8.5c-.6-.6-1.5-.9-2.5-.9-1.4 0-2.5.7-2.5 1.8 0 2.8 5 1.1 5 4 0 1.2-1.1 2-2.7 2-1 0-2-.4-2.8-1.1M12 6v12"/></svg>
                    </span>
                    <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Payment</h2>
                </div>
                <div class="p-3 sm:p-6">
                    <dl class="grid grid-cols-1 gap-2.5 sm:grid-cols-3 sm:gap-4">
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-3 sm:block sm:p-4">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-blue-800 sm:text-xs">Order total</dt>
                            <dd class="break-words text-xs font-bold tabular-nums text-blue-950 sm:mt-1.5 sm:text-sm">₦{{ number_format($payment['total'], 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-3 sm:block sm:p-4">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-emerald-800 sm:text-xs">Paid</dt>
                            <dd class="break-words text-xs font-bold tabular-nums text-emerald-950 sm:mt-1.5 sm:text-sm">₦{{ number_format($payment['paid'], 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-xl border p-3 sm:block sm:p-4 {{ $payment['covered'] ? 'border-emerald-100 bg-gradient-to-br from-emerald-50 to-white' : 'border-amber-100 bg-gradient-to-br from-amber-50 to-white' }}">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide sm:text-xs {{ $payment['covered'] ? 'text-emerald-800' : 'text-amber-800' }}">Balance</dt>
                            <dd class="break-words text-xs font-bold tabular-nums sm:mt-1.5 sm:text-sm {{ $payment['covered'] ? 'text-emerald-950' : 'text-amber-950' }}">₦{{ number_format($payment['balance'], 2) }}</dd>
                        </div>
                    </dl>
                    @if (! $payment['covered'] && $pool->status !== 'finalized')
                        <div class="mt-3 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900 sm:mt-4 sm:text-sm" data-payment-warning>
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 2.8 19h18.4L12 3Z"/><path d="M12 9v4m0 3h.01"/></svg>
                            <span>Cannot finalize: the order balance is outstanding.</span>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        {{-- Contributions --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700 sm:h-10 sm:w-10">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18.5 14.5a6.5 6.5 0 0 1 3 5.5"/></svg>
                </span>
                <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Contributions</h2>
            </div>

            @if ($pool->contributions->isEmpty())
                <div class="m-3 flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center sm:m-6">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18.5 14.5a6.5 6.5 0 0 1 3 5.5"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-semibold text-slate-800">No contributions recorded yet.</p>
                </div>
            @else
                {{-- Mobile cards --}}
                <div class="space-y-2.5 p-3 lg:hidden">
                    @foreach ($pool->contributions as $contribution)
                        <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm {{ $contribution->voided_at ? 'line-through opacity-60' : '' }}" @if ($contribution->voided_at) data-voided-contribution @endif>
                            <div class="flex items-start justify-between gap-2 border-b border-slate-100 bg-slate-50/80 px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-slate-950">{{ $names[$contribution->user_id] ?? 'Unknown' }}</p>
                                    <p class="text-[11px] capitalize text-slate-500">{{ str_replace('_', ' ', $contribution->activity_key) }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700">{{ rtrim(rtrim((string) $contribution->points, '0'), '.') }} pts</span>
                            </div>
                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 px-3 py-2.5">
                                <div>
                                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Source</dt>
                                    <dd class="mt-0.5 text-xs font-semibold text-slate-900">{{ ucfirst($contribution->source_type) }}</dd>
                                </div>
                                <div class="text-right">
                                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">When</dt>
                                    <dd class="mt-0.5 text-xs font-semibold text-slate-900">{{ $contribution->activity_at?->format('d M Y H:i') }}</dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Reason</dt>
                                    <dd class="mt-0.5 break-words text-xs leading-5 text-slate-700">{{ $contribution->reason ?? '—' }}</dd>
                                </div>
                            </dl>
                            @if ($contribution->voided_at)
                                <div class="border-t border-slate-100 px-3 py-2 text-[11px] font-medium text-red-700 no-underline">Voided: {{ $contribution->void_reason }}</div>
                            @else
                                @can('voidContribution', $pool)
                                    <div class="border-t border-slate-100 bg-slate-50/70 p-2.5">
                                        <button type="button" class="{{ $btnRowDanger }} w-full"
                                            data-void-contribution
                                            data-void-id="{{ $contribution->id }}"
                                            data-void-url="{{ route('earnings.pools.contributions.void', [$pool, $contribution]) }}">
                                            Void
                                        </button>
                                    </div>
                                @endcan
                            @endif
                        </article>
                    @endforeach
                </div>

                {{-- Desktop table --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-3">Staff</th>
                                <th scope="col" class="px-4 py-3">Activity</th>
                                <th scope="col" class="px-4 py-3 text-right">Points</th>
                                <th scope="col" class="px-4 py-3">Source</th>
                                <th scope="col" class="px-4 py-3">Reason</th>
                                <th scope="col" class="px-4 py-3">When</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($pool->contributions as $contribution)
                                <tr class="transition hover:bg-slate-50/80 {{ $contribution->voided_at ? 'line-through opacity-60' : '' }}" @if ($contribution->voided_at) data-voided-contribution @endif>
                                    <td class="px-4 py-3 font-semibold text-slate-950">{{ $names[$contribution->user_id] ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3 capitalize text-slate-700">{{ str_replace('_', ' ', $contribution->activity_key) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900">{{ rtrim(rtrim((string) $contribution->points, '0'), '.') }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ ucfirst($contribution->source_type) }}</td>
                                    <td class="min-w-48 px-4 py-3 text-slate-600">{{ $contribution->reason ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">{{ $contribution->activity_at?->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 text-right no-underline">
                                        @if ($contribution->voided_at)
                                            <span class="text-xs font-medium text-red-700">Voided: {{ $contribution->void_reason }}</span>
                                        @else
                                            @can('voidContribution', $pool)
                                                <button type="button" class="{{ $btnRowDanger }}"
                                                    data-void-contribution
                                                    data-void-id="{{ $contribution->id }}"
                                                    data-void-url="{{ route('earnings.pools.contributions.void', [$pool, $contribution]) }}">
                                                    Void
                                                </button>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($pool->status === 'finalized')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 sm:h-10 sm:w-10">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                    </span>
                    <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Finalized allocations</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs sm:text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-3">Staff</th>
                                <th scope="col" class="px-4 py-3 text-right">Points</th>
                                <th scope="col" class="px-4 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($pool->allocations as $allocation)
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="px-4 py-3 font-semibold text-slate-950">{{ $names[$allocation->user_id] ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ rtrim(rtrim((string) $allocation->effective_points, '0'), '.') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-bold tabular-nums text-slate-950">₦{{ number_format((float) $allocation->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700 sm:h-10 sm:w-10">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                    <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Allocation preview</h2>
                </div>
                @if ($preview['verdict'] === 'held')
                    <div class="m-3 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900 sm:m-6 sm:text-sm" data-preview-held>
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 2.8 19h18.4L12 3Z"/><path d="M12 9v4m0 3h.01"/></svg>
                        <span>
                            This pool would be held:
                            {{ collect($preview['hold_reasons'])->map(fn ($r) => str_replace('_', ' ', $r))->implode(', ') }}.
                        </span>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-xs sm:text-sm">
                            <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Staff</th>
                                    <th scope="col" class="px-4 py-3 text-right">Points</th>
                                    <th scope="col" class="px-4 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($preview['allocations'] as $row)
                                    <tr class="transition hover:bg-slate-50/80">
                                        <td class="px-4 py-3 font-semibold text-slate-950">{{ $names[$row['user_id']] ?? 'Unknown' }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ rtrim(rtrim((string) $row['effective_points'], '0'), '.') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-bold tabular-nums text-slate-950">₦{{ number_format((float) $row['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="border-t border-slate-100 bg-slate-50/70 px-4 py-3 text-[11px] text-slate-500 sm:px-6 sm:text-xs">Preview only. Nothing is recorded until the pool is finalized.</p>
                @endif
            </section>
        @endif

    </div>

    @can('assignContribution', $pool)
        <div id="contribution-modal" data-pool-modal
             data-reopen="{{ $errors->hasAny(['user_id', 'activity_key', 'activity_at', 'reason']) ? 'true' : 'false' }}"
             class="{{ $overlay }}"
             role="dialog" aria-modal="true" aria-labelledby="contribution-modal-title">
            <div class="{{ $modalCard }}">
                <div class="{{ $modalHead }}">
                    <h2 id="contribution-modal-title" class="{{ $modalTitle }}">Add contribution</h2>
                    <button type="button" data-close-pool-modal aria-label="Close" class="{{ $closeBtn }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('earnings.pools.contributions.store', $pool) }}" class="space-y-3 p-4 sm:space-y-4 sm:p-6">
                    @csrf
                    <div>
                        <label for="contribution-user" class="{{ $labelClass }}">Staff member</label>
                        <select id="contribution-user" name="user_id" class="{{ $inputClass }}" required data-modal-focus>
                            <option value="">Select staff</option>
                            @foreach ($staff as $person)
                                <option value="{{ $person->id }}" @selected((string) old('user_id') === (string) $person->id)>{{ $person->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="contribution-activity" class="{{ $labelClass }}">Activity</label>
                        <select id="contribution-activity" name="activity_key" class="{{ $inputClass }}" required>
                            <option value="">Select activity</option>
                            @foreach ($weights as $weight)
                                <option value="{{ $weight->activity_key }}" @selected(old('activity_key') === $weight->activity_key)>{{ $weight->label }}</option>
                            @endforeach
                        </select>
                        @error('activity_key') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="contribution-at" class="{{ $labelClass }}">When the work was done</label>
                        <input id="contribution-at" type="datetime-local" name="activity_at" value="{{ old('activity_at') }}" class="{{ $inputClass }}" required>
                        @error('activity_at') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="contribution-reason" class="{{ $labelClass }}">Reason (at least 10 characters)</label>
                        <textarea id="contribution-reason" name="reason" rows="3" class="{{ $inputClass }}" required>{{ old('reason') }}</textarea>
                        @error('reason') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                        <button type="button" class="{{ $btnSecondary }}" data-close-pool-modal>Cancel</button>
                        <button type="submit" class="{{ $btnPrimary }}">Record contribution</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @can('voidContribution', $pool)
        <div id="void-modal" data-pool-modal
             data-reopen="{{ $errors->hasAny(['void_reason', 'contribution']) ? 'true' : 'false' }}"
             class="{{ $overlay }}"
             role="dialog" aria-modal="true" aria-labelledby="void-modal-title">
            <div class="{{ $modalCard }}">
                <div class="{{ $modalHead }}">
                    <h2 id="void-modal-title" class="{{ $modalTitle }}">Void contribution</h2>
                    <button type="button" data-close-pool-modal aria-label="Close" class="{{ $closeBtn }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="void-form" method="POST"
                      action="{{ old('void_target') ? route('earnings.pools.contributions.void', [$pool, old('void_target')]) : '' }}"
                      class="space-y-3 p-4 sm:space-y-4 sm:p-6">
                    @csrf
                    <input type="hidden" name="void_target" id="void-target" value="{{ old('void_target') }}">
                    <p class="text-xs leading-5 text-slate-600 sm:text-sm sm:leading-6">The contribution stays in history but no longer counts toward the allocation.</p>
                    @error('contribution') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    <div>
                        <label for="void-reason" class="{{ $labelClass }}">Reason (at least 10 characters)</label>
                        <textarea id="void-reason" name="void_reason" rows="3" class="{{ $inputClass }}" required data-modal-focus>{{ old('void_reason') }}</textarea>
                        @error('void_reason') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                        <button type="button" class="{{ $btnSecondary }}" data-close-pool-modal>Cancel</button>
                        <button type="submit" class="{{ $btnPrimary }}">Void contribution</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @can('finalize', $pool)
        <div id="finalize-modal" data-pool-modal data-reopen="false"
             class="{{ $overlay }}"
             role="dialog" aria-modal="true" aria-labelledby="finalize-modal-title">
            <div class="{{ $modalCard }}">
                <div class="{{ $modalHead }}">
                    <h2 id="finalize-modal-title" class="{{ $modalTitle }}">Finalize earnings pool</h2>
                    <button type="button" data-close-pool-modal aria-label="Close" class="{{ $closeBtn }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('earnings.pools.finalize', $pool) }}" class="space-y-3 p-4 sm:space-y-4 sm:p-6">
                    @csrf
                    <p class="text-xs leading-5 text-slate-600 sm:text-sm sm:leading-6">
                        This records each person's share and credits their wallet. Finalized shares cannot be edited;
                        corrections are made through audited adjustments.
                    </p>
                    @if (! $payment['covered'])
                        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900 sm:text-sm">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 2.8 19h18.4L12 3Z"/><path d="M12 9v4m0 3h.01"/></svg>
                            <span>The order balance is still outstanding, so finalization will be refused.</span>
                        </div>
                    @endif
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                        <button type="button" class="{{ $btnSecondary }}" data-close-pool-modal data-modal-focus>Cancel</button>
                        <button type="submit" class="{{ $btnPrimary }}">Finalize pool</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

</div>
@endsection