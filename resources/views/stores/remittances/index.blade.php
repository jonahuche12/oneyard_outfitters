@extends('layouts.app')

@section('content')
<div
    class="mx-auto max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-8 lg:px-8"
    data-store-remittances
    data-history-url="{{ route('stores.remittances.index', $store) }}"
    data-submit-url="{{ route('stores.remittances.store', $store) }}"
>
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <a href="{{ route('stores.show', $store) }}"
               class="text-sm font-medium text-slate-500 transition hover:text-slate-900">
                ← {{ $store->name }}
            </a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                Store remittances
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Submit and track remittances against closed cash days.
            </p>
        </div>

        <a href="{{ route('stores.show', $store) }}" class="oy-btn oy-btn-secondary">
            Back to store
        </a>
    </header>

    <section class="oy-card overflow-hidden">
        <div class="oy-card-header">
            <h2 class="oy-card-title">Submit a remittance</h2>
            <p class="oy-card-description">
                Select a closed business day. Cash and bank amounts are checked independently against that day's closing balances.
            </p>
        </div>

        <div class="oy-card-body">
            <div id="remittance-form-feedback"
                 class="mb-4 hidden rounded-lg border px-4 py-3 text-sm"
                 role="status" aria-live="polite"></div>

            <form id="store-remittance-form" class="oy-form space-y-5">
                @csrf

                <div>
                    <label for="remittance-daily-record" class="oy-label">
                        Closed business day <span class="oy-form-required">*</span>
                    </label>
                    <select id="remittance-daily-record"
                            name="store_daily_cash_record_id"
                            required
                            class="oy-select mt-1">
                        <option value="">Select a closed day</option>
                        @forelse ($dailyCashRecords as $record)
                            @php
                                $counted = $record->remittances
                                    ->whereIn('status', ['pending', 'confirmed']);

                                $remittedCash = (float) $counted->sum('amount_cash');
                                $remittedBank = (float) $counted->sum('amount_bank');

                                $remainingCash = max(0, (float) $record->closing_cash - $remittedCash);
                                $remainingBank = max(0, (float) $record->closing_bank - $remittedBank);
                            @endphp
                            <option value="{{ $record->id }}">
                                {{ $record->business_date }}
                                — Cash remaining: ₦{{ number_format($remainingCash, 2) }}
                                — Bank remaining: ₦{{ number_format($remainingBank, 2) }}
                            </option>
                        @empty
                            <option value="" disabled>No closed cash days available</option>
                        @endforelse
                    </select>
                    <p class="oy-form-hint">Only closed business days can receive remittance submissions.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="remittance-cash" class="oy-label">Cash amount (₦)</label>
                        <input id="remittance-cash"
                               name="amount_cash"
                               type="number"
                               min="0"
                               step="0.01"
                               value="0"
                               inputmode="decimal"
                               required
                               class="oy-input mt-1">
                    </div>

                    <div>
                        <label for="remittance-bank" class="oy-label">Bank amount (₦)</label>
                        <input id="remittance-bank"
                               name="amount_bank"
                               type="number"
                               min="0"
                               step="0.01"
                               value="0"
                               inputmode="decimal"
                               required
                               class="oy-input mt-1">
                    </div>
                </div>

                <div>
                    <label for="remittance-method" class="oy-label">
                        Remittance method <span class="oy-form-required">*</span>
                    </label>
                    <select id="remittance-method" name="method" required class="oy-select mt-1">
                        <option value="cash_handover">Cash handover</option>
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="bank_deposit">Bank deposit</option>
                    </select>
                </div>

                <div>
                    <label for="remittance-reference" class="oy-label">Reference (optional)</label>
                    <input id="remittance-reference"
                           name="reference"
                           maxlength="100"
                           class="oy-input mt-1"
                           placeholder="Transfer or deposit reference">
                </div>

                <div>
                    <label for="remittance-note" class="oy-label">Note (optional)</label>
                    <textarea id="remittance-note"
                              name="note"
                              rows="3"
                              maxlength="1000"
                              class="oy-textarea mt-1"
                              placeholder="Add any useful details about this remittance"></textarea>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                    <button type="submit"
                            id="remittance-submit-button"
                            class="oy-btn oy-btn-primary w-full sm:w-auto">
                        Review remittance
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="oy-card overflow-hidden">
        <div class="oy-card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="oy-card-title">Remittance history</h2>
                <p class="oy-card-description">
                    Pending, confirmed and rejected submissions.
                </p>
            </div>
            <button type="button"
                    id="remittance-history-refresh"
                    class="oy-btn oy-btn-secondary oy-btn-sm w-full sm:w-auto">
                Refresh history
            </button>
        </div>

        <div class="px-4 pt-4 sm:px-6">
            <div id="remittance-history-feedback"
                 class="hidden rounded-lg border px-4 py-3 text-sm"
                 role="status" aria-live="polite"></div>
        </div>

        <div class="p-4 sm:p-6">
            <div id="remittance-history-mobile" class="space-y-3 md:hidden">
                <div class="rounded-xl border border-slate-200 p-4 text-center text-sm text-slate-500">
                    Loading remittance history…
                </div>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="oy-table w-full min-w-[48rem] text-sm">
                    <thead>
                        <tr>
                            <th scope="col">Business date</th>
                            <th scope="col" class="text-right">Cash</th>
                            <th scope="col" class="text-right">Bank</th>
                            <th scope="col">Status</th>
                            <th scope="col">Reference</th>
                            <th scope="col">Review note</th>
                        </tr>
                    </thead>
                    <tbody id="remittance-history-body">
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                Loading remittance history…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <dialog id="remittance-submit-dialog"
            aria-labelledby="remittance-submit-dialog-title"
            class="m-auto w-[calc(100vw-1.5rem)] max-w-lg rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/50">
        <div class="flex max-h-[85dvh] flex-col">
            <header class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                <div>
                    <h2 id="remittance-submit-dialog-title" class="text-base font-semibold">
                        Confirm remittance submission
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Check the details before submitting.
                    </p>
                </div>
                <button type="button"
                        data-close-remittance-dialog
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Close dialog">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="m6 6 12 12M18 6 6 18"/>
                    </svg>
                </button>
            </header>

            <div class="space-y-4 overflow-y-auto p-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Business day</p>
                    <p id="remittance-dialog-date" class="mt-1 font-semibold text-slate-900">—</p>
                </div>

                <dl class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <dt class="text-xs text-slate-500">Cash amount</dt>
                        <dd id="remittance-dialog-cash" class="mt-1 break-words text-lg font-bold text-slate-900">₦0.00</dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <dt class="text-xs text-slate-500">Bank amount</dt>
                        <dd id="remittance-dialog-bank" class="mt-1 break-words text-lg font-bold text-slate-900">₦0.00</dd>
                    </div>
                </dl>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Method</span>
                        <span id="remittance-dialog-method" class="text-right font-medium">—</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Reference</span>
                        <span id="remittance-dialog-reference" class="max-w-[65%] break-words text-right font-medium">—</span>
                    </div>
                    <div class="border-t border-slate-100 pt-2">
                        <p class="text-slate-500">Note</p>
                        <p id="remittance-dialog-note" class="mt-1 whitespace-pre-wrap break-words font-medium">—</p>
                    </div>
                </div>

                <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-800">
                    Confirm that these amounts and details are correct before proceeding.
                </p>
                <div id="remittance-dialog-feedback"
                     class="hidden rounded-lg border px-3 py-2 text-sm"
                     role="alert" aria-live="assertive"></div>
            </div>

            <footer class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button"
                        data-close-remittance-dialog
                        class="oy-btn oy-btn-secondary w-full sm:w-auto">
                    Go back
                </button>
                <button type="button"
                        id="remittance-dialog-confirm"
                        class="oy-btn oy-btn-primary w-full sm:w-auto">
                    Confirm and submit
                </button>
            </footer>
        </div>
    </dialog>
</div>
@endsection
