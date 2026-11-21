@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-8 lg:px-8">

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-5 py-6 sm:px-7">
            <a href="{{ route('stock.index') }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition hover:text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"/>
                    <path d="M20 12H9"/>
                </svg>
                Back to store stock
            </a>

            <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold tracking-wide text-slate-200">
                            STOCK RECORD #{{ $item->id }}
                        </span>

                        <span class="inline-flex items-center rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-300">
                            {{ $item->is_raw ? 'Raw material' : 'Finished stock' }}
                        </span>
                    </div>

                    <h1 class="mt-3 break-words text-2xl font-bold tracking-tight text-white sm:text-3xl">
                        {{ $item->name }}
                    </h1>

                    <p class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-slate-300">
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="M7 9h10M7 13h5"/>
                        </svg>
                        {{ $item->code }}
                    </p>

                    @if($item->store)
                        <p class="mt-2 text-sm text-slate-400">
                            Origin store: {{ $item->store->name }}
                        </p>
                    @endif
                </div>

                @if($canManageItems && (int) $item->store_id === (int) $level->store_id)
                    <a href="{{ route('stock.items.edit', $item) }}"
                       class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-100">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m16 4 4 4L9 19H5v-4L16 4Z"/>
                            <path d="m14 6 4 4"/>
                        </svg>
                        Edit stock details
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Current inventory position at this store --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:px-6">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/>
                            <path d="m3.5 7.7 8.5 4.5 8.5-4.5M12 12.2V21"/>
                        </svg>
                    </span>
                    <h2 class="text-base font-bold tracking-tight text-slate-950">Current inventory</h2>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    Stock position and valuation at this store.
                </p>
            </div>

            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                {{ $level->store->name }}
            </span>
        </div>

        <div class="grid gap-3 p-4 sm:grid-cols-2 sm:gap-4 sm:p-6 xl:grid-cols-4">
            <div class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-4">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800">
                        Available quantity
                    </p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m5 12 4 4L19 6"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight text-emerald-950 tabular-nums">
                    {{ number_format((float) $level->quantity, 3) }}
                </p>
                <p class="mt-1 text-xs font-medium text-emerald-700">{{ $item->unit }} available</p>
            </div>

            <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-4">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">
                        Quarantined quantity
                    </p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3 2.8 19h18.4L12 3Z"/>
                            <path d="M12 9v4m0 3h.01"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight text-amber-950 tabular-nums">
                    {{ number_format((float) $level->quantity_quarantined, 3) }}
                </p>
                <p class="mt-1 text-xs font-medium text-amber-700">{{ $item->unit }} quarantined</p>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-4">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-800">
                        Average unit cost
                    </p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M15 8.5c-.6-.6-1.5-.9-2.5-.9-1.4 0-2.5.7-2.5 1.8 0 2.8 5 1.1 5 4 0 1.2-1.1 2-2.7 2-1 0-2-.4-2.8-1.1M12 6v12"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-xl font-bold tracking-tight text-blue-950 tabular-nums">
                    ₦{{ number_format((float) $level->average_unit_cost, 2) }}
                </p>
                <p class="mt-1 text-xs font-medium text-blue-700">Weighted average cost</p>
            </div>

            <div class="rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-4">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-800">
                        Available inventory value
                    </p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 text-violet-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="M7 10h10m-10 4h5"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-xl font-bold tracking-tight text-violet-950 tabular-nums">
                    ₦{{ number_format((float) $level->inventory_value, 2) }}
                </p>
                <p class="mt-1 text-xs font-medium text-violet-700">Value of available stock</p>
            </div>
        </div>

        <div class="grid gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:grid-cols-2 sm:px-6">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Store holding this stock</p>
                    <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $level->store->name }}</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3 2.8 19h18.4L12 3Z"/>
                        <path d="M12 9v4m0 3h.01"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quarantined inventory value</p>
                    <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                        ₦{{ number_format((float) $level->quarantine_value, 2) }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Item information --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-5 sm:px-6">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Z"/>
                    <path d="M4 6h16M8 10h8m-8 4h6"/>
                </svg>
            </span>
            <div>
                <h2 class="text-base font-bold tracking-tight text-slate-950">Stock information</h2>
                <p class="mt-1 text-sm text-slate-500">Identification, classification and pricing details.</p>
            </div>
        </div>

        <dl class="grid gap-3 p-4 sm:grid-cols-2 sm:gap-4 sm:p-6 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Generated stock name</dt>
                <dd class="mt-2 break-words text-sm font-semibold text-slate-950">{{ $item->name }}</dd>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stock code</dt>
                <dd class="mt-2 break-all font-mono text-sm font-bold text-blue-800">{{ $item->code }}</dd>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</dt>
                <dd class="mt-2 text-sm font-semibold text-slate-900">{{ $item->category ?: '—' }}</dd>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grade</dt>
                <dd class="mt-2 text-sm font-semibold text-slate-900">{{ $item->grade ?: '—' }}</dd>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unit of measure</dt>
                <dd class="mt-2">
                    <span class="inline-flex rounded-lg bg-blue-50 px-2.5 py-1 text-sm font-bold text-blue-800">
                        {{ $item->unit }}
                    </span>
                </dd>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stock classification</dt>
                <dd class="mt-2">
                    @if($item->is_raw)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Raw material
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Finished stock
                        </span>
                    @endif
                </dd>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Suggested selling price</dt>
                <dd class="mt-2 text-sm font-bold tabular-nums text-slate-950">
                    {{ $item->suggested_price !== null ? '₦'.number_format((float) $item->suggested_price, 2) : '—' }}
                </dd>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current market price</dt>
                <dd class="mt-2 text-sm font-bold tabular-nums text-slate-950">
                    {{ $item->current_market_price !== null ? '₦'.number_format((float) $item->current_market_price, 2) : '—' }}
                </dd>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 sm:col-span-2 lg:col-span-1">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Registered by</dt>
                <dd class="mt-2 text-sm font-semibold text-slate-900">{{ $item->creator?->name ?? '—' }}</dd>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 sm:col-span-2 lg:col-span-1">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date registered</dt>
                <dd class="mt-2 text-sm font-semibold text-slate-900">
                    {{ $item->created_at?->format('d M Y, h:i A') ?? '—' }}
                </dd>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 sm:col-span-2 lg:col-span-1">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Description</dt>
                <dd class="mt-2 break-words text-sm leading-6 text-slate-700">
                    {{ $item->description ?: 'No description provided.' }}
                </dd>
            </div>
        </dl>
    </section>

    {{-- Stock photographs --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-fuchsia-50 text-fuchsia-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                        <circle cx="8.5" cy="9" r="1.5"/>
                        <path d="m21 15-5-5L5 20"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold tracking-tight text-slate-950">Stock photographs</h2>
                    <p class="mt-1 text-sm text-slate-500">Photographs uploaded for this stock record.</p>
                </div>
            </div>

            <span class="inline-flex rounded-full bg-fuchsia-50 px-3 py-1.5 text-xs font-bold text-fuchsia-700">
                {{ $item->photos->count() }} / 3 photos
            </span>
        </div>

        @if($item->photos->isNotEmpty())
            <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 sm:gap-4 sm:p-6 lg:grid-cols-4">
                @foreach($item->photos as $photo)
                    <a href="{{ route('stock.items.photos.show', [$item, $photo]) }}"
                       target="_blank"
                       rel="noopener"
                       class="group min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                        <div class="overflow-hidden bg-slate-100">
                            <img
                                src="{{ route('stock.items.photos.show', [$item, $photo]) }}"
                                alt="{{ $photo->original_name }}"
                                loading="lazy"
                                class="h-28 w-full object-cover transition duration-300 group-hover:scale-105 sm:h-32"
                            >
                        </div>
                        <div class="p-3">
                            <p class="truncate text-xs font-bold text-slate-900" title="{{ $photo->original_name }}">
                                {{ $photo->original_name }}
                            </p>
                            <p class="mt-1 text-[11px] font-medium text-slate-500">
                                {{ number_format($photo->file_size / 1024, 1) }} KB
                            </p>
                            <p class="mt-2 truncate text-[11px] text-slate-500">
                                Uploaded by {{ $photo->uploader?->name ?? 'Unknown' }}
                            </p>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-blue-700">
                                View photo
                                <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="m-4 flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center sm:m-6">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                        <circle cx="8.5" cy="9" r="1.5"/>
                        <path d="m21 15-5-5L5 20"/>
                    </svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-800">No photographs yet</p>
                <p class="mt-1 text-xs text-slate-500">No photographs have been uploaded for this stock item.</p>
            </div>
        @endif
    </section>

    {{-- Append-only movement history --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                        <path d="M3 3v5h5m4-1v5l3 2"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold tracking-tight text-slate-950">Stock movement history</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Recorded movements for {{ $level->store->name }}.
                    </p>
                </div>
            </div>

            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">
                {{ $movements->total() }} records
            </span>
        </div>

        @if($movements->isNotEmpty())
            {{-- Mobile and tablet movement cards --}}
            <div class="space-y-3 p-4 lg:hidden">
                @foreach($movements as $movement)
                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3 bg-slate-50/80 p-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-bold text-slate-950">
                                        {{ \Illuminate\Support\Str::headline($movement->type) }}
                                    </span>
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-700">
                                        Movement
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $movement->created_at?->format('d M Y, h:i A') }}
                                </p>
                            </div>

                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-right shadow-sm">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Quantity</p>
                                <p class="mt-0.5 text-sm font-bold tabular-nums text-slate-950">
                                    {{ number_format((float) $movement->quantity, 3) }}
                                    <span class="text-xs font-semibold text-slate-500">{{ $item->unit }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 p-4">
                            <div>
                                <p class="text-xs font-medium text-slate-500">Unit cost</p>
                                <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                                    ₦{{ number_format((float) $movement->unit_cost, 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-500">Movement value</p>
                                <p class="mt-1 text-sm font-bold tabular-nums text-slate-900">
                                    ₦{{ number_format((float) $movement->movement_value, 2) }}
                                </p>
                            </div>
                            <div class="col-span-2 border-t border-slate-100 pt-3">
                                <p class="text-xs font-medium text-slate-500">Recorded by</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800">
                                    {{ $movement->actor?->name ?? '—' }}
                                </p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-xs font-medium text-slate-500">Reason</p>
                                <p class="mt-1 break-words text-sm leading-6 text-slate-700">
                                    {{ $movement->reason ?: '—' }}
                                </p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop movement table --}}
            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3">Date</th>
                            <th class="whitespace-nowrap px-4 py-3">Movement</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right">Quantity</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right">Unit cost</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right">Value</th>
                            <th class="whitespace-nowrap px-4 py-3">Recorded by</th>
                            <th class="min-w-48 px-4 py-3">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($movements as $movement)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
                                    {{ $movement->created_at?->format('d M Y, h:i A') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">
                                        {{ \Illuminate\Support\Str::headline($movement->type) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-slate-900">
                                    {{ number_format((float) $movement->quantity, 3) }} {{ $item->unit }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-slate-600">
                                    ₦{{ number_format((float) $movement->unit_cost, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-slate-900">
                                    ₦{{ number_format((float) $movement->movement_value, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $movement->actor?->name ?? '—' }}
                                </td>
                                <td class="min-w-48 px-4 py-3 text-slate-600">
                                    {{ $movement->reason ?: '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-4 py-4 sm:px-6">
                {{ $movements->links() }}
            </div>
        @else
            <div class="m-4 flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center sm:m-6">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                        <path d="M3 3v5h5m4-1v5l3 2"/>
                    </svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-800">No stock movements yet</p>
                <p class="mt-1 text-xs text-slate-500">Movement records will appear here as stock activity is recorded.</p>
            </div>
        @endif
    </section>
</div>
@endsection
