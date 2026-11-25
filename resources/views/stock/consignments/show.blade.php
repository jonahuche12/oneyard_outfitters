@extends('layouts.app')

@section('content')
@php
    $viewer = auth()->user();
    $viewerAssignment = $viewer->activeStoreAssignment;
    $canAcknowledge = $viewer->is_active
        && $viewerAssignment
        && in_array($viewerAssignment->role, [
            \App\Models\StoreStaff::ROLE_COORDINATOR,
            \App\Models\StoreStaff::ROLE_ASSISTANT,
        ], true)
        && (int) $viewerAssignment->store_id === (int) $consignment->destination_store_id;

    $canConfirmDiscrepancies = $viewer->is_active
        && $viewerAssignment
        && $viewerAssignment->role === \App\Models\StoreStaff::ROLE_COORDINATOR
        && (int) $viewerAssignment->store_id === (int) $consignment->source_store_id;

    $discrepancies = $consignment->items
        ->flatMap(fn ($line) => $line->discrepancies);
    $acknowledgements = $consignment->acknowledgements()
        ->with(['acknowledger', 'items.discrepancies'])
        ->latest('acknowledged_at')
        ->get();

    $statusClasses = match ($consignment->status) {
        \App\Models\StockConsignment::STATUS_RECONCILED => 'bg-emerald-400/15 text-emerald-300',
        \App\Models\StockConsignment::STATUS_CANCELLED => 'bg-rose-400/15 text-rose-300',
        default => 'bg-blue-400/15 text-blue-300',
    };
@endphp

