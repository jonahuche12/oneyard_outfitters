@extends('layouts.app')

@section('content')
@php
    $inputClass = 'block min-h-10 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 sm:min-h-12 sm:py-3 sm:text-base';
    $labelClass = 'mb-1 block text-xs font-semibold text-slate-700 sm:mb-1.5 sm:text-sm';
    $miniLabelClass = 'mb-1 block text-[11px] font-semibold text-slate-600 sm:text-xs';
    $btnPrimary = 'inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:min-h-12 sm:py-3';
    $btnHeroGlass = 'inline-flex min-h-10 w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-5 py-2 text-sm font-semibold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/70 sm:min-h-12 sm:w-auto sm:py-3';
@endphp

<div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Hero --}}
    <header class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-4 py-5 sm:px-7 sm:py-6">
            @if ($mode === 'store')
                <a href="{{ route('stores.show', $store) }}"
                   class="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"/>
                        <path d="M20 12H9"/>
                    </svg>
                    {{ $store->name }}
                </a>
            @endif

            <div class="@if ($mode === 'store') mt-4 sm:mt-5 @endif flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h1 class="break-words text-xl font-bold tracking-tight text-white sm:text-3xl">
                        @if ($mode === 'requests')
                            Store Fund Requests
                        @elseif ($mode === 'admin' && $tab === 'requests')
                            Store Fund Requests
                        @elseif ($mode === 'admin' && $tab === 'assign')
                            Assign Funds
                        @elseif ($mode === 'admin')
                            Store Funds History
                        @else
                            Assigned Funds
                        @endif
                    </h1>

                    <p class="mt-1.5 max-w-2xl text-xs leading-5 text-slate-300 sm:mt-2 sm:text-sm sm:leading-6">
                        @if ($mode === 'requests')
                            Review store-submitted requests and record decisions.
                        @elseif ($mode === 'admin' && $tab === 'requests')
                            Review store-submitted requests and record approval or rejection decisions.
                        @elseif ($mode === 'admin' && $tab === 'assign')
                            Create direct assignments for eligible stores. Every assignment still requires dispatch.
                        @elseif ($mode === 'admin')
                            Review all fund requests, direct assignments, dispatches and receipts.
                        @else
                            View fund activity and manage requests and receipt confirmations for {{ $store->name }}.
                        @endif
                    </p>
                </div>

                @if ($mode === 'store')
                    @can('viewAny', \App\Models\StoreFundAssignment::class)
                        <a href="{{ route('store-fund-assignments.index') }}" class="{{ $btnHeroGlass }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m15 18-6-6 6-6"/>
                                <path d="M20 12H9"/>
                            </svg>
                            Store Funds workspace
                        </a>
                    @endcan
                @endif
                @if ($mode !== 'store' && $coordinatorStore)
                    <a href="{{ route('stores.fund-assignments.index', $coordinatorStore) }}" class="{{ $btnHeroGlass }}">
                        Open my store funds
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    </header>

    @if ($mode === 'admin')
        <nav aria-label="Store Funds sections"
             class="flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
            <a href="{{ route('store-fund-assignments.index', ['tab' => 'requests']) }}"
               class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 py-2 text-xs font-semibold transition sm:flex-none sm:text-sm {{ $tab === 'requests' ? 'bg-indigo-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 13h4l2 3h4l2-3h4M4 13V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v7M4 13v5a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/>
                </svg>
                Requests
            </a>
            <a href="{{ route('store-fund-assignments.index', ['tab' => 'assign']) }}"
               class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 py-2 text-xs font-semibold transition sm:flex-none sm:text-sm {{ $tab === 'assign' ? 'bg-indigo-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M21 3 10 14M21 3l-7 18-4-8-8-4 19-6Z"/>
                </svg>
                Assign Funds
            </a>
            <a href="{{ route('store-fund-assignments.index', ['tab' => 'history']) }}"
               class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 py-2 text-xs font-semibold transition sm:flex-none sm:text-sm {{ $tab === 'history' ? 'bg-indigo-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                    <path d="M3 3v5h5m4-1v5l3 2"/>
                </svg>
                History
            </a>
        </nav>
    @elseif ($mode === 'requests')
        <div class="flex items-start gap-3 rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white px-4 py-3 text-xs leading-5 text-blue-900 sm:text-sm sm:leading-6">
            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                    <path d="M12 8h.01M11 12h1v5h1"/>
                </svg>
            </span>
            <span>Finance workspace: review store-submitted requests and view their request history.</span>
        </div>
    @endif

    {{-- Feedback --}}
    @if (session('success'))
        <div role="status"
             class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs leading-5 text-emerald-800 sm:text-sm sm:leading-6">
            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
            </span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div role="alert"
             class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800 sm:py-4 sm:text-sm">
            <p class="font-semibold">The action could not be completed.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Store actions --}}
    @if ($mode === 'store')

        @can('request', [\App\Models\StoreFundAssignment::class, $store])
            <details open class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-5 [&::-webkit-details-marker]:hidden">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 sm:h-10 sm:w-10">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <path d="M3 10h18M7 15h4"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Request funds</h2>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500 sm:mt-1 sm:text-sm">
                                Enter cash and bank amounts separately. At least one must be greater than zero.
                            </p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 group-open:bg-slate-200">
                        <span class="group-open:hidden">Open</span>
                        <span class="hidden group-open:inline">Close</span>
                    </span>
                </summary>

                <div class="border-t border-slate-100 bg-slate-50/70 p-4 sm:p-6">
                    <form method="POST"
                          action="{{ route('stores.fund-assignments.request', $store) }}"
                          class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                        @csrf

                        <div class="sm:col-span-2">
                            <label for="request-purpose" class="{{ $labelClass }}">Purpose</label>
                            <select id="request-purpose" name="purpose" required class="{{ $inputClass }}">
                                <option value="daily_transactions">Daily transactions</option>
                                <option value="physical_purchases">Physical purchases</option>
                                <option value="utilities">Utilities</option>
                            </select>
                        </div>

                        <div>
                            <label for="request-cash" class="{{ $labelClass }}">Cash requested (₦)</label>
                            <input id="request-cash" name="requested_cash" type="number" min="0" step="0.01"
                                   value="0" required inputmode="decimal" class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label for="request-bank" class="{{ $labelClass }}">Bank requested (₦)</label>
                            <input id="request-bank" name="requested_bank" type="number" min="0" step="0.01"
                                   value="0" required inputmode="decimal" class="{{ $inputClass }}">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="request-reference" class="{{ $labelClass }}">
                                Reference <span class="font-normal text-slate-400">(optional)</span>
                            </label>
                            <input id="request-reference" name="reference" maxlength="255" class="{{ $inputClass }}">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="request-note" class="{{ $labelClass }}">
                                Note <span class="font-normal text-slate-400">(optional)</span>
                            </label>
                            <textarea id="request-note" name="note" rows="2" class="{{ $inputClass }}"></textarea>
                        </div>

                        <div class="border-t border-slate-200/70 pt-3 sm:col-span-2 sm:pt-4">
                            <button type="submit" class="{{ $btnPrimary }} w-full sm:w-auto">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M21 3 10 14M21 3l-7 18-4-8-8-4 19-6Z"/>
                                </svg>
                                Submit funds request
                            </button>
                        </div>
                    </form>
                </div>
            </details>
        @endcan
    @endif

    @if ($mode === 'admin' && $tab === 'assign')
        @include('store-fund-assignments.partials.direct-assignments')
    @endif

    @if (! ($mode === 'admin' && $tab === 'assign'))
    {{-- Assignment list --}}
    @php
        $statusStyles = [
            'requested' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'approved' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            'sent' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
            'confirmed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'rejected' => 'bg-red-50 text-red-700 ring-red-600/20',
        ];

        $statusDots = [
            'requested' => 'bg-amber-500',
            'approved' => 'bg-blue-500',
            'sent' => 'bg-violet-500',
            'confirmed' => 'bg-emerald-500',
            'rejected' => 'bg-red-500',
        ];

        $money = fn ($value) => '₦'.number_format((float) $value, 2);
    @endphp

    <section class="space-y-3 sm:space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700 sm:h-10 sm:w-10">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                        <path d="M3 3v5h5m4-1v5l3 2"/>
                    </svg>
                </span>
                <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">
                    {{ $mode === 'store' ? 'Assignment history' : ($tab === 'requests' ? 'Store fund requests' : 'Assignment history') }}
                </h2>
            </div>

            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 sm:px-3 sm:py-1.5 sm:text-xs">
                {{ $assignments->total() }} {{ $assignments->total() === 1 ? 'record' : 'records' }}
            </span>
        </div>

        @forelse ($assignments as $assignment)
            @php
                $status = (string) $assignment->status;
                $statusClass = $statusStyles[$status] ?? 'bg-slate-100 text-slate-700 ring-slate-600/20';
                $statusDot = $statusDots[$status] ?? 'bg-slate-400';
            @endphp

            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {{-- Card heading --}}
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-3 sm:px-5 sm:py-4">
                    <div class="min-w-0">
                        @if ($mode === 'admin' || $mode === 'requests')
                            @if ($assignment->store && \Illuminate\Support\Facades\Gate::allows('viewStore', [\App\Models\StoreFundAssignment::class, $assignment->store]))
                                <a href="{{ route('stores.fund-assignments.index', $assignment->store) }}"
                                   class="inline-flex max-w-full items-center gap-2 text-sm font-bold text-slate-950 transition hover:text-indigo-700">
                                    <span class="truncate">{{ $assignment->store->name }}</span>
                                    <span class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-blue-700">
                                        Open store funds
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                                        </svg>
                                    </span>
                                </a>
                            @else
                                <p class="truncate text-sm font-bold text-slate-950">
                                    {{ $assignment->store?->name ?? 'Store' }}
                                </p>
                            @endif
                        @endif

                        <h3 class="mt-0.5 text-xs font-semibold text-slate-800 sm:text-sm">
                            {{ ucwords(str_replace('_', ' ', $assignment->purpose)) }}
                        </h3>

                        <p class="mt-0.5 text-[11px] text-slate-500 sm:text-xs">
                            {{ $assignment->business_date?->format('d M Y') }}
                            <span class="px-1 text-slate-300">·</span>
                            {{ ucwords(str_replace('_', ' ', $assignment->source)) }}
                            <span class="px-1 text-slate-300">·</span>
                            #{{ $assignment->id }}
                        </p>
                    </div>

                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset sm:px-2.5 sm:py-1 sm:text-xs {{ $statusClass }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                        {{ ucwords(str_replace('_', ' ', $status)) }}
                    </span>
                </div>

                <div class="space-y-3 p-3 sm:space-y-4 sm:p-5">
                    {{-- Amounts --}}
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3 sm:gap-3">
                        <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-3">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-blue-800 sm:text-[11px]">Requested</p>
                            <dl class="mt-2 space-y-1 text-xs sm:text-sm">
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Cash</dt>
                                    <dd class="font-bold tabular-nums text-blue-950">{{ $money($assignment->requested_cash) }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Bank</dt>
                                    <dd class="font-bold tabular-nums text-blue-950">{{ $money($assignment->requested_bank) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-xl border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-3">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-violet-800 sm:text-[11px]">Sent</p>
                            <dl class="mt-2 space-y-1 text-xs sm:text-sm">
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Cash</dt>
                                    <dd class="font-bold tabular-nums text-violet-950">{{ $money($assignment->sent_cash ?? 0) }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Bank</dt>
                                    <dd class="font-bold tabular-nums text-violet-950">{{ $money($assignment->sent_bank ?? 0) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-[11px]">People</p>
                            <dl class="mt-2 space-y-1 text-[11px] sm:text-xs">
                                <div class="flex justify-between gap-2">
                                    <dt class="shrink-0 text-slate-500">Requested</dt>
                                    <dd class="truncate text-right font-semibold text-slate-900">{{ $assignment->requestedBy?->name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="shrink-0 text-slate-500">Dispatched</dt>
                                    <dd class="truncate text-right font-semibold text-slate-900">{{ $assignment->dispatchedBy?->name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="shrink-0 text-slate-500">Confirmed</dt>
                                    <dd class="truncate text-right font-semibold text-slate-900">{{ $assignment->confirmedBy?->name ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Reference and notes --}}
                    @if ($assignment->reference || $assignment->note || $assignment->rejection_reason)
                        <div class="space-y-2 text-xs sm:text-sm">
                            @if ($assignment->reference)
                                <p class="break-words rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2 text-slate-600">
                                    <span class="font-semibold text-slate-700">Reference:</span>
                                    {{ $assignment->reference }}
                                </p>
                            @endif

                            @if ($assignment->note)
                                <p class="break-words rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2 text-slate-600">
                                    <span class="font-semibold text-slate-700">Note:</span>
                                    {{ $assignment->note }}
                                </p>
                            @endif

                            @if ($assignment->rejection_reason)
                                <p class="break-words rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-red-700">
                                    <span class="font-semibold">Rejection reason:</span>
                                    {{ $assignment->rejection_reason }}
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Admin and finance request review --}}
                    @if (in_array($mode, ['admin', 'requests'], true))
                        @if ($tab === 'requests')
                            @can('approve', $assignment)
                                @if ($assignment->status === 'requested')
                                    <div class="grid grid-cols-1 gap-3 border-t border-slate-100 pt-3 sm:grid-cols-2 sm:pt-4">
                                        <form method="POST"
                                              action="{{ route('store-fund-assignments.approve', $assignment) }}">
                                            @csrf
                                            <button type="submit" class="{{ $btnPrimary }} w-full">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="m5 12 4 4L19 6"/>
                                                </svg>
                                                Approve request
                                            </button>
                                        </form>

                                        <form method="POST"
                                              action="{{ route('store-fund-assignments.reject', $assignment) }}"
                                              class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                                            @csrf
                                            <label for="reject-reason-{{ $assignment->id }}" class="text-[11px] font-semibold text-slate-600 sm:text-xs">
                                                Reason for rejection
                                            </label>
                                            <input id="reject-reason-{{ $assignment->id }}"
                                                   name="reason" required minlength="10" maxlength="5000"
                                                   placeholder="Enter a reason (at least 10 characters)"
                                                   class="{{ $inputClass }}">
                                            <button type="submit"
                                                    class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-5 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:min-h-12 sm:py-3">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="M6 18 18 6M6 6l12 12"/>
                                                </svg>
                                                Reject request
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @endcan
                        @endif

                        {{-- Dispatch --}}
                        @can('dispatch', $assignment)
                            @if ($assignment->status === 'approved')
                                <form method="POST"
                                      action="{{ route('store-fund-assignments.dispatch', $assignment) }}"
                                      class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3 sm:grid-cols-3 sm:p-4">
                                    @csrf
                                    <div>
                                        <label for="sent-cash-{{ $assignment->id }}" class="{{ $miniLabelClass }}">Cash sent (₦)</label>
                                        <input id="sent-cash-{{ $assignment->id }}" name="sent_cash" type="number" min="0" step="0.01"
                                               value="{{ $assignment->requested_cash }}" required inputmode="decimal" class="{{ $inputClass }}">
                                    </div>
                                    <div>
                                        <label for="sent-bank-{{ $assignment->id }}" class="{{ $miniLabelClass }}">Bank sent (₦)</label>
                                        <input id="sent-bank-{{ $assignment->id }}" name="sent_bank" type="number" min="0" step="0.01"
                                               value="{{ $assignment->requested_bank }}" required inputmode="decimal" class="{{ $inputClass }}">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit" class="{{ $btnPrimary }} w-full">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M21 3 10 14M21 3l-7 18-4-8-8-4 19-6Z"/>
                                            </svg>
                                            Record dispatch
                                        </button>
                                    </div>
                                </form>
                            @endif
                        @endcan

                        {{-- Dispatch correction --}}
                        @can('correctDispatch', $assignment)
                            @if ($assignment->status === 'sent')
                                <form method="POST"
                                      action="{{ route('store-fund-assignments.correct-dispatch', $assignment) }}"
                                      class="grid grid-cols-1 gap-3 rounded-xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-3 sm:grid-cols-3 sm:p-4">
                                    @csrf
                                    <div>
                                        <label for="correct-cash-{{ $assignment->id }}" class="{{ $miniLabelClass }}">Correct cash sent (₦)</label>
                                        <input id="correct-cash-{{ $assignment->id }}" name="sent_cash" type="number" min="0" step="0.01"
                                               value="{{ $assignment->sent_cash }}" required inputmode="decimal" class="{{ $inputClass }}">
                                    </div>
                                    <div>
                                        <label for="correct-bank-{{ $assignment->id }}" class="{{ $miniLabelClass }}">Correct bank sent (₦)</label>
                                        <input id="correct-bank-{{ $assignment->id }}" name="sent_bank" type="number" min="0" step="0.01"
                                               value="{{ $assignment->sent_bank }}" required inputmode="decimal" class="{{ $inputClass }}">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit"
                                                class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-100 px-5 py-2 text-sm font-bold text-amber-900 transition hover:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 sm:min-h-12 sm:py-3">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="m16 4 4 4L9 19H5v-4L16 4Z"/>
                                            </svg>
                                            Correct dispatch
                                        </button>
                                    </div>
                                </form>
                            @endif
                        @endcan
                    @else
                        {{-- Store coordinator receipt confirmation --}}
                        @can('confirm', $assignment)
                            @if ($assignment->status === 'sent')
                                <form method="POST"
                                      action="{{ route('store-fund-assignments.confirm', $assignment) }}"
                                      class="grid grid-cols-1 gap-3 rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-3 sm:grid-cols-3 sm:p-4">
                                    @csrf
                                    <div>
                                        <label for="received-cash-{{ $assignment->id }}" class="{{ $miniLabelClass }}">Cash received (₦)</label>
                                        <input id="received-cash-{{ $assignment->id }}" name="received_cash" type="number" min="0" step="0.01"
                                               value="{{ $assignment->sent_cash }}" required inputmode="decimal" class="{{ $inputClass }}">
                                    </div>
                                    <div>
                                        <label for="received-bank-{{ $assignment->id }}" class="{{ $miniLabelClass }}">Bank received (₦)</label>
                                        <input id="received-bank-{{ $assignment->id }}" name="received_bank" type="number" min="0" step="0.01"
                                               value="{{ $assignment->sent_bank }}" required inputmode="decimal" class="{{ $inputClass }}">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit" class="{{ $btnPrimary }} w-full">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="m5 12 4 4L19 6"/>
                                            </svg>
                                            Confirm exact receipt
                                        </button>
                                    </div>
                                </form>
                            @endif
                        @endcan
                    @endif
                </div>
            </article>
        @empty
            <div class="flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                        <path d="M3 3v5h5m4-1v5l3 2"/>
                    </svg>
                </span>
                <h3 class="mt-3 text-sm font-semibold text-slate-800">No fund assignments found</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Records will appear here when fund requests or assignments are created.
                </p>
            </div>
        @endforelse

        @if ($assignments->hasPages())
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-6">
                {{ $assignments->links() }}
            </div>
        @endif
    </section>
    @endif
</div>
@endsection