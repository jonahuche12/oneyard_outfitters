@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('stores.show', $store) }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">
                ← {{ $store->name }}
            </a>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Customers</h1>
            <p class="mt-1 text-sm text-slate-500">Customer records belonging to {{ $store->name }}.</p>
        </div>
        <a href="{{ route('stores.customers.create', $store) }}" class="oy-btn oy-btn-primary">Register customer</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('stores.customers.index', $store) }}"
          class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
        <input type="search" name="search" value="{{ $search }}"
               placeholder="Search name, phone or customer code"
               class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        <button type="submit" class="oy-btn oy-btn-secondary">Search</button>
        @if ($search !== '')
            <a href="{{ route('stores.customers.index', $store) }}" class="oy-btn oy-btn-secondary">Clear</a>
        @endif
    </form>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @if ($customers->isEmpty())
            <div class="px-6 py-14 text-center">
                <h2 class="font-semibold text-slate-900">No customers found</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Register customers here even when they have not made a purchase.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($customers as $customer)
                            <tr>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-slate-900">{{ $customer->name }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $customer->customer_code }}</span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-700">{{ $customer->phone }}</td>
                                <td class="px-5 py-4 text-sm text-slate-700">{{ $customer->email ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $customer->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4">{{ $customers->links() }}</div>
        @endif
    </section>
</div>
@endsection
