<div data-sales-results>
    <div data-list-total="{{ $sales->total() }}" hidden></div>

@php
    $money = fn ($value) => '₦'.number_format((float) $value, 2);

    $qty = fn ($value) => str_contains((string) $value, '.')
        ? rtrim(rtrim((string) $value, '0'), '.')
        : (string) $value;

    $photoUrl = fn ($stockItem, $photo) => route('stock.items.photos.show', [
        'stockItem' => $stockItem,
        'photo' => $photo,
    ]);

    $methodLabels = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'pos' => 'POS',
        'other' => 'Other',
    ];

    $statusStyles = [
        'paid' => ['Paid', 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'],
        'part' => ['Part-paid', 'bg-amber-50 text-amber-700 ring-amber-600/20'],
        'unpaid' => ['Unpaid', 'bg-rose-50 text-rose-700 ring-rose-600/20'],
    ];

    // Exact decimal arithmetic: float subtraction can leave a phantom balance
    // after several part-payments.
    $summaries = $sales->getCollection()->mapWithKeys(function ($sale) {
        $total = number_format((float) $sale->total_amount, 2, '.', '');
        $paid = number_format((float) ($sale->payments_sum_amount ?? 0), 2, '.', '');
        $balance = bcsub($total, $paid, 2);

        if (bccomp($balance, '0', 2) <= 0) {
            $balance = '0.00';
        }

        $status = bccomp($balance, '0', 2) === 0
            ? 'paid'
            : (bccomp($paid, '0', 2) > 0 ? 'part' : 'unpaid');

        return [$sale->id => compact('total', 'paid', 'balance', 'status')];
    });
@endphp

    {{-- List --}}

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($sales->isEmpty())
            <div class="px-6 py-16 text-center">
                <h2 class="font-semibold text-slate-900">No sales found</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $outstandingOnly
                        ? 'There are no sales with an outstanding balance at this store.'
                        : 'Sales recorded at this store will appear here.' }}
                </p>
            </div>
        @else
    {{-- Mobile sales cards --}}
    <div data-mobile-sales-list class="space-y-2 md:hidden">
        @foreach ($sales as $sale)
            @php
                $s = $summaries[$sale->id];
                $firstLine = $sale->items->first();
                $firstItem = $firstLine?->stockItem;
                $firstPhoto = $firstItem?->photos?->first();
            @endphp

            <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                {{-- Sale identity + payment status --}}
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                    <button
                        type="button"
                        data-sale-open="{{ $sale->id }}"
                        class="min-w-0 truncate text-sm font-bold text-slate-950 hover:text-indigo-700"
                    >{{ $sale->sale_number }}</button>

                    <span class="inline-flex shrink-0 rounded-full px-2 py-1 text-[10px] font-semibold ring-1 ring-inset {{ $statusStyles[$s['status']][1] }}">
                        {{ $statusStyles[$s['status']][0] }}
                    </span>
                </div>

                {{-- Product preview --}}
                <button
                    type="button"
                    data-sale-open="{{ $sale->id }}"
                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-slate-50"
                >
                    @if ($firstItem && $firstPhoto)
                        <img
                            src="{{ $photoUrl($firstItem, $firstPhoto) }}"
                            alt=""
                            width="40"
                            height="40"
                            loading="lazy"
                            decoding="async"
                            class="h-12 w-12 shrink-0 rounded-lg border border-slate-200 object-cover"
                        >
                    @else
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-slate-400" aria-hidden="true">
                            <x-ui.icon name="cube" class="h-4 w-4" />
                        </span>
                    @endif

                    <span class="min-w-0 flex-1">
                        @if ($sale->items->count() === 1)
                            <span class="block truncate text-[11px] font-medium text-slate-800">{{ $firstItem?->name ?? 'Stock item' }}</span>
                            <span class="block text-[10px] text-slate-500">Qty {{ $qty($firstLine->quantity) }}</span>
                        @else
                            <span class="block text-[11px] font-medium text-slate-800">{{ $sale->items->count() }} items</span>
                            <span class="block truncate text-[10px] text-slate-500">
                                @foreach ($sale->items->take(2) as $previewLine)
                                    {{ $previewLine->stockItem?->name ?? 'Stock item' }}@if (!$loop->last) · @endif
                                @endforeach
                                @if ($sale->items->count() > 2)
                                    +{{ $sale->items->count() - 2 }} more
                                @endif
                            </span>
                        @endif
                    </span>

                    <span class="shrink-0 text-[10px] font-medium text-indigo-600">View</span>
                </button>

                {{-- Customer and date --}}
                <div class="grid grid-cols-2 gap-3 border-t border-slate-100 px-4 py-3">
                    <div class="min-w-0">
                        <p class="text-[9px] font-medium uppercase tracking-wide text-slate-400">Customer</p>
                        <p class="truncate text-[10px] font-medium text-slate-700">{{ $sale->customer_name ?: 'Walk-in customer' }}</p>
                        @if ($sale->customer_phone)
                            <p class="truncate text-[9px] text-slate-500">{{ $sale->customer_phone }}</p>
                        @endif
                    </div>

                    <div class="min-w-0 text-right">
                        <p class="text-[9px] font-medium uppercase tracking-wide text-slate-400">Date</p>
                        <p class="text-[10px] font-medium text-slate-700">{{ $sale->sold_at?->format('d M Y') }}</p>
                        <p class="text-[9px] text-slate-500">{{ $sale->sold_at?->format('h:i A') }}</p>
                    </div>
                </div>

                {{-- Financial summary --}}
                <div class="grid grid-cols-2 border-t border-slate-200 bg-slate-50">
                    <div class="px-4 py-3">
                        <p class="text-[9px] font-medium uppercase tracking-wide text-slate-400">Sale total</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-800">{{ $money($s['total']) }}</p>
                        <p class="text-[9px] text-slate-500">Paid {{ $money($s['paid']) }}</p>
                    </div>

                    <div class="border-l border-slate-200 px-4 py-3 text-right">
                        <p class="text-[9px] font-medium uppercase tracking-wide text-slate-400">Balance due</p>
                        <p class="mt-0.5 text-xs font-semibold {{ $s['status'] === 'paid' ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $money($s['balance']) }}
                        </p>
                        <button
                            type="button"
                            data-sale-open="{{ $sale->id }}"
                            class="mt-0.5 text-[10px] font-medium text-indigo-600 hover:text-indigo-800"
                        >View details / payment</button>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

            <div class="hidden overflow-x-auto md:block" data-desktop-sales-table>
                <table class="w-full min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="hidden bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 md:table-header-group">
                        <tr>
                            <th scope="col" class="px-4 py-3">Sale</th>
                            <th scope="col" class="px-4 py-3">Customer</th>
                            <th scope="col" class="px-4 py-3">Date</th>
                            <th scope="col" class="px-4 py-3 text-right">Total</th>
                            <th scope="col" class="hidden px-4 py-3 text-right lg:table-cell">Paid</th>
                            <th scope="col" class="px-4 py-3 text-right">Balance</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($sales as $sale)
                            @php
                                $s = $summaries[$sale->id];
                                $firstLine = $sale->items->first();
                                $firstItem = $firstLine?->stockItem;
                                $firstPhoto = $firstItem?->photos?->first();
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50">
                                {{-- Sale --}}
                                <td class="min-w-[13rem] px-4 py-3"> <div class="flex items-center gap-3"> @if ($firstItem && $firstPhoto) <img src="{{ $photoUrl($firstItem, $firstPhoto) }}" alt="" width="40" height="40" loading="lazy" decoding="async" class="h-12 w-12 shrink-0 rounded-lg border border-slate-200 object-cover" > @else <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-slate-400" aria-hidden="true"> <x-ui.icon name="cube" class="h-5 w-5" /> </div> @endif <div class="min-w-0"> <button type="button" data-sale-open="{{ $sale->id }}" class="inline-block whitespace-nowrap text-sm font-semibold text-slate-900 hover:text-indigo-700 focus:outline-none focus-visible:underline" >{{ $sale->sale_number }}</button> <p class="text-[11px] text-slate-400 ">{{ $sale->sold_at?->format('d M Y, h:i A') }}</p> @if ($sale->items->count() > 1) <details class="group mt-0.5" data-sale-items-toggle> <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-[11px] font-medium text-slate-500 hover:text-slate-800 sm:text-xs [&::-webkit-details-marker]:hidden"> {{ $sale->items->count() }} items <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3 w-3 transition-transform group-open:rotate-180" aria-hidden="true"> <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /> </svg> </summary> <ul class="mt-1.5 max-w-[16rem] space-y-1 text-[11px] text-slate-600 sm:text-xs"> @foreach ($sale->items->take(3) as $previewLine) <li class="truncate">{{ $previewLine->stockItem?->name ?? 'Stock item' }}</li> @endforeach @if ($sale->items->count() > 3) <li> <button type="button" data-sale-open="{{ $sale->id }}" class="font-medium text-indigo-600 hover:text-indigo-800" >+ {{ $sale->items->count() - 3 }} more</button> </li> @endif </ul> </details> @else <p class="max-w-[14rem] truncate text-[11px] text-slate-500 sm:text-xs">{{ $firstItem?->name ?? 'No items' }}</p> @endif </div> </div> </td>

                                {{-- Customer --}}
                                <td class="min-w-0 px-4 py-3">
                                    <span class="block text-[10px] font-medium uppercase tracking-wide text-slate-400 md:hidden">Customer</span>
                                    <span class="block truncate font-medium text-slate-800">{{ $sale->customer_name ?: 'Walk-in customer' }}</span>
                                    @if ($sale->customer_phone)
                                        <span class="block text-[11px] text-slate-500 sm:text-xs">{{ $sale->customer_phone }}</span>
                                    @endif
                                </td>

                                {{-- Date (tablet and up) --}}
                                <td class="hidden whitespace-nowrap text-slate-600 md:table-cell md:px-4 md:py-3">
                                    {{ $sale->sold_at?->format('d M Y') }}
                                    <span class="block text-xs text-slate-400">{{ $sale->sold_at?->format('h:i A') }}</span>
                                </td>

                                {{-- Total --}}
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <span class="block text-[10px] font-medium uppercase tracking-wide text-slate-400 md:hidden">Total</span>
                                    <span class="text-xs font-medium text-slate-800 md:font-normal md:text-slate-700">{{ $money($s['total']) }}</span>
                                </td>

                                {{-- Paid (desktop) --}}
                                <td class="hidden whitespace-nowrap text-right text-xs text-slate-700 lg:table-cell lg:px-4 lg:py-3">{{ $money($s['paid']) }}</td>

                                {{-- Balance --}}
                                <td class="whitespace-nowrap border-t border-slate-100 px-4 py-3 text-right">
                                    <span class="block text-xs font-semibold {{ $s['status'] === 'paid' ? 'text-slate-400' : 'text-slate-900' }}">
                                        <span class="mr-1 text-[10px] font-medium uppercase tracking-wide text-slate-400 md:hidden">Balance</span>{{ $money($s['balance']) }}
                                    </span>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset sm:text-xs md:mt-1 {{ $statusStyles[$s['status']][1] }}">
                                        {{ $statusStyles[$s['status']][0] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($sales->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $sales->links() }}
                </div>
            @endif
        @endif
    </section>

{{-- Sale details: one dialog per sale, outside the table --}}
@foreach ($sales as $sale)
    @php
        $s = $summaries[$sale->id];
        $paymentFailed = $errors->any() && (int) old('_sale') === $sale->id;
    @endphp

    <dialog
        id="sale-{{ $sale->id }}"
        data-sale-dialog
        @if ($paymentFailed) data-open-on-load @endif
        aria-labelledby="sale-{{ $sale->id }}-title"
        class="m-auto w-[calc(100vw-1.5rem)] rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/50 sm:w-[34rem] lg:w-1/2 lg:max-w-2xl"
    >
        <div class="flex max-h-[85dvh] flex-col">

            {{-- Header (fixed) --}}
            <header class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                <div class="min-w-0">
                    <h2 id="sale-{{ $sale->id }}-title" class="text-sm font-semibold sm:text-base">{{ $sale->sale_number }}</h2>
                    <p class="mt-0.5 truncate text-xs text-slate-600 sm:text-sm">
                        {{ $sale->customer_name ?: 'Walk-in customer' }}@if ($sale->customer_phone) · {{ $sale->customer_phone }}@endif
                    </p>
                    <p class="text-xs text-slate-400">{{ $sale->sold_at?->format('d M Y, h:i A') }}</p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusStyles[$s['status']][1] }}">
                        {{ $statusStyles[$s['status']][0] }}
                    </span>
                    <button
                        type="button"
                        data-sale-close
                        aria-label="Close"
                        class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </header>

            {{-- Body (scrolls) --}}
            <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain px-5 py-5 sm:px-6 sm:py-6">

                {{-- Items --}}
                <section>
                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs">Items</h3>

                    @if ($sale->items->isEmpty())
                        <p class="mt-2 text-xs text-slate-500 sm:text-sm">No item details are available for this sale.</p>
                    @else
                        <ul class="mt-1 divide-y divide-slate-100">
                            @foreach ($sale->items as $line)
                                @php
                                    $lineItem = $line->stockItem;
                                    $linePhotos = $lineItem?->photos ?? collect();
                                @endphp
                                <li class="py-3">
                                    <p class="text-[13px] font-medium leading-snug text-slate-900 sm:text-sm">{{ $lineItem?->name ?? 'Stock item' }}</p>

                                    @if ($lineItem && $linePhotos->isNotEmpty())
                                        <details class="group mt-1" data-photo-toggle>
                                            <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-[11px] font-medium text-indigo-600 hover:text-indigo-800 sm:text-xs [&::-webkit-details-marker]:hidden">
                                                {{ $linePhotos->count() }} {{ $linePhotos->count() === 1 ? 'photo' : 'photos' }}
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3 w-3 transition-transform group-open:rotate-180" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </summary>

                                            {{-- One horizontal row: scrolls sideways instead of wrapping. --}}
                                            <div data-photo-strip class="-mx-1 flex flex-nowrap gap-2 overflow-x-auto overscroll-x-contain px-1 pb-1 pt-2">
                                                @foreach ($linePhotos as $photo)
                                                    <a
                                                        href="{{ $photoUrl($lineItem, $photo) }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        title="Open photo"
                                                        class="shrink-0"
                                                    >
                                                        <img
                                                            src="{{ $photoUrl($lineItem, $photo) }}"
                                                            alt="{{ $lineItem->name }} photo {{ $loop->iteration }}"
                                                            width="44"
                                                            height="44"
                                                            loading="lazy"
                                                            decoding="async"
                                                            class="h-11 w-11 rounded-full border border-slate-200 object-cover sm:h-12 sm:w-12"
                                                        >
                                                    </a>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <p class="mt-0.5 text-[11px] text-slate-400 sm:text-xs">No photos</p>
                                    @endif

                                    <dl class="mt-2 grid grid-cols-3 gap-2 text-[11px] sm:text-xs">
                                        <div>
                                            <dt class="text-slate-500">Qty</dt>
                                            <dd class="mt-0.5 font-medium text-slate-800">{{ $qty($line->quantity) }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-slate-500">Unit price</dt>
                                            <dd class="mt-0.5 font-medium text-slate-800">{{ $money($line->unit_selling_price) }}</dd>
                                        </div>
                                        <div class="text-right">
                                            <dt class="text-slate-500">Line total</dt>
                                            <dd class="mt-0.5 font-semibold text-slate-900">{{ $money($line->line_total) }}</dd>
                                        </div>
                                    </dl>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Totals --}}
                <dl class="ml-auto w-full max-w-xs space-y-1.5 text-xs sm:text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Total</dt>
                        <dd class="font-medium">{{ $money($s['total']) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Paid</dt>
                        <dd class="font-medium text-emerald-700">{{ $money($s['paid']) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-1.5">
                        <dt class="font-medium">Balance</dt>
                        <dd class="font-semibold {{ $s['status'] === 'paid' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $money($s['balance']) }}</dd>
                    </div>
                </dl>

                {{-- Payment history --}}
                <section>
                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs">Payments</h3>

                    @forelse ($sale->payments->sortByDesc('paid_at') as $payment)
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 py-2.5 text-xs last:border-b-0 sm:text-sm">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900">
                                    {{ $money($payment->amount) }}
                                    <span class="font-normal text-slate-500">· {{ $methodLabels[$payment->payment_method] ?? ucfirst(str_replace('_', ' ', (string) $payment->payment_method)) }}</span>
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $payment->paid_at?->format('d M Y, h:i A') }}@if ($payment->receiver?->name) · {{ $payment->receiver->name }}@endif
                                </p>
                                @if ($payment->reference)
                                    <p class="text-xs text-slate-500">Ref: {{ $payment->reference }}</p>
                                @endif
                            </div>

                            @if ((float) $payment->tip_amount > 0)
                                <p class="shrink-0 text-xs text-slate-500">Tip {{ $money($payment->tip_amount) }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">No payments recorded yet.</p>
                    @endforelse
                </section>

                {{-- Record payment --}}
                @if ($s['status'] === 'paid')
                    <p class="text-xs font-medium text-emerald-700 sm:text-sm">This sale has been paid in full.</p>
                @else
                    <section class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <h3 class="text-sm font-semibold text-slate-900">Record payment</h3>

                        <form
                            method="POST"
                            action="{{ route('stores.sales.payments.store', ['store' => $store, 'sale' => $sale]) }}"
                            class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2"
                        >
                            @csrf
                            <input type="hidden" name="_sale" value="{{ $sale->id }}">

                            @if ($paymentFailed)
                                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 sm:col-span-2">
                                    @foreach ($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif

                            <div>
                                <div class="flex items-baseline justify-between">
                                    <label for="pay-amount-{{ $sale->id }}" class="block text-xs font-medium text-slate-700 sm:text-sm">Amount (₦)</label>
                                    <button
                                        type="button"
                                        data-fill-amount="{{ $s['balance'] }}"
                                        data-fill-target="pay-amount-{{ $sale->id }}"
                                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                                    >Fill balance</button>
                                </div>
                                <input
                                    id="pay-amount-{{ $sale->id }}"
                                    name="amount"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    required
                                    inputmode="decimal"
                                    value="{{ $paymentFailed ? old('amount') : '' }}"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                                >
                            </div>

                            <div>
                                <label for="pay-method-{{ $sale->id }}" class="block text-xs font-medium text-slate-700 sm:text-sm">Method</label>
                                <select
                                    id="pay-method-{{ $sale->id }}"
                                    name="payment_method"
                                    required
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                                >
                                    @foreach ($methodLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($paymentFailed && old('payment_method') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="pay-ref-{{ $sale->id }}" class="block text-xs font-medium text-slate-700 sm:text-sm">
                                    Reference <span class="font-normal text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="pay-ref-{{ $sale->id }}"
                                    name="reference"
                                    maxlength="120"
                                    value="{{ $paymentFailed ? old('reference') : '' }}"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                                >
                            </div>

                            <div>
                                <label for="pay-notes-{{ $sale->id }}" class="block text-xs font-medium text-slate-700 sm:text-sm">
                                    Notes <span class="font-normal text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="pay-notes-{{ $sale->id }}"
                                    name="notes"
                                    value="{{ $paymentFailed ? old('notes') : '' }}"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                                >
                            </div>

                            <div class="sm:col-span-2">
                                <button type="submit" class="oy-btn oy-btn-primary">Record payment</button>
                            </div>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </dialog>
@endforeach


</div>
