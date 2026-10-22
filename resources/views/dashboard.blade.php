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
                    <span class="oy-dashboard-icon oy-dashboard-icon-indigo"><x-ui.icon name="users" class="h-5 w-5" /></span>
                    <span class="oy-dashboard-action">Manage<x-ui.icon name="arrow-left" class="h-3.5 w-3.5 rotate-180" /></span>
                </div>

                <div class="oy-dashboard-card-title">Staff</div>
                <div class="oy-dashboard-card-description">
                    Staff accounts and access management
                </div>
            </a>
        @endcan

        {{-- Organizations --}}
        @can('viewAny', App\Models\Organization::class)
            <a href="{{ route('organizations.index') }}" class="oy-dashboard-card oy-dashboard-card-sky">
                <div class="flex items-start justify-between gap-4">
                    <span class="oy-dashboard-icon oy-dashboard-icon-sky"><x-ui.icon name="building" class="h-5 w-5" /></span>
                    <span class="oy-dashboard-action">Manage<x-ui.icon name="arrow-left" class="h-3.5 w-3.5 rotate-180" /></span>
                </div>

                <div class="oy-dashboard-card-title">Organizations</div>
                <div class="oy-dashboard-card-description">
                    Organization records, contacts and requirements
                </div>
            </a>
        @endcan

        {{-- Leads --}}
        @can('viewAny', App\Models\Lead::class)
            <a href="{{ route('leads.index') }}" class="oy-dashboard-card oy-dashboard-card-violet">
                <div class="flex items-start justify-between gap-4">
                    <span class="oy-dashboard-icon oy-dashboard-icon-violet"><x-ui.icon name="inbox" class="h-5 w-5" /></span>
                    <span class="oy-dashboard-action">Manage<x-ui.icon name="arrow-left" class="h-3.5 w-3.5 rotate-180" /></span>
                </div>

                <div class="oy-dashboard-card-title">Leads</div>
                <div class="oy-dashboard-card-description">
                    Enquiries and prospects to convert into organizations
                </div>
            </a>
        @endcan

        {{-- Quotations --}}
        @if (auth()->user()->hasPermission('quotations.view'))
            <a href="{{ route('quotations.index') }}" class="oy-dashboard-card oy-dashboard-card-amber">
                <div class="flex items-start justify-between gap-4">
                    <span class="oy-dashboard-icon oy-dashboard-icon-amber"><x-ui.icon name="document" class="h-5 w-5" /></span>
                    <span class="oy-dashboard-action">View<x-ui.icon name="arrow-left" class="h-3.5 w-3.5 rotate-180" /></span>
                </div>

                <div class="oy-dashboard-card-title">Quotations</div>
                <div class="oy-dashboard-card-description">
                    Commercial pipeline
                </div>
            </a>
        @endif

        {{-- Orders --}}
        @can('viewAny', App\Models\Order::class)
            <a href="{{ route('orders.index') }}" class="oy-dashboard-card oy-dashboard-card-emerald">
                <div class="flex items-start justify-between gap-4">
                    <span class="oy-dashboard-icon oy-dashboard-icon-emerald"><x-ui.icon name="clipboard" class="h-5 w-5" /></span>
                    <span class="oy-dashboard-action">View<x-ui.icon name="arrow-left" class="h-3.5 w-3.5 rotate-180" /></span>
                </div>

                <div class="oy-dashboard-card-title">Orders</div>
                <div class="oy-dashboard-card-description">
                    Order fulfillment and production
                </div>
            </a>
        @endcan

        {{-- Payments --}}
        @if (auth()->user()->hasPermission('payments.view'))
            <div class="oy-dashboard-card oy-dashboard-card-muted">
                <div class="flex items-start justify-between gap-4">
                    <span class="oy-dashboard-icon oy-dashboard-icon-muted"><x-ui.icon name="wallet" class="h-5 w-5" /></span>
                    <span class="oy-dashboard-status">Coming soon</span>
                </div>

                <div class="oy-dashboard-card-title">Payments</div>
                <div class="oy-dashboard-card-description">
                    Financial activity
                </div>
            </div>
        @endif

    </div>

    <div class="oy-card oy-dashboard-note mt-8">
        <div class="oy-card-body">
            <h2 class="oy-card-title">Oneyard Outfitters</h2>

            <p class="oy-dashboard-note-text">
                Institutional supply and uniform management system.
                Use the navigation to manage organizations, staff and the operational pipeline.
            </p>
        </div>
    </div>

</div>
@endsection
