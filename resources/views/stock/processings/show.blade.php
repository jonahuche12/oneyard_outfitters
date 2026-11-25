@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Processing identity and primary action --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4 py-5 text-white sm:px-6 sm:py-7">
            <a href="{{ route('stock.index') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 transition hover:text-white sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/><path d="M20 12H9"/></svg>
                Back to stock
            </a>

            <div class="mt-3 flex flex-col gap-4 sm:mt-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-200 sm:text-xs">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7M21 3l-9 9"/><path d="M5 7a7 7 0 0 0 0 10l2 2a7 7 0 0 0 10 0"/></svg>
                            Stock processing
                        </span>
                        <span class="rounded-full bg-cyan-400/15 px-2.5 py-1 text-[10px] font-bold text-cyan-300 sm:text-xs">
                            {{ $processing->store->name }}
                        </span>
                    </div>
                    <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">
                        Processing {{ $processing->number }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-400 sm:text-sm">{{ $processing->store->name }}</p>
                </div>

                <a href="{{ route('stock.processings.create', auth()->user()->isAdministrator() ? ['store_id' => $processing->store_id] : []) }}"
                   class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-xs font-bold text-emerald-950 shadow-sm transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 sm:w-auto sm:text-sm">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Record Processing
                </a>
            </div>
        </div>

        {{-- Headline figures --}}
        <div class="grid grid-cols-3 gap-2 bg-slate-50 p-3 sm:gap-3 sm:p-4">
            <div class="rounded-xl border border-rose-100 bg-rose-50/70 p-2.5 sm:p-4">
                <p class="text-[10px] font-bold uppercase tracking-wide text-rose-700 sm:text-xs">Consumed</p>
                <p class="mt-1 text-sm font-bold tabular-nums text-rose-950 sm:text-xl">
                    {{ number_format((float) $processing->quantity_consumed, 3) }} <span class="text-[10px] font-semibold text-rose-700 sm:text-xs">kg</span>
                </p>
            </div>
            <div class="rounded-xl border border-violet-100 bg-violet-50/70 p-2.5 sm:p-4">
                <p class="text-[10px] font-bold uppercase tracking-wide text-violet-700 sm:text-xs">Recoverable</p>
                <p class="mt-1 text-sm font-bold tabular-nums text-violet-950 sm:text-xl">
                    {{ number_format((float) $processing->recoverable_waste_kg, 3) }} <span class="text-[10px] font-semibold text-violet-700 sm:text-xs">kg</span>
                </p>
            </div>
            <div class="rounded-xl border border-amber-100 bg-amber-50/70 p-2.5 sm:p-4">
                <p class="text-[10px] font-bold uppercase tracking-wide text-amber-700 sm:text-xs">Discarded</p>
                <p class="mt-1 text-sm font-bold tabular-nums text-amber-950 sm:text-xl">
                    {{ number_format((float) $processing->discarded_waste_kg, 3) }} <span class="text-[10px] font-semibold text-amber-700 sm:text-xs">kg</span>
                </p>
            </div>
        </div>
    </section>

    {{-- Feedback --}}
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Processing record --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 3h14v18H5zM8 8h8M8 12h8M8 16h4"/></svg>
            </span>
            <div class="min-w-0">
                <h2 class="text-base font-bold text-slate-900">Processing record</h2>
                <p class="mt-0.5 text-xs text-slate-500">When, by whom and from what material.</p>
            </div>
        </div>
        <dl class="grid gap-3 p-3 sm:grid-cols-2 sm:gap-4 sm:p-5 lg:grid-cols-3">
            <div class="flex min-w-0 items-start gap-3 rounded-xl border border-violet-100 bg-violet-50/70 p-3 sm:p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h18v17H3zM8 2v4M16 2v4M3 9h18"/></svg></span>
                <div class="min-w-0"><dt class="text-[11px] font-bold uppercase tracking-wide text-violet-700">Processing date</dt><dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $processing->processed_on?->format('d M Y') ?? '—' }}</dd></div>
            </div>
            <div class="flex min-w-0 items-start gap-3 rounded-xl border border-sky-100 bg-sky-50/70 p-3 sm:p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3.5"/><path d="M5 20v-1a7 7 0 0 1 14 0v1"/></svg></span>
                <div class="min-w-0"><dt class="text-[11px] font-bold uppercase tracking-wide text-sky-700">Processed by</dt><dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $processing->processor?->name ?? '—' }}</dd></div>
            </div>
            <div class="flex min-w-0 items-start gap-3 rounded-xl border border-rose-100 bg-rose-50/70 p-3 sm:col-span-2 sm:p-4 lg:col-span-1">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 7.5 9-4.5 9 4.5v9L12 21l-9-4.5v-9Z"/><path d="m3.5 7.7 8.5 4.5 8.5-4.5M12 12.2V21"/></svg></span>
                <div class="min-w-0"><dt class="text-[11px] font-bold uppercase tracking-wide text-rose-700">Raw material</dt><dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $processing->rawItem?->description ?? '—' }}</dd></div>
            </div>
        </dl>
    </section>

    {{-- Cost summary --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-emerald-50 via-white to-blue-50 px-4 py-4 sm:px-6">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>
            </span>
            <div class="min-w-0">
                <h2 class="text-base font-bold text-slate-900">Cost summary</h2>
                <p class="mt-0.5 text-xs text-slate-500">Consumed cost against allocated output cost.</p>
            </div>
        </div>
        <div class="grid gap-3 p-3 sm:grid-cols-3 sm:gap-4 sm:p-5">
            <div class="rounded-xl border border-rose-100 bg-gradient-to-br from-rose-50 to-white p-3 shadow-sm sm:p-4">
                <p class="flex items-center gap-1.5 text-xs font-bold text-rose-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>Consumed cost</p>
                <p class="mt-2 text-lg font-bold tabular-nums tracking-tight text-rose-950 sm:text-xl">₦{{ number_format((float) $processing->consumed_cost, 2) }}</p>
            </div>
            <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-3 shadow-sm sm:p-4">
                <p class="flex items-center gap-1.5 text-xs font-bold text-emerald-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H7"/></svg>Allocated output cost</p>
                <p class="mt-2 text-lg font-bold tabular-nums tracking-tight text-emerald-950 sm:text-xl">₦{{ number_format((float) $processing->allocated_cost, 2) }}</p>
            </div>
            <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-3 shadow-sm sm:p-4">
                <p class="flex items-center gap-1.5 text-xs font-bold text-blue-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16M4 6h16M4 18h8"/></svg>Cost difference</p>
                <p class="mt-2 text-lg font-bold tabular-nums tracking-tight text-blue-950 sm:text-xl">₦{{ number_format((float) $processing->cost_difference, 2) }}</p>
            </div>
        </div>
    </section>

    {{-- Produced stock --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z"/><path d="M3 7.5V17l9 4 9-4V7.5M12 12v9"/></svg>
                </span>
                <h2 class="text-base font-bold text-slate-900 sm:text-lg">Produced stock</h2>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                {{ $processing->outputs->count() }} {{ \Illuminate\Support\Str::plural('line', $processing->outputs->count()) }}
            </span>
        </div>

        @if ($processing->outputs->isEmpty())
            <div class="p-4 sm:p-6">
                <div class="rounded-xl border border-dashed border-indigo-200 bg-indigo-50/40 px-4 py-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 7.5 9-4.5 9 4.5v9L12 21l-9-4.5v-9Z"/><path d="m3.5 7.7 8.5 4.5 8.5-4.5M12 12.2V21"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-bold text-slate-700">No output lines were recorded.</p>
                </div>
            </div>
        @else
            {{-- Mobile output cards --}}
            <div class="space-y-3 bg-slate-50/50 p-3 md:hidden">
                @foreach ($processing->outputs as $output)
                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                        <h3 class="break-words text-sm font-bold text-slate-900">{{ $output->item?->description ?? '—' }}</h3>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-lg border border-emerald-100 bg-emerald-50/70 p-2.5">
                                <p class="text-xs font-semibold text-emerald-700">Quantity produced</p>
                                <p class="mt-1 text-sm font-bold tabular-nums text-emerald-950">
                                    {{ number_format((float) $output->quantity_produced, 3) }}
                                    <span class="text-xs font-normal text-emerald-700">{{ $output->item?->unit }}</span>
                                </p>
                            </div>
                            <div class="rounded-lg border border-blue-100 bg-blue-50/70 p-2.5">
                                <p class="text-xs font-semibold text-blue-700">Allocated cost</p>
                                <p class="mt-1 text-sm font-bold tabular-nums text-blue-950">₦{{ number_format((float) $output->line_cost, 2) }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Tablet and desktop output table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-gradient-to-r from-slate-50 via-white to-indigo-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Stock item</th>
                            <th scope="col" class="px-5 py-3 text-right text-emerald-700">Quantity produced</th>
                            <th scope="col" class="px-5 py-3 text-right text-blue-700">Allocated cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($processing->outputs as $output)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $output->item?->description ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums text-emerald-800">
                                    {{ number_format((float) $output->quantity_produced, 3) }}
                                    {{ $output->item?->unit }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums text-blue-800">₦{{ number_format((float) $output->line_cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Notes --}}
    @if ($processing->notes)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-amber-50 px-4 py-4 sm:px-6">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 3h14v18H5zM8 8h8M8 12h8M8 16h4"/></svg>
                </span>
                <h2 class="text-base font-bold text-slate-900">Notes</h2>
            </div>
            <p class="whitespace-pre-line break-words p-4 text-sm text-slate-700 sm:p-6">{{ $processing->notes }}</p>
        </section>
    @endif
</div>
@endsection