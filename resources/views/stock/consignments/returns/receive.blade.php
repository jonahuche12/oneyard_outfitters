@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Page identity --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
            <a href="{{ route('stock.consignments.show', $stockReturn->stock_consignment_id) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 transition hover:text-white sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
                Back to consignment
            </a>

            <div class="mt-3 sm:mt-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-200 sm:text-xs">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
                        Return receipt
                    </span>
                    <span class="rounded-full bg-violet-400/15 px-2.5 py-1 text-[10px] font-bold text-violet-300 sm:text-xs">
                        {{ $stockReturn->reference }}
                    </span>
                    <span class="rounded-full bg-cyan-400/15 px-2.5 py-1 text-[10px] font-bold text-cyan-300 sm:text-xs">
                        {{ $stockReturn->consignment->sourceStore->name }}
                    </span>
                </div>
                <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">Confirm return receipt</h1>
                <p class="mt-1 max-w-2xl text-xs leading-relaxed text-slate-400 sm:text-sm">
                    {{ $stockReturn->reference }} — confirm what physically arrived at {{ $stockReturn->consignment->sourceStore->name }}.
                </p>
            </div>
        </div>
    </section>

    {{-- Validation summary --}}
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 sm:px-5 sm:py-4">
            <div class="flex items-start gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="font-bold">Please correct the following:</p>
                    <ul class="mt-2 list-inside list-disc space-y-0.5 text-xs sm:text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('stock.consignments.returns.confirm', $stockReturn) }}"
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @csrf

        {{-- Received quantities --}}
        <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-50 via-white to-violet-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/><path d="m16 16 2 2 3-4"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">Received quantities</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Enter the quantity that physically arrived for each item.</p>
                </div>
            </div>
        </div>

        <div class="space-y-3 p-3 sm:space-y-4 sm:p-6">
            @foreach ($stockReturn->items as $line)
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3 sm:p-4">
                    {{-- Item identity --}}
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 7.5 9-4.5 9 4.5v9L12 21l-9-4.5v-9Z"/><path d="m3.5 7.7 8.5 4.5 8.5-4.5M12 12.2V21"/></svg>
                        </span>
                        <p class="min-w-0 break-words text-sm font-bold text-slate-900">{{ $line->consignmentItem->item->description }}</p>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-3 sm:gap-4">
                        <div class="rounded-lg border border-indigo-100 bg-indigo-50/70 p-2.5">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-indigo-700 sm:text-xs">Dispatched back</p>
                            <p class="mt-1 text-sm font-bold tabular-nums text-indigo-950">{{ number_format((float) $line->quantity_dispatched, 3) }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <input type="hidden" name="items[{{ $loop->index }}][stock_consignment_return_item_id]"
                                   value="{{ $line->id }}">
                            <label for="quantity_received_{{ $loop->index }}" class="block text-sm font-semibold text-emerald-800">Quantity received</label>
                            <input id="quantity_received_{{ $loop->index }}" type="number" name="items[{{ $loop->index }}][quantity_received]"
                                   min="0" max="{{ $line->quantity_dispatched }}" step="0.001" inputmode="decimal"
                                   value="{{ old('items.'.$loop->index.'.quantity_received', $line->quantity_dispatched) }}"
                                   required
                                   class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm tabular-nums text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 sm:max-w-xs">
                            <p class="mt-1.5 flex items-start gap-1.5 text-xs text-amber-800">
                                <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v5h1"/></svg>
                                Not received if short: quantity dispatched minus quantity received
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Notes --}}
            <div>
                <label for="notes" class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3h14v18H5zM8 8h8M8 12h8M8 16h4"/></svg>
                    </span>
                    Receipt notes
                </label>
                <textarea id="notes" name="notes" rows="3" maxlength="2000"
                          class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/70 px-4 py-4 sm:flex-row sm:justify-end sm:px-6">
            <a href="{{ route('stock.consignments.show', $stockReturn->stock_consignment_id) }}"
               class="oy-btn oy-btn-secondary w-full justify-center sm:w-auto">Cancel</a>
            <button type="submit" class="oy-btn oy-btn-primary w-full justify-center sm:w-auto">
                Confirm actual receipt
            </button>
        </div>
    </form>
</div>
@endsection