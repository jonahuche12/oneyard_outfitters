@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Page header --}}
    <header class="space-y-4">
        <div>
            <a href="{{ route('stores.show', $store) }}"
               class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900">
                ← {{ $store->name }}
            </a>

            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                Store expenses
            </h1>

            <p class="mt-1 text-sm leading-6 text-slate-500">
                Record store expenses and review previous entries.
            </p>
        </div>

        <div>
            @if ($canRecordExpenses)
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Business day open · Expenses enabled
                </span>
            @elseif ($dailyCashToday)
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                    <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                    Business day closed
                </span>
            @else
                <span class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Opening balances not recorded
                </span>
            @endif
        </div>
    </header>

    {{-- Feedback --}}
    @if (session('success'))
        <div role="status"
             class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div role="alert"
             class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
            {{ session('warning') }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert"
             class="rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800">
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
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-slate-900">Record an expense</h2>
                <p class="mt-1 text-sm leading-5 text-slate-500">
                    Entries are attached to today's business day and cannot be edited or deleted after saving.
                </p>
            </div>

            <form method="POST"
                  action="{{ route('stores.expenses.store', $store) }}"
                  class="space-y-5 p-4 sm:p-6">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Amount --}}
                    <div>
                        <label for="amount" class="mb-1.5 block text-sm font-medium text-slate-700">
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
                            class="oy-input w-full"
                            @if ($errors->has('amount')) aria-invalid="true" @endif
                        >
                        @error('amount')
                            <p class="oy-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label for="category" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Expense category <span class="text-red-600">*</span>
                        </label>
                        <select
                            id="category"
                            name="category"
                            required
                            class="oy-select w-full"
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
                            <p class="oy-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Payment method --}}
                    <div>
                        <label for="payment_method" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Payment method <span class="text-red-600">*</span>
                        </label>
                        <select
                            id="payment_method"
                            name="payment_method"
                            required
                            class="oy-select w-full"
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
                            <p class="oy-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="sm:col-span-2">
                        <label for="description" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Description <span class="text-red-600">*</span>
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            required
                            maxlength="2000"
                            placeholder="Briefly describe what the money was spent on."
                            class="oy-textarea w-full"
                            @if ($errors->has('description')) aria-invalid="true" @endif
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="oy-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4">
                    <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">
                        Save expense
                    </button>
                    <p class="mt-2 text-xs leading-5 text-slate-500">
                        Review the amount and details before saving. Saved entries are permanent.
                    </p>
                </div>
            </form>
        </section>
    @else
        {{-- Recording unavailable --}}
        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
            <h2 class="font-semibold text-slate-900">Expense recording unavailable</h2>
            <p class="mt-1 text-sm leading-6 text-slate-600">
                @if ($dailyCashToday)
                    Today's business day has been closed. Expenses can no longer be recorded for it.
                @else
                    Record today's opening cash and bank balances on the Store page before recording expenses.
                @endif
            </p>
            <a href="{{ route('stores.show', $store) }}"
               class="oy-btn oy-btn-secondary mt-4 w-full justify-center sm:w-auto">
                Return to store
            </a>
        </section>
    @endif

    {{-- Expense history --}}
    <section class="space-y-4">
        <header class="flex flex-col gap-1">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Expense history</h2>
            <p class="text-sm leading-5 text-slate-500">
                All expenses recorded for this store, including goods purchases and debt repayments.
            </p>
        </header>

        @if ($expenses->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-white px-5 py-10 text-center shadow-sm sm:px-8 sm:py-12">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                    <span class="text-xl" aria-hidden="true">₦</span>
                </div>
                <h3 class="mt-3 font-semibold text-slate-900">No expenses recorded yet</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Saved expenses will appear here.
                </p>
            </div>
        @else
            {{-- Mobile-first history cards --}}
            <div class="space-y-3 md:hidden">
                @foreach ($expenses as $expense)
                    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold leading-5 text-slate-900">
                                    {{ ucwords(str_replace('_', ' ', $expense->category)) }}
                                </h3>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $expense->dailyCashRecord?->business_date?->format('d M Y') ?? '—' }}
                                </p>
                            </div>
                            <p class="shrink-0 text-right text-base font-semibold tabular-nums text-slate-900">
                                ₦{{ number_format((float) $expense->amount, 2) }}
                            </p>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium uppercase tracking-wide text-slate-700">
                                {{ $expense->payment_method }}
                            </span>
                        </div>

                        <p class="mt-3 whitespace-pre-line break-words text-sm leading-6 text-slate-700">
                            {{ $expense->description }}
                        </p>

                        <div class="mt-3 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">
                            <p>Recorded by {{ $expense->recordedBy?->name ?? 'Unknown' }}</p>
                            <p>{{ $expense->created_at?->format('d M Y, g:i a') }}</p>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop history table --}}
            <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Category / Description</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Payment</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Recorded by</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($expenses as $expense)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                        {{ $expense->dailyCashRecord?->business_date?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td class="max-w-md px-5 py-4">
                                        <p class="font-medium text-slate-900">
                                            {{ ucwords(str_replace('_', ' ', $expense->category)) }}
                                        </p>
                                        <p class="mt-1 whitespace-pre-line break-words text-sm leading-5 text-slate-600">
                                            {{ $expense->description }}
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm uppercase text-slate-600">
                                        {{ $expense->payment_method }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                        {{ $expense->recordedBy?->name ?? 'Unknown' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold tabular-nums text-slate-900">
                                        ₦{{ number_format((float) $expense->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            <div class="px-1 py-3">
                {{ $expenses->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
