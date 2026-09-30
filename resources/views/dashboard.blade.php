@extends('layouts.app')

@section('content')

<div class="mb-8">
    <p class="mt-1 text-sm text-slate-500">
        Welcome back, {{ auth()->user()->name }}.
    </p>
</div>

<div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">

    {{-- Staff --}}
    @can('viewAny', App\Models\User::class)
        <a
            href="{{ route('staff.index') }}"
            class="block rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-slate-300 hover:shadow"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-sm font-medium text-slate-500">
                        Staff
                    </div>

                    <div class="mt-3 text-2xl font-bold text-slate-900">
                        →
                    </div>

                    <div class="mt-2 text-xs text-slate-500">
                        Staff accounts and access management
                    </div>
                </div>

                <span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600">
                    Manage
                </span>
            </div>
        </a>
    @endcan

    {{-- Organizations --}}
    @can('viewAny', App\Models\Organization::class)
        <a
            href="{{ route('organizations.index') }}"
            class="block rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-slate-300 hover:shadow"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-sm font-medium text-slate-500">
                        Organizations
                    </div>

                    <div class="mt-3 text-2xl font-bold text-slate-900">
                        →
                    </div>

                    <div class="mt-2 text-xs text-slate-500">
                        Organization management
                    </div>
                </div>

                <span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600">
                    Manage
                </span>
            </div>
        </a>
    @endcan

    {{-- Quotations --}}
    @if(auth()->user()->hasPermission('quotations.view'))
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="text-sm font-medium text-slate-500">
                Quotations
            </div>

            <div class="mt-3 text-2xl font-bold text-slate-900">
                —
            </div>

            <div class="mt-2 text-xs text-slate-500">
                Commercial pipeline
            </div>
        </div>
    @endif

    {{-- Orders --}}
    @can('viewAny', App\Models\Order::class)
        <a
            href="{{ route('orders.index') }}"
            class="block rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-slate-300 hover:shadow"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-sm font-medium text-slate-500">
                        Orders
                    </div>

                    <div class="mt-3 text-2xl font-bold text-slate-900">
                        →
                    </div>

                    <div class="mt-2 text-xs text-slate-500">
                        Order fulfillment and production
                    </div>
                </div>

                <span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600">
                    View
                </span>
            </div>
        </a>
    @endcan

    {{-- Payments --}}
    @if(auth()->user()->hasPermission('payments.view'))
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="text-sm font-medium text-slate-500">
                Payments
            </div>

            <div class="mt-3 text-2xl font-bold text-slate-900">
                —
            </div>

            <div class="mt-2 text-xs text-slate-500">
                Financial activity
            </div>
        </div>
    @endif

</div>

<div class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-base font-semibold text-slate-900">
        Oneyard Outfitters
    </h2>

    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
        Institutional supply and uniform management system.
        Use the navigation to manage organizations, staff and the operational
        pipeline.
    </p>
</div>

@endsection
