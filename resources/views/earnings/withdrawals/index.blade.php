@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="oy-page-title">Withdrawal Requests</h1>
            <p class="mt-1 text-sm text-slate-500">
                Review requests, document decisions, and record completed transfers.
            </p>
        </div>
        <div class="text-sm text-slate-500">
            {{ $withdrawals->total() }} matching {{ \Illuminate\Support\Str::plural('request', $withdrawals->total()) }}
        </div>
    </div>

    @if(session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">Please review the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach([
            ['label' => 'Requested', 'key' => 'requested', 'color' => 'amber'],
            ['label' => 'Approved', 'key' => 'approved', 'color' => 'blue'],
            ['label' => 'Rejected', 'key' => 'rejected', 'color' => 'red'],
            ['label' => 'Paid', 'key' => 'paid', 'color' => 'emerald'],
            ['label' => 'Cancelled', 'key' => 'cancelled', 'color' => 'slate'],
        ] as $card)
            <a href="{{ route('earnings.withdrawals.index', array_merge(request()->except('page', 'status'), ['status' => $card['key']])) }}"
               class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-400 hover:shadow">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($summary[$card['key']]) }}</p>
            </a>
        @endforeach
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        @php
            $hasFilters = ($search ?? '') !== '' || $statusFilter !== '' || $fromFilter !== '' || $toFilter !== '';
        @endphp
        <form id="withdrawals-filter-form"
              data-ajax-list-form
              method="GET"
              action="{{ route('earnings.withdrawals.index') }}"
              class="space-y-4">
            <div>
                <label for="withdrawal-search" class="oy-meta">Search withdrawals</label>
                <input id="withdrawal-search"
                       type="search"
                       name="search"
                       value="{{ $search }}"
                       maxlength="100"
                       placeholder="Staff name, email, WD-12, bank, account name or payment reference"
                       class="oy-input w-full">
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="status" class="oy-meta">Status</label>
                    <select id="status" name="status" class="oy-input w-full">
                        <option value="">All statuses</option>
                        @foreach(['requested', 'approved', 'rejected', 'paid', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="from" class="oy-meta">Requested from</label>
                    <input id="from" type="date" name="from" value="{{ $fromFilter }}" class="oy-input w-full">
                </div>

                <div>
                    <label for="to" class="oy-meta">Requested to</label>
                    <input id="to" type="date" name="to" value="{{ $toFilter }}" class="oy-input w-full">
                </div>

                <div class="flex items-end gap-2">
                    <a href="{{ route('earnings.withdrawals.index') }}"
                       data-list-clear
                       data-list-clear-toggle
                       class="oy-btn oy-btn-secondary"
                       @if(! $hasFilters) style="display: none;" @endif>Clear</a>
                    <button type="submit" class="oy-btn oy-btn-primary flex-1">Apply filters</button>
                </div>
            </div>
        </form>
    </div>

    <div id="withdrawals-results" data-ajax-list-results>
        @include('earnings.withdrawals.partials.table')
    </div>
</div>

<div id="withdrawal-action-modal"
     data-reopen="{{ ($errors->hasAny(['approval_reason', 'reject_reason', 'payment_reference']) && old('modal_action')) ? 'true' : 'false' }}"
     data-reopen-type="{{ old('modal_type') }}"
     data-reopen-action="{{ old('modal_action') }}"
     data-reopen-value="{{ old('approval_reason', old('reject_reason', old('payment_reference'))) }}"
     class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4"
     role="dialog" aria-modal="true"
     aria-labelledby="withdrawal-modal-title"
     aria-describedby="withdrawal-modal-description">
    <div class="oy-card mx-auto mt-16 max-w-lg">
        <div class="oy-card-header flex items-start justify-between gap-4">
            <div>
                <h2 id="withdrawal-modal-title" class="oy-card-title">Review withdrawal</h2>
                <p id="withdrawal-modal-description" class="oy-meta mt-1"></p>
            </div>
            <button type="button" data-withdrawal-modal-close aria-label="Close dialog"
                    class="oy-btn oy-btn-ghost oy-btn-sm">&times;</button>
        </div>

        <form id="withdrawal-action-form" method="POST" action="" class="oy-card-body space-y-4">
            @csrf
            <input type="hidden" name="modal_type" id="withdrawal-modal-type" value="">
            <input type="hidden" name="modal_action" id="withdrawal-modal-action" value="">

            <div id="withdrawal-payment-warning"
                 class="hidden rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                Only record payment after the bank transfer has actually been completed.
            </div>

            <div id="withdrawal-reason-field">
                <label id="withdrawal-field-label" for="withdrawal-action-value" class="oy-meta">Approval reason</label>
                <textarea id="withdrawal-action-value" name="approval_reason" rows="3"
                          minlength="10" maxlength="1000" required class="oy-input w-full"
                          placeholder="Record the reason for this decision"></textarea>
                <p id="withdrawal-field-help" class="mt-1 text-xs text-slate-500">
                    Enter 10 to 1,000 characters. This reason is saved to the withdrawal record.
                </p>
                @php
                    $modalError = $errors->first('approval_reason')
                        ?: ($errors->first('reject_reason') ?: $errors->first('payment_reference'));
                @endphp
                @if($modalError)
                    <p data-modal-error class="mt-1 text-sm text-red-600">{{ $modalError }}</p>
                @endif
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" data-withdrawal-modal-close class="oy-btn oy-btn-secondary">Cancel</button>
                <button id="withdrawal-modal-submit" type="submit" class="oy-btn oy-btn-primary">Confirm decision</button>
            </div>
        </form>
    </div>
</div>

@endsection
