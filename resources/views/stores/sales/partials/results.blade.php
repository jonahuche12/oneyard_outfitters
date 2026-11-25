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

    // [label, pill classes, dot classes]
    $statusStyles = [
        'paid' => ['Paid', 'bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
        'part' => ['Part-paid', 'bg-amber-50 text-amber-700 ring-amber-600/20', 'bg-amber-500'],
        'unpaid' => ['Unpaid', 'bg-red-50 text-red-700 ring-red-600/20', 'bg-red-500'],
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

        {{-- Section header --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 sm:h-10 sm:w-10">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/>
                        <path d="M9 8h6M9 12h6"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Sales</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:mt-1 sm:text-sm">
                        {{ $outstandingOnly ? 'Sales with an outstanding balance at this store.' : 'Sales recorded at this store.' }}
                    </p>
                </div>
            </div>

            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 sm:px-3 sm:py-1.5 sm:text-xs">
                {{ $sales->total() }} {{ $sales->total() === 1 ? 'sale' : 'sales' }}
            </span>
        </div>

        @if ($sales->isEmpty())
            <div class="m-4 flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center sm:m-6">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/>
                        <path d="M9 8h6M9 12h6"/>
                    </svg>
                </span>
                <h2 class="mt-3 text-sm font-semibold text-slate-800">No sales found</h2>
                <p class="mt-1 text-[11px] text-slate-500 sm:text-xs">
                    {{ $outstandingOnly
                        ? 'There are no sales with an outstanding balance at this store.'
                        : 'Sales recorded at this store will appear here.' }}
                </p>
            </div>
        @else

            {{-- Mobile sales cards (compact) --}}
            <div data-mobile-sales-list class="space-y-2.5 p-3 md:hidden">
                @foreach ($sales as $sale)
                    @php
                        $s = $summaries[$sale->id];
                        $firstLine = $sale->items->first();
                        $firstItem = $firstLine?->stockItem;
                        $firstPhoto = $firstItem?->photos?->first();
                    @endphp

                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        {{-- Sale identity + payment status --}}
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/80 px-3 py-2">
                            <button
                                type="button"
                                data-sale-open="{{ $sale->id }}"
                                class="min-w-0 truncate text-left text-xs font-bold text-slate-950 transition hover:text-indigo-700 focus:outline-none focus-visible:underline"
                            >{{ $sale->sale_number }}</button>

                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $statusStyles[$s['status']][1] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusStyles[$s['status']][2] }}"></span>
                                {{ $statusStyles[$s['status']][0] }}
                            </span>
                        </div>

                        {{-- Product preview --}}
                        <button
                            type="button"
                            data-sale-open="{{ $sale->id }}"
                            class="flex w-full items-center gap-2.5 px-3 py-2 text-left transition hover:bg-slate-50"
                        >
                            @if ($firstItem && $firstPhoto)
                                <img
                                    src="{{ $photoUrl($firstItem, $firstPhoto) }}"
                                    alt=""
                                    width="40"
                                    height="40"
                                    loading="lazy"
                                    decoding="async"
                                    class="h-10 w-10 shrink-0 rounded-lg border border-slate-200 object-cover"
                                >
                            @else
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-400" aria-hidden="true">
                                    <x-ui.icon name="cube" class="h-4 w-4" />
                                </span>
                            @endif

                            <span class="min-w-0 flex-1">
                                @if ($sale->items->count() === 1)
                                    <span class="block truncate text-xs font-semibold text-slate-900">{{ $firstItem?->name ?? 'Stock item' }}</span>
                                    <span class="block text-[11px] text-slate-500">Qty {{ $qty($firstLine->quantity) }}</span>
                                @else
                                    <span class="block text-xs font-semibold text-slate-900">{{ $sale->items->count() }} items</span>
                                    <span class="block truncate text-[11px] text-slate-500">
                                        @foreach ($sale->items->take(2) as $previewLine)
                                            {{ $previewLine->stockItem?->name ?? 'Stock item' }}@if (!$loop->last) · @endif
                                        @endforeach
                                        @if ($sale->items->count() > 2)
                                            +{{ $sale->items->count() - 2 }} more
                                        @endif
                                    </span>
                                @endif
                            </span>

                            <span class="inline-flex shrink-0 items-center gap-0.5 text-[11px] font-semibold text-blue-700">
                                View
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </span>
                        </button>

                        {{-- Customer and date --}}
                        <div class="grid grid-cols-2 gap-2 border-t border-slate-100 px-3 py-2">
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Customer</p>
                                <p class="mt-0.5 truncate text-[11px] font-semibold text-slate-900">{{ $sale->customer_name ?: 'Walk-in customer' }}</p>
                                @if ($sale->customer_phone)
                                    <p class="truncate text-[10px] text-slate-500">{{ $sale->customer_phone }}</p>
                                @endif
                            </div>

                            <div class="min-w-0 text-right">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Date</p>
                                <p class="mt-0.5 text-[11px] font-semibold text-slate-900">{{ $sale->sold_at?->format('d M Y') }}</p>
                                <p class="text-[10px] text-slate-500">{{ $sale->sold_at?->format('h:i A') }}</p>
                            </div>
                        </div>

                        {{-- Financial summary --}}
                        <div class="grid grid-cols-2 gap-2 border-t border-slate-100 bg-slate-50/70 p-2.5">
                            <div class="min-w-0 rounded-lg border border-slate-200 bg-white px-2.5 py-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Sale total</p>
                                <p class="mt-1 break-words text-xs font-bold tabular-nums text-slate-950">{{ $money($s['total']) }}</p>
                                <p class="break-words text-[10px] tabular-nums text-slate-500">Paid {{ $money($s['paid']) }}</p>
                            </div>

                            <div class="min-w-0 rounded-lg border px-2.5 py-2 {{ $s['status'] === 'paid' ? 'border-emerald-100 bg-gradient-to-br from-emerald-50 to-white' : 'border-amber-100 bg-gradient-to-br from-amber-50 to-white' }}">
                                <p class="text-[10px] font-semibold uppercase tracking-wide {{ $s['status'] === 'paid' ? 'text-emerald-800' : 'text-amber-800' }}">Balance due</p>
                                <p class="mt-1 break-words text-xs font-bold tabular-nums {{ $s['status'] === 'paid' ? 'text-emerald-950' : 'text-amber-950' }}">
                                    {{ $money($s['balance']) }}
                                </p>
                            </div>

                            <button
                                type="button"
                                data-sale-open="{{ $sale->id }}"
                                class="col-span-2 inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                View details / payment
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop sales table --}}
            <div class="hidden overflow-x-auto md:block" data-desktop-sales-table>
                <table class="w-full min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
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
                            <tr class="transition-colors hover:bg-slate-50/80">
                                {{-- Sale --}}
                                <td class="min-w-[13rem] px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($firstItem && $firstPhoto)
                                            <img
                                                src="{{ $photoUrl($firstItem, $firstPhoto) }}"
                                                alt=""
                                                width="48"
                                                height="48"
                                                loading="lazy"
                                                decoding="async"
                                                class="h-12 w-12 shrink-0 rounded-xl border border-slate-200 object-cover"
                                            >
                                        @else
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-400" aria-hidden="true">
                                                <x-ui.icon name="cube" class="h-5 w-5" />
                                            </div>
                                        @endif

                                        <div class="min-w-0">
                                            <button
                                                type="button"
                                                data-sale-open="{{ $sale->id }}"
                                                class="inline-block whitespace-nowrap text-sm font-bold text-slate-950 transition hover:text-indigo-700 focus:outline-none focus-visible:underline"
                                            >{{ $sale->sale_number }}</button>

                                            @if ($sale->items->count() > 1)
                                                <details class="group mt-0.5" data-sale-items-toggle>
                                                    <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-800 [&::-webkit-details-marker]:hidden">
                                                        {{ $sale->items->count() }} items
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3 w-3 transition-transform group-open:rotate-180" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                        </svg>
                                                    </summary>

                                                    <ul class="mt-1.5 max-w-[16rem] space-y-1 text-xs text-slate-600">
                                                        @foreach ($sale->items->take(3) as $previewLine)
                                                            <li class="truncate">{{ $previewLine->stockItem?->name ?? 'Stock item' }}</li>
                                                        @endforeach
                                                        @if ($sale->items->count() > 3)
                                                            <li>
                                                                <button
                                                                    type="button"
                                                                    data-sale-open="{{ $sale->id }}"
                                                                    class="font-semibold text-blue-700 hover:text-blue-800"
                                                                >+ {{ $sale->items->count() - 3 }} more</button>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </details>
                                            @else
                                                <p class="max-w-[14rem] truncate text-xs text-slate-500">{{ $firstItem?->name ?? 'No items' }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Customer --}}
                                <td class="min-w-0 px-4 py-3">
                                    <span class="block truncate font-semibold text-slate-900">{{ $sale->customer_name ?: 'Walk-in customer' }}</span>
                                    @if ($sale->customer_phone)
                                        <span class="block text-xs text-slate-500">{{ $sale->customer_phone }}</span>
                                    @endif
                                </td>

                                {{-- Date --}}
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                    {{ $sale->sold_at?->format('d M Y') }}
                                    <span class="block text-xs text-slate-500">{{ $sale->sold_at?->format('h:i A') }}</span>
                                </td>

                                {{-- Total --}}
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-slate-900">
                                    {{ $money($s['total']) }}
                                </td>

                                {{-- Paid (desktop) --}}
                                <td class="hidden whitespace-nowrap px-4 py-3 text-right tabular-nums text-slate-600 lg:table-cell">
                                    {{ $money($s['paid']) }}
                                </td>

                                {{-- Balance --}}
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <span class="block font-semibold tabular-nums {{ $s['status'] === 'paid' ? 'text-slate-400' : 'text-slate-950' }}">
                                        {{ $money($s['balance']) }}
                                    </span>
                                    <span class="mt-1 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $statusStyles[$s['status']][1] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $statusStyles[$s['status']][2] }}"></span>
                                        {{ $statusStyles[$s['status']][0] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($sales->hasPages())
                <div class="border-t border-slate-100 px-4 py-4 sm:px-6">
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
        class="m-auto w-[calc(100%-2rem)] max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60"
    >
        <div class="flex max-h-[90dvh] flex-col">

            {{-- Header (fixed) --}}
            <header class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-4 py-3 sm:px-6 sm:py-5">
                <div class="min-w-0">
                    <h2 id="sale-{{ $sale->id }}-title" class="break-words text-base font-bold tracking-tight text-slate-950 sm:text-lg">{{ $sale->sale_number }}</h2>
                    <p class="mt-0.5 text-xs leading-5 text-slate-600 sm:mt-1 sm:text-sm sm:leading-6">
                        {{ $sale->customer_name ?: 'Walk-in customer' }}@if ($sale->customer_phone) · {{ $sale->customer_phone }}@endif
                    </p>
                    <p class="text-[11px] text-slate-500 sm:text-xs">{{ $sale->sold_at?->format('d M Y, h:i A') }}</p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset sm:px-2.5 sm:py-1 sm:text-xs {{ $statusStyles[$s['status']][1] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $statusStyles[$s['status']][2] }}"></span>
                        {{ $statusStyles[$s['status']][0] }}
                    </span>
                    <button
                        type="button"
                        data-sale-close
                        aria-label="Close"
                        class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-500 sm:h-10 sm:w-10 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </header>

            {{-- Body (scrolls) --}}
            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain px-4 py-4 sm:space-y-6 sm:px-6 sm:py-6">

                {{-- Items --}}
                <section>
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-700 sm:h-8 sm:w-8">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/>
                                <path d="m3.5 7.7 8.5 4.5 8.5-4.5M12 12.2V21"/>
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Items</h3>
                    </div>

                    @if ($sale->items->isEmpty())
                        <p class="mt-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">No item details are available for this sale.</p>
                    @else
                        <ul class="mt-3 space-y-3">
                            @foreach ($sale->items as $line)
                                @php
                                    $lineItem = $line->stockItem;
                                    $linePhotos = $lineItem?->photos ?? collect();
                                @endphp
                                <li class="rounded-xl border border-slate-100 bg-slate-50/80 p-3 sm:p-4">
                                    <p class="break-words text-[13px] font-semibold leading-snug sm:text-sm text-slate-950">{{ $lineItem?->name ?? 'Stock item' }}</p>

                                    @if ($lineItem && $linePhotos->isNotEmpty())
                                        <details class="group mt-1.5" data-photo-toggle>
                                            <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-[11px] font-semibold text-blue-700 sm:text-xs hover:text-blue-800 [&::-webkit-details-marker]:hidden">
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
                                                            width="48"
                                                            height="48"
                                                            loading="lazy"
                                                            decoding="async"
                                                            class="h-10 w-10 rounded-lg border border-slate-200 object-cover transition sm:h-12 sm:w-12 sm:rounded-xl hover:border-slate-300 hover:shadow-md"
                                                        >
                                                    </a>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <p class="mt-1 text-[11px] text-slate-500 sm:text-xs">No photos</p>
                                    @endif

                                    <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-slate-200/70 pt-3">
                                        <div class="min-w-0">
                                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-[11px]">Qty</dt>
                                            <dd class="mt-1 text-xs font-semibold tabular-nums text-slate-900 sm:text-sm">{{ $qty($line->quantity) }}</dd>
                                        </div>
                                        <div class="min-w-0">
                                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-[11px]">Unit price</dt>
                                            <dd class="mt-1 break-words text-xs font-semibold tabular-nums text-slate-900 sm:text-sm">{{ $money($line->unit_selling_price) }}</dd>
                                        </div>
                                        <div class="min-w-0 text-right">
                                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-[11px]">Line total</dt>
                                            <dd class="mt-1 break-words text-xs font-bold tabular-nums text-slate-950 sm:text-sm">{{ $money($line->line_total) }}</dd>
                                        </div>
                                    </dl>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Totals --}}
                <dl class="grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-3">
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-2.5 sm:block sm:p-4">
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs">Total</dt>
                        <dd class="break-words text-xs font-bold tabular-nums text-slate-950 sm:mt-2 sm:text-base">{{ $money($s['total']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-2.5 sm:block sm:p-4">
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-emerald-800 sm:text-xs">Paid</dt>
                        <dd class="break-words text-xs font-bold tabular-nums text-emerald-950 sm:mt-2 sm:text-base">{{ $money($s['paid']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-xl border p-2.5 sm:block sm:p-4 {{ $s['status'] === 'paid' ? 'border-emerald-100 bg-gradient-to-br from-emerald-50 to-white' : 'border-amber-100 bg-gradient-to-br from-amber-50 to-white' }}">
                        <dt class="text-[10px] font-semibold uppercase tracking-wide sm:text-xs {{ $s['status'] === 'paid' ? 'text-emerald-800' : 'text-amber-800' }}">Balance</dt>
                        <dd class="break-words text-xs font-bold tabular-nums sm:mt-2 sm:text-base {{ $s['status'] === 'paid' ? 'text-emerald-950' : 'text-amber-950' }}">{{ $money($s['balance']) }}</dd>
                    </div>
                </dl>

                {{-- Payment history --}}
                <section>
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-cyan-50 text-cyan-700 sm:h-8 sm:w-8">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                                <path d="M3 3v5h5m4-1v5l3 2"/>
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Payments</h3>
                    </div>

                    @forelse ($sale->payments->sortByDesc('paid_at') as $payment)
                        @if ($loop->first)
                            <ul class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white">
                        @endif
                                <li class="flex items-start justify-between gap-3 px-3 py-2.5 sm:px-4 sm:py-3">
                                    <div class="min-w-0">
                                        <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <span class="text-xs font-bold tabular-nums text-slate-950 sm:text-sm">{{ $money($payment->amount) }}</span>
                                            <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700 sm:px-2.5 sm:text-xs">
                                                {{ $methodLabels[$payment->payment_method] ?? ucfirst(str_replace('_', ' ', (string) $payment->payment_method)) }}
                                            </span>
                                        </p>
                                        <p class="mt-1 text-[11px] text-slate-500 sm:text-xs">
                                            {{ $payment->paid_at?->format('d M Y, h:i A') }}@if ($payment->receiver?->name) · {{ $payment->receiver->name }}@endif
                                        </p>
                                        @if ($payment->reference)
                                            <p class="mt-0.5 break-all text-[11px] text-slate-500 sm:text-xs">Ref: {{ $payment->reference }}</p>
                                        @endif
                                    </div>

                                    @if ((float) $payment->tip_amount > 0)
                                        <span class="shrink-0 rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-violet-700 sm:px-2.5 sm:py-1 sm:text-xs">
                                            Tip {{ $money($payment->tip_amount) }}
                                        </span>
                                    @endif
                                </li>
                        @if ($loop->last)
                            </ul>
                        @endif
                    @empty
                        <p class="mt-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">No payments recorded yet.</p>
                    @endforelse
                </section>

                {{-- Record payment --}}
                @if ($s['status'] === 'paid')
                    <div class="flex items-center gap-3 rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m5 12 4 4L19 6"/>
                            </svg>
                        </span>
                        <p class="text-xs font-semibold text-emerald-800 sm:text-sm">This sale has been paid in full.</p>
                    </div>
                @else
                    <section class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-3 sm:p-5">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 sm:h-8 sm:w-8">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                                    <path d="M7 10h10m-10 4h5"/>
                                </svg>
                            </span>
                            <h3 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Record payment</h3>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('stores.sales.payments.store', ['store' => $store, 'sale' => $sale]) }}"
                            class="mt-3 grid grid-cols-1 gap-3 sm:mt-4 sm:grid-cols-2 sm:gap-4"
                        >
                            @csrf
                            <input type="hidden" name="_sale" value="{{ $sale->id }}">

                            @if ($paymentFailed)
                                <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 sm:col-span-2 sm:px-4 sm:py-3 sm:text-sm">
                                    @foreach ($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif

                            <div>
                                <div class="mb-1.5 flex items-baseline justify-between gap-2">
                                    <label for="pay-amount-{{ $sale->id }}" class="block text-sm font-semibold text-slate-700">Amount (₦)</label>
                                    <button
                                        type="button"
                                        data-fill-amount="{{ $s['balance'] }}"
                                        data-fill-target="pay-amount-{{ $sale->id }}"
                                        class="text-xs font-semibold text-indigo-700 hover:text-indigo-800 focus:outline-none focus-visible:underline"
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
                                    class="block min-h-10 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 sm:min-h-12 sm:py-3 sm:text-base shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                >
                            </div>

                            <div>
                                <label for="pay-method-{{ $sale->id }}" class="mb-1 block text-xs font-semibold text-slate-700 sm:mb-1.5 sm:text-sm">Method</label>
                                <select
                                    id="pay-method-{{ $sale->id }}"
                                    name="payment_method"
                                    required
                                    class="block min-h-10 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 sm:min-h-12 sm:py-3 sm:text-base shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                >
                                    @foreach ($methodLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($paymentFailed && old('payment_method') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="pay-ref-{{ $sale->id }}" class="mb-1 block text-xs font-semibold text-slate-700 sm:mb-1.5 sm:text-sm">
                                    Reference <span class="font-normal text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="pay-ref-{{ $sale->id }}"
                                    name="reference"
                                    maxlength="120"
                                    value="{{ $paymentFailed ? old('reference') : '' }}"
                                    class="block min-h-10 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 sm:min-h-12 sm:py-3 sm:text-base shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                >
                            </div>

                            <div>
                                <label for="pay-notes-{{ $sale->id }}" class="mb-1 block text-xs font-semibold text-slate-700 sm:mb-1.5 sm:text-sm">
                                    Notes <span class="font-normal text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="pay-notes-{{ $sale->id }}"
                                    name="notes"
                                    value="{{ $paymentFailed ? old('notes') : '' }}"
                                    class="block min-h-10 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 sm:min-h-12 sm:py-3 sm:text-base shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                >
                            </div>

                            <div class="border-t border-indigo-100 pt-4 sm:col-span-2 sm:flex sm:justify-end">
                                <button type="submit" class="oy-btn oy-btn-primary min-h-10 w-full sm:min-h-12 sm:w-auto">Record payment</button>
                            </div>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </dialog>
@endforeach


</div>