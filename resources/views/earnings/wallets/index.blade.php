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
            <h1 class="break-words text-xl font-bold tracking-tight text-white sm:text-3xl">Staff Wallets</h1>
            <p class="mt-1.5 text-xs leading-5 text-slate-300 sm:mt-2 sm:text-sm sm:leading-6">Balances from earnings and commission.</p>
        </div>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('earnings.wallets.index') }}"
          class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:gap-3 sm:p-4">
        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </span>
            <input type="text" name="q" value="{{ $search }}" placeholder="Search name or email" aria-label="Search name or email"
                   class="{{ $inputClass }} pl-9">
        </div>
        <div class="flex gap-2 sm:gap-3">
            <button type="submit" class="{{ $btnPrimary }} flex-1 sm:flex-none">Search</button>
            @if ($search !== '')
                <a href="{{ route('earnings.wallets.index') }}" class="{{ $btnSecondary }} flex-1 sm:flex-none">Clear</a>
            @endif
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700 sm:h-10 sm:w-10">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18M16 15h2"/></svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Wallet balances</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:mt-1 sm:text-sm">Current balance and last activity per staff member.</p>
                </div>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 sm:px-3 sm:py-1.5 sm:text-xs">
                {{ $users->count() }} shown
            </span>
        </div>

        @if ($users->isEmpty())
            <div class="m-4 flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center sm:m-6">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-800">No staff found.</p>
            </div>
        @else
            {{-- Mobile cards --}}
            <div class="space-y-2.5 p-3 md:hidden">
                @foreach ($users as $person)
                    @php $balance = (float) ($totals[$person->id]->balance ?? 0); @endphp
                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center gap-2.5 px-3 py-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-bold text-indigo-700" aria-hidden="true">{{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-slate-950">{{ $person->name }}</p>
                                @unless ($person->is_active)
                                    <span class="mt-0.5 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Inactive</span>
                                @endunless
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 border-t border-slate-100 bg-slate-50/70 p-2.5">
                            <div class="min-w-0 rounded-lg border px-2.5 py-2 {{ $balance < 0 ? 'border-red-100 bg-gradient-to-br from-red-50 to-white' : 'border-violet-100 bg-gradient-to-br from-violet-50 to-white' }}">
                                <p class="text-[10px] font-semibold uppercase tracking-wide {{ $balance < 0 ? 'text-red-800' : 'text-violet-800' }}">Balance</p>
                                <p class="mt-1 break-words text-xs font-bold tabular-nums {{ $balance < 0 ? 'text-red-950' : 'text-violet-950' }}">{{ $balance < 0 ? '-' : '' }}₦{{ number_format(abs($balance), 2) }}</p>
                            </div>
                            <div class="min-w-0 rounded-lg border border-slate-200 bg-white px-2.5 py-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Last entry</p>
                                <p class="mt-1 text-xs font-semibold text-slate-900">{{ isset($totals[$person->id]) ? \Illuminate\Support\Carbon::parse($totals[$person->id]->last_at)->format('d M Y') : '—' }}</p>
                            </div>
                            <a href="{{ route('earnings.wallets.show', $person) }}" class="{{ $btnRow }} col-span-2">View</a>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Staff</th>
                            <th scope="col" class="px-4 py-3 text-right">Balance</th>
                            <th scope="col" class="px-4 py-3">Last entry</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $person)
                            @php $balance = (float) ($totals[$person->id]->balance ?? 0); @endphp
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-bold text-indigo-700" aria-hidden="true">{{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}</span>
                                        <span class="font-bold text-slate-950">{{ $person->name }}</span>
                                        @unless ($person->is_active)
                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">Inactive</span>
                                        @endunless
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-bold tabular-nums {{ $balance < 0 ? 'text-red-700' : 'text-slate-950' }}">{{ $balance < 0 ? '-' : '' }}₦{{ number_format(abs($balance), 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ isset($totals[$person->id]) ? \Illuminate\Support\Carbon::parse($totals[$person->id]->last_at)->format('d M Y') : '—' }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('earnings.wallets.show', $person) }}" class="{{ $btnRow }}">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-4 py-4 sm:px-6">{{ $users->links() }}</div>
        @endif
    </section>
</div>
@endsection