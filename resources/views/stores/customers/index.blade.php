@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

    {{-- Hero --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-4 py-5 sm:px-7 sm:py-6">
            <a href="{{ route('stores.show', $store) }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition hover:text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m15 18-6-6 6-6"/>
                    <path d="M20 12H9"/>
                </svg>
                {{ $store->name }}
            </a>

            <div class="mt-4 flex flex-col gap-4 sm:mt-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h1 class="break-words text-xl font-bold tracking-tight text-white sm:text-3xl">Customers</h1>
                    <p class="mt-1.5 text-xs leading-5 text-slate-300 sm:mt-2 sm:text-sm sm:leading-6">
                        Customer records belonging to {{ $store->name }}.
                    </p>
                </div>

                <a href="{{ route('stores.customers.create', $store) }}"
                   class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl bg-white px-5 py-2 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-white/70 sm:min-h-12 sm:w-auto sm:py-3">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Register customer
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div role="status" class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs leading-5 text-emerald-800 sm:text-sm sm:leading-6">
            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
            </span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Search --}}
    <form method="GET" action="{{ route('stores.customers.index', $store) }}"
          class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:gap-3 sm:p-4">
        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>
            </span>
            <input type="search" name="search" value="{{ $search }}"
                   placeholder="Search name, phone or customer code"
                   class="block min-h-10 w-full rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 sm:min-h-12 sm:py-3 sm:text-base">
        </div>

        <div class="flex gap-2 sm:gap-3">
            <button type="submit"
                    class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:min-h-12 sm:flex-none sm:py-3">
                Search
            </button>
            @if ($search !== '')
                <a href="{{ route('stores.customers.index', $store) }}"
                   class="inline-flex min-h-10 flex-1 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:min-h-12 sm:flex-none sm:py-3">Clear</a>
            @endif
        </div>
    </form>

    {{-- Customer list --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 sm:h-10 sm:w-10">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="9" cy="8" r="3.5"/>
                        <path d="M2.5 20a6.5 6.5 0 0 1 13 0"/>
                        <path d="M16 4.5a3.5 3.5 0 0 1 0 7M18.5 14.5a6.5 6.5 0 0 1 3 5.5"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold tracking-tight text-slate-950 sm:text-base">Customer records</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:mt-1 sm:text-sm">Name, contact details and status.</p>
                </div>
            </div>

            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 sm:px-3 sm:py-1.5 sm:text-xs">
                {{ $customers->count() }} shown
            </span>
        </div>

        @if ($customers->isEmpty())
            <div class="m-4 flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center sm:m-6">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="9" cy="8" r="3.5"/>
                        <path d="M2.5 20a6.5 6.5 0 0 1 13 0"/>
                        <path d="M16 4.5a3.5 3.5 0 0 1 0 7M18.5 14.5a6.5 6.5 0 0 1 3 5.5"/>
                    </svg>
                </span>
                <h2 class="mt-3 text-sm font-semibold text-slate-800">No customers found</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Register customers here even when they have not made a purchase.
                </p>
            </div>
        @else
            {{-- Mobile customer cards --}}
            <div class="space-y-2.5 p-3 md:hidden">
                @foreach ($customers as $customer)
                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center gap-2.5 px-3 py-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-bold text-indigo-700" aria-hidden="true">
                                {{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-slate-950">{{ $customer->name }}</p>
                                <p class="break-all font-mono text-[11px] font-bold text-blue-800">{{ $customer->customer_code }}</p>
                            </div>

                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $customer->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $customer->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ $customer->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="space-y-1.5 border-t border-slate-100 bg-slate-50/70 px-3 py-2.5">
                            <p class="flex items-center gap-2 text-xs text-slate-700">
                                <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>
                                </svg>
                                <span class="min-w-0 break-words font-medium">{{ $customer->phone }}</span>
                            </p>
                            <p class="flex items-center gap-2 text-xs text-slate-700">
                                <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                                    <path d="m3 7 9 6 9-6"/>
                                </svg>
                                <span class="min-w-0 break-all">{{ $customer->email ?: '—' }}</span>
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop customer table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Customer</th>
                            <th scope="col" class="px-4 py-3">Phone</th>
                            <th scope="col" class="px-4 py-3">Email</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($customers as $customer)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-bold text-indigo-700" aria-hidden="true">
                                            {{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <span class="block font-bold text-slate-950">{{ $customer->name }}</span>
                                            <span class="mt-0.5 block break-all font-mono text-xs font-bold text-blue-800">{{ $customer->customer_code }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $customer->phone }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $customer->email ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $customer->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $customer->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-4 py-4 sm:px-6">{{ $customers->links() }}</div>
        @endif
    </section>
</div>
@endsection