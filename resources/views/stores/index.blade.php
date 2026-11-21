@extends('layouts.app')

@section('content')
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Stores</h1>

                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                        {{ $stores->total() }}
                    </span>
                </div>

                <p class="mt-1 text-sm text-slate-600">
                    The general warehouse and every store, with their coordinators and staff.
                </p>
            </div>

            @can('create', App\Models\Store::class)
                <a href="{{ route('stores.create') }}" class="oy-btn oy-btn-primary">+ Add Store</a>
            @endcan
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Search --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('stores.index') }}" class="flex flex-col gap-3 sm:flex-row">
                <div class="min-w-0 flex-1">
                    <label for="store-search" class="sr-only">Search stores</label>
                    <input
                        id="store-search"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by name or code"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                    >
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="oy-btn oy-btn-primary">Search</button>

                    @if ($search !== '')
                        <a href="{{ route('stores.index') }}" class="oy-btn oy-btn-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- List --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @if ($stores->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-slate-600">
                    {{ $search !== '' ? 'No stores match your search.' : 'No stores have been created yet.' }}
                </div>
            @else
                {{-- Mobile cards --}}
                <div class="space-y-3 p-3 md:hidden">
                    @foreach ($stores as $store)
                        <article class="rounded-lg border border-slate-200 bg-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a
                                        href="{{ route('stores.show', $store) }}"
                                        class="break-words text-sm font-semibold text-slate-900 hover:underline"
                                    >
                                        {{ $store->name }}
                                    </a>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $store->code }}</p>
                                </div>

                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $store->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                    {{ $store->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-x-3 gap-y-3 text-sm">
                                <div>
                                    <p class="text-xs text-slate-500">Type</p>
                                    <p class="mt-1 font-medium text-slate-800">
                                        {{ $store->isWarehouse() ? 'General Store' : 'Store' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-500">Staff</p>
                                    <p class="mt-1 font-medium tabular-nums text-slate-800">
                                        {{ $store->active_staff_count }}
                                    </p>
                                </div>

                                <div class="col-span-2 min-w-0">
                                    <p class="text-xs text-slate-500">Coordinator</p>
                                    @if ($store->coordinatorAssignment)
                                        <p class="mt-1 break-words font-medium text-slate-800">
                                            {{ $store->coordinatorAssignment->user->name }}
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs font-medium text-amber-700">No coordinator assigned</p>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 border-t border-slate-100 pt-3">
                                <a
                                    href="{{ route('stores.show', $store) }}"
                                    class="inline-flex min-h-9 items-center text-sm font-semibold text-slate-700 hover:text-slate-900"
                                >
                                    View store →
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Tablet and desktop table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-3">Store</th>
                                <th scope="col" class="px-4 py-3">Type</th>
                                <th scope="col" class="px-4 py-3">Coordinator</th>
                                <th scope="col" class="px-4 py-3">Staff</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($stores as $store)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('stores.show', $store) }}" class="font-medium text-slate-900 hover:underline">
                                            {{ $store->name }}
                                        </a>
                                        <div class="text-xs text-slate-500">{{ $store->code }}</div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $store->isWarehouse() ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $store->isWarehouse() ? 'General Store' : 'Store' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-slate-700">
                                        @if ($store->coordinatorAssignment)
                                            {{ $store->coordinatorAssignment->user->name }}
                                        @else
                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">No coordinator</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $store->active_staff_count }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $store->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                            {{ $store->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('stores.show', $store) }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($stores->hasPages())
            <div>{{ $stores->links() }}</div>
        @endif
    </div>
@endsection
