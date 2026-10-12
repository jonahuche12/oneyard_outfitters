@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Dashboard</h1>
            <p class="oy-page-description">
                Welcome back, {{ auth()->user()->name }}.
            </p>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

        {{-- Staff --}}
        @can('viewAny', App\Models\User::class)
            <a href="{{ route('staff.index') }}" class="oy-dashboard-card oy-dashboard-card-indigo">
                <div class="flex items-start justify-between gap-4">
                    <span class="oy-dashboard-icon oy-dashboard-icon-indigo">STF</span>
                    <span class="oy-dashboard-action">Manage</span>
                </div>

                <div class="mt-5 text-base font-semibold text-slate-900">Staff</div>
                <div class="mt-1 text-sm leading-5 text-slate-500">
                    Staff accounts and access management
                </div>
            </a>
        @endcan

        {{-- Organizations --}}
        @can('viewAny', App\Models\Organization::class)
            <a href="{{ route('organizations.index') }}" class="oy-dashboard-card oy-dashboard-card-sky">
                <div class="flex items-start justify-between gap-4">
                    <span class="oy-dashboard-icon oy-dashboard-icon-sky">ORG</span>
                    <span class="oy-dashboard-action">Manage</span>
                </div>

                <div class="mt-5 text-base font-semibold text-slate-900">Organizations</div>
                <div class="mt-1 text-sm leading-5 text-slate-500">
                    Organization records, contacts and requirements
                </div>
            </a>
        @endcan

        {{-- Leads --}}
        @can('viewAny', App\Models\Lead::class)
            <a href="{{ route('leads.index') }}" class="oy-dashboard-card oy-dashboard-card-violet">
                <div class="flex items-start justify-between gap-4">
                    <span class="oy-dashboard-icon oy-dashboard-icon-violet">LDS</span>
                    <span class="oy-dashboard-action">Manage</span>
                </div>

                <div class="mt-5 text-base font-semibold text-slate-900">Leads</div>
                <div class="mt-1 text-sm leading-5 text-slate-500">
                    Enquiries and prospects to convert into organizations
                </div>
            </a>
        @endcan

        {{-- Quotations --}}
        @if (auth()->user()->hasPermission('quotations.view'))
            <a href="{{ route('quotations.index') }}" class="oy-dashboard-card oy-dashboard-card-amber">
                <div class="flex items-start justify-between gap-4">
                    <span class="oy-dashboard-icon oy-dashboard-icon-amber">QTN</span>
                    <span class="oy-dashboard-action">View</span>
                </div>

                <div class="mt-5 text-base font-semibold text-slate-900">Quotations</div>
                <div class="mt-1 text-sm leading-5 text-slate-500">
                    Commercial pipeline
                </div>
            </a>
        @endif

        {{-- Orders --}}
        @can('viewAny', App\Models\Order::class)
            <a href="{{ route('orders.index') }}" class="oy-dashboard-card oy-dashboard-card-emerald">
                <div class="flex items-start justify-between gap-4">
                    <span class="oy-dashboard-icon oy-dashboard-icon-emerald">ORD</span>
                    <span class="oy-dashboard-action">View</span>
                </div>

                <div class="mt-5 text-base font-semibold text-slate-900">Orders</div>
                <div class="mt-1 text-sm leading-5 text-slate-500">
                    Order fulfillment and production
                </div>
            </a>
        @endcan

        {{-- Payments --}}
        @if (auth()->user()->hasPermission('payments.view'))
            <div class="oy-dashboard-card oy-dashboard-card-sky">
                <div class="flex items-start justify-between gap-4">
                    <span class="oy-dashboard-icon oy-dashboard-icon-sky">PAY</span>
                    <span class="oy-dashboard-status">Coming soon</span>
                </div>

                <div class="mt-5 text-base font-semibold text-slate-900">Payments</div>
                <div class="mt-1 text-sm leading-5 text-slate-500">
                    Financial activity
                </div>
            </div>
        @endif

    </div>

    <div class="oy-card mt-8">
        <div class="oy-card-body">
            <h2 class="oy-card-title">Oneyard Outfitters</h2>

            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Institutional supply and uniform management system.
                Use the navigation to manage organizations, staff and the operational pipeline.
            </p>
        </div>
    </div>

</div>
@endsection
