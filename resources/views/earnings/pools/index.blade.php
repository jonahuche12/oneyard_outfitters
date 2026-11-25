@extends('layouts.app')

@section('content')
@php
    $inputClass = 'block min-h-10 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 sm:min-h-12 sm:py-3 sm:text-base';
    $btnPrimary = 'inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:min-h-12 sm:py-3';
    $btnSecondary = 'inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:min-h-12 sm:py-3';
    $btnRow = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1';
@endphp

<div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Hero --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-4 py-5 sm:px-7 sm:py-6">
            <h1 class="break-words text-xl font-bold tracking-tight text-white sm:text-3xl">Earnings</h1>
            <p class="mt-1.5 text-xs leading-5 text-slate-300 sm:mt-2 sm:text-sm sm:leading-6">
                Staff earnings pools for delivered orders.
            </p>
        </div>
    </div>

    @foreach (['status_message', 'success'] as $flashKey)
        @if (session($flashKey))
            <div role="status" class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs leading-5 text-emerald-800 sm:text-sm sm:leading-6">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                </span>
                <span>{{ session($flashKey) }}</span>
            </div>
        @endif
    @endforeach

    {{-- Filters --}}
    <form method="GET" action="{{ route('earnings.pools.index') }}"
          class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:gap-3 sm:p-4">
        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </span>
            <input type="text" name="q" value="{{ $search }}" placeholder="Search order number" aria-label="Search order number"
                   class="{{ $inputClass }} pl-9">
        </div>
        <select name="status" aria-label="Status" class="{{ $inputClass }} sm:w-48">
            <option value="">All statuses</option>
            @foreach (['pending' => 'Pending', 'held' => 'Held', 'finalized' => 'Finalized'] as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2 sm:gap-3">
            <button type="submit" class="{{ $btnPrimary }} flex-1 sm:flex-none">Filter</button>
            @if ($search !== '' || $status)
                <a href="{{ route('earnings.pools.index') }}" class="{{ $btnSecondary }} flex-1 sm:flex-none">Clear</a>
            @endif
        </div>
    </form>

    {{-- Pools --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700 sm:h-10 sm:w-10">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M15 8.5c-.6-.6-1.5-.9-2.5-.9-1.4 0-2.5.7-2.5 1.8 0 2.8 5 1.1 5 4 0 1.2-1.1 2-2.7 2-1 0-2-.4-2.8-1.1M12 6v12"/></svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Earnings pools</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:mt-1 sm:text-sm">Pool amount, contributors and payment state.</p>
                </div>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 sm:px-3 sm:py-1.5 sm:text-xs">
                {{ $pools->count() }} shown
            </span>
        </div>

        @if ($pools->isEmpty())
            <div class="m-4 flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center sm:m-6">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M15 8.5c-.6-.6-1.5-.9-2.5-.9-1.4 0-2.5.7-2.5 1.8 0 2.8 5 1.1 5 4 0 1.2-1.1 2-2.7 2-1 0-2-.4-2.8-1.1M12 6v12"/></svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-800">No earnings pools found.</p>
                <p class="mt-1 text-xs text-slate-500">A pool is created when an order is delivered.</p>
            </div>
        @else
            {{-- Mobile cards --}}
            <div class="space-y-2.5 p-3 lg:hidden">
                @foreach ($pools as $pool)
                    @php $covered = $paymentState[$pool->id]['covered']; @endphp
                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 bg-slate-50/80 px-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-slate-950">{{ $pool->order?->order_number ?? '—' }}</p>
                                <p class="truncate text-[11px] text-slate-500">{{ $pool->order?->organization?->name ?? '—' }}</p>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $pool->status === 'finalized' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $pool->status === 'finalized' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                {{ ucfirst($pool->status) }}
                            </span>
                        </div>

                        <dl class="grid grid-cols-2 gap-x-3 gap-y-2 px-3 py-2.5">
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Pool</dt>
                                <dd class="mt-0.5 break-words text-xs font-bold tabular-nums text-violet-900">₦{{ number_format((float) $pool->pool_amount, 2) }}</dd>
                            </div>
                            <div class="text-right">
                                <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Delivered</dt>
                                <dd class="mt-0.5 text-xs font-semibold text-slate-900">{{ $pool->delivered_at?->format('d M Y') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Contributors</dt>
                                <dd class="mt-0.5 text-xs font-semibold tabular-nums text-slate-900">{{ $pool->contributions_count }}</dd>
                            </div>
                            <div class="text-right">
                                <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Payment</dt>
                                <dd class="mt-0.5">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $covered ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ $covered ? 'Paid' : 'Outstanding' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>

                        <div class="border-t border-slate-100 bg-slate-50/70 p-2.5">
                            <a href="{{ route('earnings.pools.show', $pool) }}" class="{{ $btnRow }} w-full">View</a>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Order</th>
                            <th scope="col" class="px-4 py-3">Organization</th>
                            <th scope="col" class="px-4 py-3">Delivered</th>
                            <th scope="col" class="px-4 py-3 text-right">Pool</th>
                            <th scope="col" class="px-4 py-3 text-right">Contributors</th>
                            <th scope="col" class="px-4 py-3">Payment</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($pools as $pool)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-950">{{ $pool->order?->order_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $pool->order?->organization?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $pool->delivered_at?->format('d M Y') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-slate-950">₦{{ number_format((float) $pool->pool_amount, 2) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ $pool->contributions_count }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @php $covered = $paymentState[$pool->id]['covered']; @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $covered ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ $covered ? 'Paid' : 'Outstanding' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $pool->status === 'finalized' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $pool->status === 'finalized' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                        {{ ucfirst($pool->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('earnings.pools.show', $pool) }}" class="{{ $btnRow }}">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-4 py-4 sm:px-6">{{ $pools->links() }}</div>
        @endif
    </section>
</div>
@endsection