<div class="mx-auto max-w-6xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Consignment identity and key figures --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
            <a href="{{ route('stock.index') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 transition hover:text-white sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
                Back to stock
            </a>

            <div class="mt-3 sm:mt-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-200 sm:text-xs">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="1.5"/><circle cx="18" cy="18" r="1.5"/></svg>
                        Stock consignment
                    </span>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold sm:text-xs {{ $statusClasses }}">
                        {{ ucfirst(str_replace('_', ' ', $consignment->status)) }}
                    </span>
                </div>
                <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">{{ $consignment->reference }}</h1>
                <p class="mt-1 text-xs text-slate-400 sm:text-sm">Dispatched {{ $consignment->dispatched_at?->format('d M Y, h:i A') }}</p>
            </div>
        </div>

        <div class="grid gap-2 bg-slate-50 p-3 sm:grid-cols-3 sm:gap-3 sm:p-4">
            <div class="rounded-xl border border-rose-100 bg-rose-50/70 p-3 sm:p-4">
                <p class="flex items-center gap-1.5 text-xs font-bold text-rose-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>
                    Total dispatch cost
                </p>
                <p class="mt-1.5 text-lg font-bold tabular-nums tracking-tight text-rose-950 sm:text-xl">₦{{ number_format((float) $consignment->total_cost, 2) }}</p>
            </div>
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-3 sm:p-4">
                <p class="flex items-center gap-1.5 text-xs font-bold text-emerald-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>
                    Expected sales value
                </p>
                <p class="mt-1.5 text-lg font-bold tabular-nums tracking-tight text-emerald-950 sm:text-xl">₦{{ number_format((float) $consignment->expected_sales_value, 2) }}</p>
            </div>
            <div class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-3 sm:p-4">
                <p class="flex items-center gap-1.5 text-xs font-bold text-indigo-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16M4 6h16M4 18h8"/></svg>
                    Expected gross return
                </p>
                <p class="mt-1.5 text-lg font-bold tabular-nums tracking-tight text-indigo-950 sm:text-xl">₦{{ number_format((float) $consignment->expected_gross_return, 2) }}</p>
            </div>
        </div>
    </section>

    {{-- Feedback --}}
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    {{-- Consignment details --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 3h14v18H5zM8 8h8M8 12h8M8 16h4"/></svg>
            </span>
            <div class="min-w-0">
                <h2 class="text-base font-bold text-slate-900">Consignment details</h2>
                <p class="mt-0.5 text-xs text-slate-500">Stores and staff involved.</p>
            </div>
        </div>
        <dl class="grid gap-3 p-3 sm:grid-cols-2 sm:gap-4 sm:p-5">
            <div class="flex min-w-0 items-start gap-3 rounded-xl border border-sky-100 bg-sky-50/70 p-3 sm:p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg></span>
                <div class="min-w-0"><dt class="text-[11px] font-bold uppercase tracking-wide text-sky-700">Source store</dt><dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $consignment->sourceStore->name }}</dd></div>
            </div>
            <div class="flex min-w-0 items-start gap-3 rounded-xl border border-emerald-100 bg-emerald-50/70 p-3 sm:p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                <div class="min-w-0"><dt class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">Destination store</dt><dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $consignment->destinationStore->name }}</dd></div>
            </div>
            <div class="flex min-w-0 items-start gap-3 rounded-xl border border-violet-100 bg-violet-50/70 p-3 sm:p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3.5"/><path d="M5 20v-1a7 7 0 0 1 14 0v1"/></svg></span>
                <div class="min-w-0"><dt class="text-[11px] font-bold uppercase tracking-wide text-violet-700">Dispatched by</dt><dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $consignment->dispatcher?->name ?? '—' }}</dd></div>
            </div>
            <div class="flex min-w-0 items-start gap-3 rounded-xl border border-amber-100 bg-amber-50/70 p-3 sm:p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3.5"/><path d="M5 20v-1a7 7 0 0 1 14 0v1"/></svg></span>
                <div class="min-w-0"><dt class="text-[11px] font-bold uppercase tracking-wide text-amber-700">Created by</dt><dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $consignment->creator?->name ?? '—' }}</dd></div>
            </div>
        </dl>
    </section>

    {{-- Dispatched items --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-cyan-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700 ring-1 ring-cyan-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z"/><path d="M3 7.5V17l9 4 9-4V7.5M12 12v9"/></svg>
                </span>
                <h2 class="text-base font-bold text-slate-900 sm:text-lg">Dispatched items</h2>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                {{ $consignment->items->count() }} {{ \Illuminate\Support\Str::plural('line', $consignment->items->count()) }}
            </span>
        </div>

        {{-- Mobile item cards --}}
        <div class="space-y-3 bg-slate-50/50 p-3 md:hidden">
            @foreach ($consignment->items as $line)
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <h3 class="break-words text-sm font-bold text-slate-900">{{ $line->item?->description ?? 'Item unavailable' }}</h3>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-indigo-100 bg-indigo-50/70 p-2.5">
                            <p class="text-xs font-semibold text-indigo-700">Quantity</p>
                            <p class="mt-1 text-sm font-bold tabular-nums text-indigo-950">{{ number_format((float) $line->quantity_dispatched, 3) }}</p>
                        </div>
                        <div class="rounded-lg border border-blue-100 bg-blue-50/70 p-2.5">
                            <p class="text-xs font-semibold text-blue-700">Unit cost</p>
                            <p class="mt-1 text-sm font-bold tabular-nums text-blue-950">₦{{ number_format((float) $line->unit_cost, 2) }}</p>
                        </div>
                        <div class="rounded-lg border border-rose-100 bg-rose-50/70 p-2.5">
                            <p class="text-xs font-semibold text-rose-700">Line cost</p>
                            <p class="mt-1 text-sm font-bold tabular-nums text-rose-950">₦{{ number_format((float) $line->line_cost, 2) }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-2.5">
                            <p class="text-xs font-semibold text-slate-600">Market price</p>
                            <p class="mt-1 text-sm font-bold tabular-nums text-slate-900">{{ $line->market_price === null ? '—' : '₦'.number_format((float) $line->market_price, 2) }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs">
                        <span class="text-slate-500">Expected sales</span>
                        <span class="text-sm font-bold tabular-nums text-emerald-800">₦{{ number_format((float) $line->expected_sales_value, 2) }}</span>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Tablet and desktop item table --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-gradient-to-r from-slate-50 via-white to-indigo-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Item</th>
                        <th scope="col" class="px-5 py-3 text-right">Quantity</th>
                        <th scope="col" class="px-5 py-3 text-right text-blue-700">Unit cost</th>
                        <th scope="col" class="px-5 py-3 text-right text-rose-700">Line cost</th>
                        <th scope="col" class="px-5 py-3 text-right">Market price</th>
                        <th scope="col" class="px-5 py-3 text-right text-emerald-700">Expected sales</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($consignment->items as $line)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $line->item?->description ?? 'Item unavailable' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums text-slate-900">{{ number_format((float) $line->quantity_dispatched, 3) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums text-blue-800">₦{{ number_format((float) $line->unit_cost, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums text-rose-800">₦{{ number_format((float) $line->line_cost, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-700">{{ $line->market_price === null ? '—' : '₦'.number_format((float) $line->market_price, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums text-emerald-800">₦{{ number_format((float) $line->expected_sales_value, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 font-bold text-slate-900">
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right">Totals</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-rose-900">₦{{ number_format((float) $consignment->total_cost, 2) }}</td>
                        <td></td>
                        <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-emerald-900">₦{{ number_format((float) $consignment->expected_sales_value, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    {{-- Notes --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-amber-50 px-4 py-4 sm:px-6">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 3h14v18H5zM8 8h8M8 12h8M8 16h4"/></svg>
            </span>
            <h2 class="text-base font-bold text-slate-900">Notes</h2>
        </div>
        <div class="space-y-4 p-4 sm:p-6">
            <p class="whitespace-pre-line break-words text-sm text-slate-700">{{ $consignment->notes ?: 'No dispatch notes.' }}</p>
            <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>
                <p>All dispatched quantities are held in destination quarantine until the receiving store acknowledges them.</p>
            </div>
        </div>
    </section>

    {{-- Reported discrepancies --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-amber-50 via-white to-rose-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-slate-900 sm:text-lg">Reported discrepancies</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                        Damaged quantities require source-store write-off confirmation. Shortages require source-store confirmation.
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-3 p-3 sm:p-6">
            @forelse ($discrepancies as $discrepancy)
                @php
                    $line = $consignment->items->firstWhere('id', $discrepancy->stock_consignment_item_id);
                    $isOpen = $discrepancy->status === 'open';
                @endphp

                <div class="rounded-xl border {{ $isOpen ? 'border-amber-200 bg-amber-50/40' : 'border-emerald-100 bg-emerald-50/40' }} p-3 sm:p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="break-words text-sm font-bold text-slate-900">
                                {{ $line?->item?->description ?? 'Stock item' }}
                                — {{ ucfirst($discrepancy->type) }}
                            </p>
                            <p class="mt-1 text-xs leading-relaxed text-slate-600">
                                Quantity: <span class="font-semibold tabular-nums">{{ number_format((float) $discrepancy->quantity, 3) }}</span>
                                · Reported by {{ $discrepancy->reporter?->name ?? 'Staff member' }}
                                · {{ $discrepancy->reported_at?->format('d M Y, h:i A') ?? '—' }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $isOpen ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">
                            {{ ucfirst($discrepancy->status) }}
                        </span>
                    </div>

                    @if (! $isOpen)
                        <p class="mt-2 text-xs leading-relaxed text-slate-600">
                            Resolution: {{ ucfirst(str_replace('_', ' ', $discrepancy->resolution ?? 'confirmed')) }}
                            · Confirmed by {{ $discrepancy->resolver?->name ?? 'Staff member' }}
                            · {{ $discrepancy->resolved_at?->format('d M Y, h:i A') ?? '—' }}
                        </p>
                        @if ($discrepancy->resolution_reason)
                            <p class="mt-1 whitespace-pre-line break-words text-xs text-slate-600">{{ $discrepancy->resolution_reason }}</p>
                        @endif
                    @elseif ($canConfirmDiscrepancies)
                        <form method="POST"
                            action="{{ route('stock.consignments.discrepancies.confirm', [$consignment, $discrepancy]) }}"
                            class="mt-3 space-y-3 rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
                            @csrf
                            <label for="resolution_reason_{{ $discrepancy->id }}" class="block text-sm font-semibold text-amber-800">
                                Confirmation reason
                            </label>
                            <textarea
                                id="resolution_reason_{{ $discrepancy->id }}"
                                name="resolution_reason"
                                rows="3"
                                maxlength="2000"
                                required
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                                placeholder="Explain the source-store confirmation..."
                            >{{ old('resolution_reason') }}</textarea>
                            @error('resolution_reason')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <button type="submit"
                                class="inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg bg-amber-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-300 sm:w-auto">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
                                {{ $discrepancy->type === 'damaged' ? 'Confirm write-off' : 'Confirm shortage' }}
                            </button>
                        </form>
                    @else
                        <p class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-amber-100/70 px-3 py-1.5 text-xs font-medium text-amber-800">
                            Awaiting confirmation by the source-store coordinator.
                        </p>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-emerald-200 bg-emerald-50/40 px-4 py-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-bold text-slate-700">No discrepancies have been reported.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Acknowledgement history --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-emerald-50 via-white to-blue-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/><path d="m16 16 2 2 3-4"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-slate-900 sm:text-lg">Acknowledgement history</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Recorded receipt quantities and outstanding discrepancies.</p>
                </div>
            </div>
            @if ($canAcknowledge && ! in_array($consignment->status, [
                \App\Models\StockConsignment::STATUS_RECONCILED,
                \App\Models\StockConsignment::STATUS_CANCELLED,
            ], true))
                <a href="{{ route('stock.consignments.acknowledgements.create', $consignment) }}"
                    class="inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 sm:w-auto">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
                    Record Acknowledgement
                </a>
            @endif
        </div>

        <div class="space-y-3 p-3 sm:p-6">
            @forelse ($acknowledgements as $acknowledgement)
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2 text-sm">
                        <span class="flex items-center gap-2 font-bold text-slate-900">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-100 text-sky-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3.5"/><path d="M5 20v-1a7 7 0 0 1 14 0v1"/></svg></span>
                            {{ $acknowledgement->acknowledger->name ?? 'Staff member' }}
                        </span>
                        <span class="text-xs text-slate-500">
                            {{ $acknowledgement->acknowledged_at }}
                        </span>
                    </div>

                    @if ($acknowledgement->notes)
                        <p class="mt-2 break-words rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">{{ $acknowledgement->notes }}</p>
                    @endif

                    {{-- Mobile acknowledged lines --}}
                    <div class="mt-3 space-y-2 md:hidden">
                        @foreach ($acknowledgement->items as $acknowledgedLine)
                            <div class="rounded-lg border border-slate-200 p-2.5">
                                <p class="break-words text-xs font-bold text-slate-900">{{ $acknowledgedLine->consignmentItem->item->description ?? 'Stock item' }}</p>
                                <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                                    <div class="rounded-md bg-emerald-50 p-1.5">
                                        <p class="text-[10px] font-semibold text-emerald-700">Good</p>
                                        <p class="text-xs font-bold tabular-nums text-emerald-900">{{ number_format((float) $acknowledgedLine->quantity_good, 3) }}</p>
                                    </div>
                                    <div class="rounded-md bg-rose-50 p-1.5">
                                        <p class="text-[10px] font-semibold text-rose-700">Damaged</p>
                                        <p class="text-xs font-bold tabular-nums text-rose-900">{{ number_format((float) $acknowledgedLine->quantity_damaged, 3) }}</p>
                                    </div>
                                    <div class="rounded-md bg-amber-50 p-1.5">
                                        <p class="text-[10px] font-semibold text-amber-700">Shortage</p>
                                        <p class="text-xs font-bold tabular-nums text-amber-900">{{ number_format((float) $acknowledgedLine->quantity_shortage, 3) }}</p>
                                    </div>
                                </div>
                                <p class="mt-2 text-[11px] text-slate-500">Discrepancies: <span class="font-semibold text-slate-700">{{ $acknowledgedLine->discrepancies->count() }}</span></p>
                            </div>
                        @endforeach
                    </div>

                    {{-- Tablet and desktop acknowledged lines --}}
                    <div class="mt-3 hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th scope="col" class="py-2 pr-4">Item</th>
                                    <th scope="col" class="px-2 py-2 text-right text-emerald-700">Good</th>
                                    <th scope="col" class="px-2 py-2 text-right text-rose-700">Damaged</th>
                                    <th scope="col" class="px-2 py-2 text-right text-amber-700">Shortage</th>
                                    <th scope="col" class="py-2 pl-2 text-right">Discrepancies</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($acknowledgement->items as $acknowledgedLine)
                                    <tr class="transition hover:bg-slate-50/70">
                                        <td class="py-2 pr-4 font-medium text-slate-900">
                                            {{ $acknowledgedLine->consignmentItem->item->description ?? 'Stock item' }}
                                        </td>
                                        <td class="px-2 py-2 text-right font-semibold tabular-nums text-emerald-800">{{ number_format((float) $acknowledgedLine->quantity_good, 3) }}</td>
                                        <td class="px-2 py-2 text-right font-semibold tabular-nums text-rose-800">{{ number_format((float) $acknowledgedLine->quantity_damaged, 3) }}</td>
                                        <td class="px-2 py-2 text-right font-semibold tabular-nums text-amber-800">{{ number_format((float) $acknowledgedLine->quantity_shortage, 3) }}</td>
                                        <td class="py-2 pl-2 text-right tabular-nums text-slate-700">
                                            {{ $acknowledgedLine->discrepancies->count() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-emerald-200 bg-emerald-50/40 px-4 py-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-bold text-slate-700">No acknowledgement has been recorded yet.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Consignment returns --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-violet-50 via-white to-sky-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 ring-1 ring-violet-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-3"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-slate-900 sm:text-lg">Returns to source store</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                        Only acknowledged-good stock can be returned. Receipt must be confirmed by the source coordinator.
                    </p>
                </div>
            </div>
            @if ($canDispatchReturn)
                <a href="{{ route('stock.consignments.returns.create', $consignment) }}"
                   class="inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg bg-violet-50 px-4 text-sm font-semibold text-violet-800 ring-1 ring-inset ring-violet-200 transition hover:bg-violet-100 focus:outline-none focus:ring-2 focus:ring-violet-300 sm:w-auto">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-3"/></svg>
                    Dispatch return
                </a>
            @endif
        </div>

        <div class="p-3 sm:p-6">
            @if ($consignment->returns->isEmpty())
                <div class="rounded-xl border border-dashed border-violet-200 bg-violet-50/40 px-4 py-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-violet-100 text-violet-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-3"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-bold text-slate-700">No returns have been dispatched for this consignment.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($consignment->returns->sortByDesc('dispatched_at') as $stockReturn)
                        <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="break-words text-sm font-bold text-slate-900">{{ $stockReturn->reference }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Dispatched {{ $stockReturn->dispatched_at?->format('d M Y, h:i A') }}
                                        by {{ $stockReturn->dispatcher?->name ?? '—' }}
                                    </p>
                                    @if ($stockReturn->received_at)
                                        <p class="mt-1 text-xs text-emerald-700">
                                            Receipt confirmed {{ $stockReturn->received_at->format('d M Y, h:i A') }}
                                            by {{ $stockReturn->receiver?->name ?? '—' }}
                                        </p>
                                    @else
                                        <p class="mt-1 inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">Awaiting source-store receipt</p>
                                    @endif
                                </div>
                                @if ($canReceiveReturn && ! $stockReturn->received_at)
                                    <a href="{{ route('stock.consignments.returns.receive', $stockReturn) }}"
                                       class="inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-300 sm:w-auto">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
                                        Confirm receipt
                                    </a>
                                @endif
                            </div>

                            {{-- Mobile return lines --}}
                            <div class="mt-3 space-y-2 md:hidden">
                                @foreach ($stockReturn->items as $returnLine)
                                    @php
                                        $received = $returnLine->quantity_received;
                                        $missing = $received === null
                                            ? null
                                            : bcsub((string) $returnLine->quantity_dispatched, (string) $received, 3);
                                    @endphp
                                    <div class="rounded-lg border border-slate-200 p-2.5">
                                        <p class="break-words text-xs font-bold text-slate-900">{{ $returnLine->consignmentItem->item->description }}</p>
                                        <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                                            <div class="rounded-md bg-indigo-50 p-1.5">
                                                <p class="text-[10px] font-semibold text-indigo-700">Sent back</p>
                                                <p class="text-xs font-bold tabular-nums text-indigo-900">{{ number_format((float) $returnLine->quantity_dispatched, 3) }}</p>
                                            </div>
                                            <div class="rounded-md bg-emerald-50 p-1.5">
                                                <p class="text-[10px] font-semibold text-emerald-700">Received</p>
                                                <p class="text-xs font-bold tabular-nums text-emerald-900">{{ $received === null ? '—' : number_format((float) $received, 3) }}</p>
                                            </div>
                                            <div class="rounded-md bg-amber-50 p-1.5">
                                                <p class="text-[10px] font-semibold text-amber-700">Not received</p>
                                                <p class="text-xs font-bold tabular-nums text-amber-900">{{ $missing === null ? 'Pending' : number_format((float) $missing, 3) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Tablet and desktop return lines --}}
                            <div class="mt-3 hidden overflow-x-auto md:block">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th scope="col" class="py-2 pr-4">Item</th>
                                            <th scope="col" class="px-2 py-2 text-right text-indigo-700">Sent back</th>
                                            <th scope="col" class="px-2 py-2 text-right text-emerald-700">Received</th>
                                            <th scope="col" class="py-2 pl-2 text-right text-amber-700">Not received</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($stockReturn->items as $returnLine)
                                            @php
                                                $received = $returnLine->quantity_received;
                                                $missing = $received === null
                                                    ? null
                                                    : bcsub((string) $returnLine->quantity_dispatched, (string) $received, 3);
                                            @endphp
                                            <tr class="transition hover:bg-slate-50/70">
                                                <td class="py-2 pr-4 font-medium text-slate-900">{{ $returnLine->consignmentItem->item->description }}</td>
                                                <td class="px-2 py-2 text-right font-semibold tabular-nums text-indigo-800">{{ number_format((float) $returnLine->quantity_dispatched, 3) }}</td>
                                                <td class="px-2 py-2 text-right font-semibold tabular-nums text-emerald-800">{{ $received === null ? '—' : number_format((float) $received, 3) }}</td>
                                                <td class="py-2 pl-2 text-right font-semibold tabular-nums text-amber-800">{{ $missing === null ? 'Pending' : number_format((float) $missing, 3) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if ($stockReturn->dispatch_notes)
                                <p class="mt-3 whitespace-pre-line break-words rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">{{ $stockReturn->dispatch_notes }}</p>
                            @endif
                            @if ($stockReturn->receipt_notes)
                                <p class="mt-2 whitespace-pre-line break-words rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                    <span class="font-semibold">Receipt notes:</span> {{ $stockReturn->receipt_notes }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
@endsection