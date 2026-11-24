@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-8 lg:px-8">

    {{-- Page header --}}
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            @if ($mode === 'store')
                <a href="{{ route('stores.show', $store) }}"
                   class="text-sm font-medium text-slate-500 transition hover:text-slate-900">
                    ← {{ $store->name }}
                </a>
            @endif

            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
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

            <p class="mt-1 max-w-2xl text-sm leading-5 text-slate-500">
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
                <div class="shrink-0">
                    <a href="{{ route('store-fund-assignments.index') }}"
                       class="inline-flex min-h-10 w-full items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">
                        ← Store Funds workspace
                    </a>
                </div>
            @endcan
        @endif
        @if ($mode !== 'store' && $coordinatorStore)
            <div class="shrink-0">
                <a href="{{ route('stores.fund-assignments.index', $coordinatorStore) }}"
                   class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Open my store funds →
                </a>
            </div>
        @endif
    </header>

    @if ($mode === 'admin')
        <nav aria-label="Store Funds sections"
             class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
            <a href="{{ route('store-fund-assignments.index', ['tab' => 'requests']) }}"
               class="rounded-lg px-4 py-2 text-sm font-semibold {{ $tab === 'requests' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                Requests
            </a>
            <a href="{{ route('store-fund-assignments.index', ['tab' => 'assign']) }}"
               class="rounded-lg px-4 py-2 text-sm font-semibold {{ $tab === 'assign' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                Assign Funds
            </a>
            <a href="{{ route('store-fund-assignments.index', ['tab' => 'history']) }}"
               class="rounded-lg px-4 py-2 text-sm font-semibold {{ $tab === 'history' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                History
            </a>
        </nav>
    @elseif ($mode === 'requests')
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            Finance workspace: review store-submitted requests and view their request history.
        </div>
    @endif

    {{-- Feedback --}}
    @if (session('success'))
        <div role="status"
             class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert"
             class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
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
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4 sm:px-5">
                    <div>
                        <h2 class="text-sm font-bold text-slate-950">Request funds</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Enter cash and bank amounts separately. At least one must be greater than zero.
                        </p>
                    </div>
                    <span class="shrink-0 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 group-open:bg-slate-200">
                        <span class="group-open:hidden">Open</span>
                        <span class="hidden group-open:inline">Close</span>
                    </span>
                </summary>

                <div class="border-t border-slate-100 bg-slate-50/60 p-4 sm:p-5">
                    <form method="POST"
                          action="{{ route('stores.fund-assignments.request', $store) }}"
                          class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @csrf

                        <div class="sm:col-span-2">
                            <label for="request-purpose" class="block text-sm font-medium text-slate-700">Purpose</label>
                            <select id="request-purpose" name="purpose" required class="oy-input mt-1 w-full">
                                <option value="daily_transactions">Daily transactions</option>
                                <option value="physical_purchases">Physical purchases</option>
                                <option value="utilities">Utilities</option>
                            </select>
                        </div>

                        <div>
                            <label for="request-cash" class="block text-sm font-medium text-slate-700">Cash requested (₦)</label>
                            <input id="request-cash" name="requested_cash" type="number" min="0" step="0.01"
                                   value="0" required inputmode="decimal" class="oy-input mt-1 w-full">
                        </div>

                        <div>
                            <label for="request-bank" class="block text-sm font-medium text-slate-700">Bank requested (₦)</label>
                            <input id="request-bank" name="requested_bank" type="number" min="0" step="0.01"
                                   value="0" required inputmode="decimal" class="oy-input mt-1 w-full">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="request-reference" class="block text-sm font-medium text-slate-700">
                                Reference <span class="font-normal text-slate-400">(optional)</span>
                            </label>
                            <input id="request-reference" name="reference" maxlength="255" class="oy-input mt-1 w-full">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="request-note" class="block text-sm font-medium text-slate-700">
                                Note <span class="font-normal text-slate-400">(optional)</span>
                            </label>
                            <textarea id="request-note" name="note" rows="2" class="oy-input mt-1 w-full"></textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">
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
            'approved' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            'sent' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
            'confirmed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'rejected' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        ];

        $money = fn ($value) => '₦'.number_format((float) $value, 2);
    @endphp

    <section class="space-y-4">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-base font-bold tracking-tight text-slate-950">
                    {{ $mode === 'store' ? 'Assignment history' : ($tab === 'requests' ? 'Store fund requests' : 'Assignment history') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $assignments->total() }} {{ $assignments->total() === 1 ? 'record' : 'records' }}
                </p>
            </div>
        </div>

        @forelse ($assignments as $assignment)
            @php
                $status = (string) $assignment->status;
                $statusClass = $statusStyles[$status] ?? 'bg-slate-100 text-slate-700 ring-slate-600/20';
            @endphp

            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {{-- Card heading --}}
                <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-5">
                    <div class="min-w-0">
                        @if ($mode === 'admin' || $mode === 'requests')
                            @if ($assignment->store && \Illuminate\Support\Facades\Gate::allows('viewStore', [\App\Models\StoreFundAssignment::class, $assignment->store]))
                                <a href="{{ route('stores.fund-assignments.index', $assignment->store) }}"
                                   class="inline-flex max-w-full items-center gap-1 text-sm font-bold text-slate-950 transition hover:text-[var(--oy-primary)]">
                                    <span class="truncate">{{ $assignment->store->name }}</span>
                                    <span class="shrink-0 text-xs font-medium text-[var(--oy-primary)]">Open store funds →</span>
                                </a>
                            @else
                                <p class="truncate text-sm font-bold text-slate-950">
                                    {{ $assignment->store?->name ?? 'Store' }}
                                </p>
                            @endif
                        @endif

                        <h3 class="mt-1 text-sm font-semibold text-slate-800">
                            {{ ucwords(str_replace('_', ' ', $assignment->purpose)) }}
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ $assignment->business_date?->format('d M Y') }}
                            <span class="px-1 text-slate-300">·</span>
                            {{ ucwords(str_replace('_', ' ', $assignment->source)) }}
                            <span class="px-1 text-slate-300">·</span>
                            #{{ $assignment->id }}
                        </p>
                    </div>

                    <span class="inline-flex w-fit shrink-0 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClass }}">
                        {{ ucwords(str_replace('_', ' ', $status)) }}
                    </span>
                </div>

                <div class="space-y-4 p-4 sm:p-5">
                    {{-- Amounts --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Requested</p>
                            <dl class="mt-2 space-y-1 text-sm">
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Cash</dt>
                                    <dd class="font-semibold tabular-nums text-slate-900">{{ $money($assignment->requested_cash) }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Bank</dt>
                                    <dd class="font-semibold tabular-nums text-slate-900">{{ $money($assignment->requested_bank) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Sent</p>
                            <dl class="mt-2 space-y-1 text-sm">
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Cash</dt>
                                    <dd class="font-semibold tabular-nums text-slate-900">{{ $money($assignment->sent_cash ?? 0) }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Bank</dt>
                                    <dd class="font-semibold tabular-nums text-slate-900">{{ $money($assignment->sent_bank ?? 0) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">People</p>
                            <dl class="mt-2 space-y-1 text-xs">
                                <div class="flex justify-between gap-2">
                                    <dt class="shrink-0 text-slate-500">Requested</dt>
                                    <dd class="truncate text-right font-medium text-slate-800">{{ $assignment->requestedBy?->name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="shrink-0 text-slate-500">Dispatched</dt>
                                    <dd class="truncate text-right font-medium text-slate-800">{{ $assignment->dispatchedBy?->name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="shrink-0 text-slate-500">Confirmed</dt>
                                    <dd class="truncate text-right font-medium text-slate-800">{{ $assignment->confirmedBy?->name ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Reference and notes --}}
                    @if ($assignment->reference || $assignment->note || $assignment->rejection_reason)
                        <div class="space-y-2 text-sm">
                            @if ($assignment->reference)
                                <p class="break-words text-slate-600">
                                    <span class="font-semibold text-slate-700">Reference:</span>
                                    {{ $assignment->reference }}
                                </p>
                            @endif

                            @if ($assignment->note)
                                <p class="break-words text-slate-600">
                                    <span class="font-semibold text-slate-700">Note:</span>
                                    {{ $assignment->note }}
                                </p>
                            @endif

                            @if ($assignment->rejection_reason)
                                <p class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-700">
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
                                    <div class="grid grid-cols-1 gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
                                        <form method="POST"
                                              action="{{ route('store-fund-assignments.approve', $assignment) }}">
                                            @csrf
                                            <button type="submit" class="oy-btn oy-btn-primary w-full justify-center">
                                                Approve request
                                            </button>
                                        </form>

                                        <form method="POST"
                                              action="{{ route('store-fund-assignments.reject', $assignment) }}"
                                              class="flex flex-col gap-2">
                                            @csrf
                                            <label for="reject-reason-{{ $assignment->id }}" class="text-xs font-medium text-slate-600">
                                                Reason for rejection
                                            </label>
                                            <input id="reject-reason-{{ $assignment->id }}"
                                                   name="reason" required minlength="10" maxlength="5000"
                                                   placeholder="Enter a reason (at least 10 characters)"
                                                   class="oy-input w-full">
                                            <button type="submit"
                                                    class="inline-flex min-h-10 w-full items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
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
                                      class="grid grid-cols-1 gap-3 border-t border-slate-100 pt-4 sm:grid-cols-3">
                                    @csrf
                                    <div>
                                        <label for="sent-cash-{{ $assignment->id }}" class="block text-xs font-medium text-slate-600">Cash sent (₦)</label>
                                        <input id="sent-cash-{{ $assignment->id }}" name="sent_cash" type="number" min="0" step="0.01"
                                               value="{{ $assignment->requested_cash }}" required inputmode="decimal" class="oy-input mt-1 w-full">
                                    </div>
                                    <div>
                                        <label for="sent-bank-{{ $assignment->id }}" class="block text-xs font-medium text-slate-600">Bank sent (₦)</label>
                                        <input id="sent-bank-{{ $assignment->id }}" name="sent_bank" type="number" min="0" step="0.01"
                                               value="{{ $assignment->requested_bank }}" required inputmode="decimal" class="oy-input mt-1 w-full">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit" class="oy-btn oy-btn-primary w-full justify-center">
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
                                      class="grid grid-cols-1 gap-3 border-t border-slate-100 pt-4 sm:grid-cols-3">
                                    @csrf
                                    <div>
                                        <label for="correct-cash-{{ $assignment->id }}" class="block text-xs font-medium text-slate-600">Correct cash sent (₦)</label>
                                        <input id="correct-cash-{{ $assignment->id }}" name="sent_cash" type="number" min="0" step="0.01"
                                               value="{{ $assignment->sent_cash }}" required inputmode="decimal" class="oy-input mt-1 w-full">
                                    </div>
                                    <div>
                                        <label for="correct-bank-{{ $assignment->id }}" class="block text-xs font-medium text-slate-600">Correct bank sent (₦)</label>
                                        <input id="correct-bank-{{ $assignment->id }}" name="sent_bank" type="number" min="0" step="0.01"
                                               value="{{ $assignment->sent_bank }}" required inputmode="decimal" class="oy-input mt-1 w-full">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit"
                                                class="inline-flex min-h-10 w-full items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">
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
                                      class="grid grid-cols-1 gap-3 border-t border-slate-100 pt-4 sm:grid-cols-3">
                                    @csrf
                                    <div>
                                        <label for="received-cash-{{ $assignment->id }}" class="block text-xs font-medium text-slate-600">Cash received (₦)</label>
                                        <input id="received-cash-{{ $assignment->id }}" name="received_cash" type="number" min="0" step="0.01"
                                               value="{{ $assignment->sent_cash }}" required inputmode="decimal" class="oy-input mt-1 w-full">
                                    </div>
                                    <div>
                                        <label for="received-bank-{{ $assignment->id }}" class="block text-xs font-medium text-slate-600">Bank received (₦)</label>
                                        <input id="received-bank-{{ $assignment->id }}" name="received_bank" type="number" min="0" step="0.01"
                                               value="{{ $assignment->sent_bank }}" required inputmode="decimal" class="oy-input mt-1 w-full">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit" class="oy-btn oy-btn-primary w-full justify-center">
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
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center">
                <h3 class="text-sm font-semibold text-slate-900">No fund assignments found</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Records will appear here when fund requests or assignments are created.
                </p>
            </div>
        @endforelse

        @if ($assignments->hasPages())
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                {{ $assignments->links() }}
            </div>
        @endif
    </section>
    @endif
</div>
@endsection
