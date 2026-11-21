@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-5 sm:px-6 sm:py-8">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-5 py-6 sm:px-7">
                <a href="{{ route('stock.index', ['store_id' => $item->store_id]) }}"
                   class="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"/>
                        <path d="M20 12H9"/>
                    </svg>
                    Back to store stock
                </a>

                <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <span class="inline-flex rounded-full border border-amber-300/20 bg-amber-300/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-200">
                            Stock review
                        </span>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            Review stock item
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                            Check the item details before submitting an approval or rejection decision.
                        </p>
                    </div>

                    <span class="inline-flex shrink-0 items-center gap-2 self-start rounded-full bg-amber-400/15 px-3 py-2 text-xs font-bold text-amber-200">
                        <span class="h-2 w-2 rounded-full bg-amber-300"></span>
                        Pending approval
                    </span>
                </div>
            </div>

            <div class="p-5 sm:p-7">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/>
                            <path d="m3.5 7.7 8.5 4.5 8.5-4.5M12 12.2V21"/>
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="break-words text-xl font-bold tracking-tight text-slate-950">
                            {{ $item->name }}
                        </h2>
                        <p class="mt-1 font-mono text-sm font-semibold text-blue-700">{{ $item->code }}</p>
                        <p class="mt-2 break-words text-sm leading-6 text-slate-600">
                            {{ $item->description ?: 'No description provided.' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Owning store</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $item->store->name }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Created by</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $item->creator?->name ?? '—' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $item->category ?: '—' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grade</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $item->grade ?: '—' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unit</p>
                        <p class="mt-2">
                            <span class="inline-flex rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-800">
                                {{ strtoupper($item->unit) }}
                            </span>
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Item type</p>
                        <p class="mt-2">
                            @if($item->is_raw)
                                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                                    Raw material
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                    Finished item
                                </span>
                            @endif
                        </p>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Unit cost</p>
                        <p class="mt-2 text-base font-bold tabular-nums text-blue-950">
                            ₦{{ number_format((float) $item->unit_cost, 2) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Suggested price</p>
                        <p class="mt-2 text-sm font-bold tabular-nums text-slate-950">
                            {{ $item->suggested_price !== null ? '₦'.number_format((float) $item->suggested_price, 2) : '—' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current market price</p>
                        <p class="mt-2 text-sm font-bold tabular-nums text-slate-950">
                            {{ $item->current_market_price !== null ? '₦'.number_format((float) $item->current_market_price, 2) : '—' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST"
              action="{{ route('stock.items.review.store', $item) }}"
              class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @method('PATCH')

            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-5 sm:px-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M9 11 12 14 22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold tracking-tight text-slate-950">Review decision</h2>
                    <p class="mt-1 text-sm text-slate-500">Choose a decision and provide any necessary explanation.</p>
                </div>
            </div>

            <div class="space-y-5 p-5 sm:p-6">
                @error('item')
                    <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
                        {{ $message }}
                    </div>
                @enderror

                <div>
                    <label for="decision" class="mb-2 block text-sm font-semibold text-slate-800">
                        Decision <span class="text-red-600">*</span>
                    </label>
                    <select id="decision"
                            name="decision"
                            required
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 @error('decision') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror">
                        <option value="">Select a decision</option>
                        <option value="approved" @selected(old('decision') === 'approved')>Approve stock item</option>
                        <option value="rejected" @selected(old('decision') === 'rejected')>Reject stock item</option>
                    </select>
                    @error('decision')
                        <p class="mt-2 flex items-start gap-2 text-sm font-medium text-red-600">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 8v4m0 4h.01"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="reason" class="mb-2 block text-sm font-semibold text-slate-800">
                        Review reason
                    </label>
                    <textarea id="reason"
                              name="reason"
                              rows="4"
                              maxlength="5000"
                              placeholder="Required when rejecting. You may also add an approval note."
                              class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm leading-6 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 @error('reason') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror">{{ old('reason') }}</textarea>
                    <p class="mt-2 text-xs text-slate-500">
                        Provide a clear reason when rejecting this item. An approval note is optional.
                    </p>
                    @error('reason')
                        <p class="mt-2 flex items-start gap-2 text-sm font-medium text-red-600">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 8v4m0 4h.01"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <a href="{{ route('stock.index', ['store_id' => $item->store_id]) }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m5 12 4 4L19 6"/>
                    </svg>
                    Submit decision
                </button>
            </div>
        </form>
    </div>
@endsection
