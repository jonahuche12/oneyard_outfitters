@extends('layouts.app')

@section('content')
<div
    class="mx-auto max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-8 lg:px-8"
    data-remittance-review
    data-queue-url="{{ route('store-remittances.index') }}"
    data-confirm-url="{{ url('/store-remittances') }}"
    data-reject-url="{{ url('/store-remittances') }}"
>
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                Store remittance review
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Review pending remittance submissions from stores.
            </p>
        </div>

        <button type="button"
                id="remittance-queue-refresh"
                class="oy-btn oy-btn-secondary w-full sm:w-auto">
            Refresh queue
        </button>
    </header>

    <div id="remittance-queue-feedback"
         class="hidden rounded-lg border px-4 py-3 text-sm"
         role="status" aria-live="polite"></div>

    <section>
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">
                    Pending submissions
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Confirm accurate remittances or reject submissions with a clear reason.
                </p>
            </div>
            <span id="remittance-queue-count"
                  class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                —
            </span>
        </div>

        <div id="remittance-review-list" class="grid grid-cols-1 gap-3 xl:grid-cols-2">
            <div class="oy-card p-5 text-sm text-slate-500">
                Loading pending remittances…
            </div>
        </div>
    </section>

    <dialog id="remittance-review-dialog"
            aria-labelledby="remittance-review-dialog-title"
            class="m-auto w-[calc(100vw-1.5rem)] max-w-lg rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/50">
        <div class="flex max-h-[85dvh] flex-col">
            <header class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                <div>
                    <h2 id="remittance-review-dialog-title" class="text-base font-semibold">
                        Review remittance
                    </h2>
                    <p id="remittance-review-dialog-subtitle" class="mt-1 text-sm text-slate-500">
                        Confirm your decision.
                    </p>
                </div>
                <button type="button"
                        data-close-review-dialog
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Close dialog">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="m6 6 12 12M18 6 6 18"/>
                    </svg>
                </button>
            </header>

            <div class="space-y-4 overflow-y-auto p-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p id="remittance-review-dialog-store" class="font-semibold text-slate-900">—</p>
                    <p id="remittance-review-dialog-date" class="mt-1 text-sm text-slate-500">—</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs text-slate-500">Cash</p>
                        <p id="remittance-review-dialog-cash" class="mt-1 break-words font-bold">₦0.00</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs text-slate-500">Bank</p>
                        <p id="remittance-review-dialog-bank" class="mt-1 break-words font-bold">₦0.00</p>
                    </div>
                </div>

                <div id="remittance-review-dialog-confirm-copy"
                     class="hidden rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm text-emerald-800">
                    You are about to confirm this remittance. Make sure the submitted amounts and supporting details have been checked.
                </div>

                <div id="remittance-review-dialog-reject-copy"
                     class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-3 text-sm text-red-800">
                    You are about to reject this remittance. A clear reason is required and will be recorded with the decision.
                </div>

                <div id="remittance-review-reason-wrap">
                    <label for="remittance-review-dialog-reason" class="oy-label">
                        Review note
                    </label>
                    <textarea id="remittance-review-dialog-reason"
                              rows="3"
                              maxlength="1000"
                              class="oy-textarea mt-1"
                              placeholder="Optional note when confirming. Required when rejecting."></textarea>
                    <p id="remittance-review-reason-hint" class="oy-form-hint">
                        Rejection reasons must contain at least 10 characters.
                    </p>
                </div>

                <div id="remittance-review-dialog-feedback"
                     class="hidden rounded-lg border px-3 py-2 text-sm"
                     role="alert" aria-live="assertive"></div>
            </div>

            <footer class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button"
                        data-close-review-dialog
                        class="oy-btn oy-btn-secondary w-full sm:w-auto">
                    Cancel
                </button>
                <button type="button"
                        id="remittance-review-dialog-confirm"
                        class="oy-btn w-full sm:w-auto">
                    Confirm decision
                </button>
            </footer>
        </div>
    </dialog>
</div>
@endsection
