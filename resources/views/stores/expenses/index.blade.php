@extends('layouts.app')

@section('content')
@php
    $inputClass = fn (string $field) => 'block min-h-10 w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:ring-2 sm:min-h-12 sm:py-3 sm:text-base '
        .($errors->has($field)
            ? 'border-red-300 focus:border-red-500 focus:ring-red-100'
            : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100');

    $labelClass = 'mb-1 block text-xs font-semibold text-slate-700 sm:mb-1.5 sm:text-sm';
    $errorClass = 'mt-1 text-xs text-red-600 sm:text-sm';
@endphp

<div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Hero --}}
    <header class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-4 py-5 sm:px-7 sm:py-6">
            <a href="{{ route('stores.show', $store) }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition hover:text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m15 18-6-6 6-6"/>
                    <path d="M20 12H9"/>
                </svg>
                {{ $store->name }}
            </a>

            <div class="mt-4 sm:mt-5">
                @if ($canRecordExpenses)
                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-400/15 px-3 py-1 text-[11px] font-semibold text-emerald-300 sm:text-xs">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        Business day open · Expenses enabled
                    </span>
                @elseif ($dailyCashToday)
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold text-slate-200 sm:text-xs">
                        <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                        Business day closed
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 rounded-full bg-amber-400/15 px-3 py-1 text-[11px] font-semibold text-amber-300 sm:text-xs">
                        <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                        Opening balances not recorded
                    </span>
                @endif

                <h1 class="mt-3 break-words text-xl font-bold tracking-tight text-white sm:text-3xl">
                    Store expenses
                </h1>

                <p class="mt-1.5 text-xs leading-5 text-slate-300 sm:mt-2 sm:text-sm sm:leading-6">
                    Record store expenses and review previous entries.
                </p>
            </div>
        </div>
    </header>

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

    @if (session('warning'))
        <div role="alert"
             class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900 sm:text-sm sm:leading-6">
            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M12 9v4m0 3h.01"/>
                </svg>
            </span>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div role="alert"
             class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800 sm:py-4 sm:text-sm">
            <p class="font-semibold">Please correct the following:</p>
            <ul class="mt-2 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Record expense --}}
    @if ($canRecordExpenses)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 sm:h-10 sm:w-10">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                        <path d="M3 10h18M7 15h4"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Record an expense</h2>
                    <p class="mt-0.5 text-xs leading-5 text-slate-500 sm:mt-1 sm:text-sm">
                        Entries are attached to today's business day and cannot be edited or deleted after saving.
                    </p>
                </div>
            </div>

            <form method="POST"
                  action="{{ route('stores.expenses.store', $store) }}"
                  class="p-4 sm:p-6">
                @csrf

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                    {{-- Amount --}}
                    <div>
                        <label for="amount" class="{{ $labelClass }}">
                            Amount (₦) <span class="text-red-600">*</span>
                        </label>
                        <input
                            id="amount"
                            name="amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            inputmode="decimal"
                            autocomplete="off"
                            required
                            value="{{ old('amount') }}"
                            placeholder="0.00"
                            class="{{ $inputClass('amount') }}"
                            @if ($errors->has('amount')) aria-invalid="true" @endif
                        >
                        @error('amount')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label for="category" class="{{ $labelClass }}">
                            Expense category <span class="text-red-600">*</span>
                        </label>
                        <select
                            id="category"
                            name="category"
                            required
                            class="{{ $inputClass('category') }}"
                            @if ($errors->has('category')) aria-invalid="true" @endif
                        >
                            <option value="">Choose a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>
                                    {{ ucwords(str_replace('_', ' ', $category)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Payment method --}}
                    <div>
                        <label for="payment_method" class="{{ $labelClass }}">
                            Payment method <span class="text-red-600">*</span>
                        </label>
                        <select
                            id="payment_method"
                            name="payment_method"
                            required
                            class="{{ $inputClass('payment_method') }}"
                            @if ($errors->has('payment_method')) aria-invalid="true" @endif
                        >
                            <option value="">Choose payment method</option>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method }}" @selected(old('payment_method') === $method)>
                                    {{ strtoupper($method) }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="sm:col-span-2">
                        <label for="description" class="{{ $labelClass }}">
                            Description <span class="text-red-600">*</span>
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            required
                            maxlength="2000"
                            placeholder="Briefly describe what the money was spent on."
                            class="{{ $inputClass('description') }}"
                            @if ($errors->has('description')) aria-invalid="true" @endif
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-5 border-t border-slate-100 pt-4 sm:mt-6 sm:pt-5">
                    <button type="submit"
                            class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:min-h-12 sm:w-auto sm:py-3">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m5 12 4 4L19 6"/>
                        </svg>
                        Save expense
                    </button>
                    <p class="mt-2 text-[11px] leading-5 text-slate-500 sm:text-xs">
                        Review the amount and details before saving. Saved entries are permanent.
                    </p>
                </div>
            </form>
        </section>
    @else
        {{-- Recording unavailable --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-start gap-3 bg-slate-50/70 p-4 sm:p-6">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $dailyCashToday ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-700' }} sm:h-10 sm:w-10">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="5" y="11" width="14" height="9" rx="2"/>
                        <path d="M8 11V8a4 4 0 0 1 8 0v3"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Expense recording unavailable</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-600 sm:text-sm sm:leading-6">
                        @if ($dailyCashToday)
                            Today's business day has been closed. Expenses can no longer be recorded for it.
                        @else
                            Record today's opening cash and bank balances on the Store page before recording expenses.
                        @endif
                    </p>
                    <a href="{{ route('stores.show', $store) }}"
                       class="mt-3 inline-flex min-h-10 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-4 sm:min-h-12 sm:w-auto sm:py-3">
                        Return to store
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- Expense history --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700 sm:h-10 sm:w-10">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                        <path d="M3 3v5h5m4-1v5l3 2"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Expense history</h2>
                    <p class="mt-0.5 text-xs leading-5 text-slate-500 sm:mt-1 sm:text-sm">
                        All expenses recorded for this store, including goods purchases and debt repayments.
                    </p>
                </div>
            </div>

            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 sm:px-3 sm:py-1.5 sm:text-xs">
                {{ $expenses->count() }} shown
            </span>
        </div>

        @if ($expenses->isEmpty())
            <div class="m-4 flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center sm:m-6">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                        <path d="M3 10h18M7 15h4"/>
                    </svg>
                </span>
                <h3 class="mt-3 text-sm font-semibold text-slate-800">No expenses recorded yet</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Saved expenses will appear here.
                </p>
            </div>
        @else
            {{-- Mobile history cards --}}
            <div class="space-y-2.5 p-3 md:hidden">
                @foreach ($expenses as $expense)
                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 bg-slate-50/80 px-3 py-2.5">
                            <div class="min-w-0">
                                <h3 class="text-xs font-bold leading-5 text-slate-950">
                                    {{ ucwords(str_replace('_', ' ', $expense->category)) }}
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    {{ $expense->dailyCashRecord?->business_date?->format('d M Y') ?? '—' }}
                                </p>
                            </div>
                            <p class="shrink-0 text-right text-sm font-bold tabular-nums text-slate-950">
                                ₦{{ number_format((float) $expense->amount, 2) }}
                            </p>
                        </div>

                        <div class="px-3 py-2.5">
                            <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-blue-700">
                                {{ $expense->payment_method }}
                            </span>

                            <p class="mt-2 whitespace-pre-line break-words text-xs leading-5 text-slate-700">
                                {{ $expense->description }}
                            </p>
                        </div>

                        <div class="border-t border-slate-100 px-3 py-2 text-[11px] leading-5 text-slate-500">
                            <p>Recorded by {{ $expense->recordedBy?->name ?? 'Unknown' }}</p>
                            <p>{{ $expense->created_at?->format('d M Y, g:i a') }}</p>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop history table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Date</th>
                            <th scope="col" class="px-4 py-3">Category / Description</th>
                            <th scope="col" class="px-4 py-3">Payment</th>
                            <th scope="col" class="px-4 py-3">Recorded by</th>
                            <th scope="col" class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($expenses as $expense)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $expense->dailyCashRecord?->business_date?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="max-w-md px-4 py-3">
                                    <p class="font-semibold text-slate-950">
                                        {{ ucwords(str_replace('_', ' ', $expense->category)) }}
                                    </p>
                                    <p class="mt-1 whitespace-pre-line break-words text-sm leading-5 text-slate-600">
                                        {{ $expense->description }}
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold uppercase text-blue-700">
                                        {{ $expense->payment_method }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $expense->recordedBy?->name ?? 'Unknown' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-slate-950">
                                    ₦{{ number_format((float) $expense->amount, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="border-t border-slate-100 px-4 py-4 sm:px-6">
                {{ $expenses->links() }}
            </div>
        @endif
    </section>
</div>
@endsection