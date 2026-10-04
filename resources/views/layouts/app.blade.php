<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Dashboard' }} — Oneyard Outfitters</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">

<div class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col">

        {{-- Brand --}}
        <div class="flex h-16 items-center border-b border-slate-200 px-6">
            <div>
                <div class="text-lg font-bold tracking-tight text-slate-900">
                    Oneyard 
                </div>

                <div class="text-xs font-medium text-amber-700">
                    Outfitters
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 space-y-1 p-4">

            {{-- Dashboard --}}
            <a
                href="{{ route('dashboard') }}"
                class="oy-nav-link
                    {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
            >
                <span>Dashboard</span>

                @if(request()->routeIs('dashboard'))
                    <span class="text-xs opacity-70">Active</span>
                @endif
            </a>

            {{-- Organizations --}}
            @can('viewAny', App\Models\Organization::class)
                <a
                    href="{{ route('organizations.index') }}"
                    class="oy-nav-link
                        {{ request()->routeIs('organizations.*') ? 'is-active' : '' }}"
                >
                    <span>Organizations</span>

                    @if(request()->routeIs('organizations.*'))
                        <span class="text-xs opacity-70">Active</span>
                    @endif
                </a>
            @endcan

            {{-- Orders --}}
            @can('viewAny', App\Models\Order::class)
                <a
                    href="{{ route('orders.index') }}"
                    class="oy-nav-link
                        {{ request()->routeIs('orders.*') ? 'is-active' : '' }}"
                >
                    <span>Orders</span>

                    @if(request()->routeIs('orders.*'))
                        <span class="text-xs opacity-70">Active</span>
                    @endif
                </a>
            @endcan

            {{-- Quality Control --}}
            @can('viewQualityControl', App\Models\Order::class)
                <a
                    href="{{ route('quality-control.index') }}"
                    class="oy-nav-link
                        {{ request()->routeIs('quality-control.*') ? 'is-active' : '' }}"
                >
                    <span>Quality Control</span>

                    @if(request()->routeIs('quality-control.*'))
                        <span class="text-xs opacity-70">Active</span>
                    @endif
                </a>
            @endcan

            {{-- Procurement --}}
            @can('viewAny', App\Models\Procurement::class)
                <a
                    href="{{ route('procurements.index') }}"
                    class="oy-nav-link
                        {{ request()->routeIs('procurements.*') ? 'is-active' : '' }}"
                >
                    <span>Procurement</span>

                    @if(request()->routeIs('procurements.*'))
                        <span class="text-xs opacity-70">Active</span>
                    @endif
                </a>
            @endcan

            {{-- Staff --}}
            @can('viewAny', App\Models\User::class)
                <a
                    href="{{ route('staff.index') }}"
                    class="oy-nav-link
                        {{ request()->routeIs('staff.*') ? 'is-active' : '' }}"
                >
                    <span>Staff</span>

                    @if(request()->routeIs('staff.*'))
                        <span class="text-xs opacity-70">Active</span>
                    @endif
                </a>
            @endcan

        </nav>

        {{-- Current User --}}
        <div class="oy-user-panel">

            <div class="mb-3 truncate text-sm font-medium text-slate-800">
                {{ auth()->user()->name }}
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="oy-btn oy-btn-secondary oy-btn-block"
                >
                    Sign out
                </button>
            </form>

        </div>

    </aside>

    {{-- Main Application Area --}}
    <div class="oy-main flex min-w-0 flex-1 flex-col">

        {{-- Top Header --}}
        <header class="oy-topbar">

            <div>
                <h1 class="text-lg font-semibold text-slate-900">
                    {{ $title ?? 'Dashboard' }}
                </h1>
            </div>

            <div class="flex items-center gap-3">

                <div class="hidden text-right sm:block">
                    <div class="text-sm font-medium text-slate-900">
                        {{ auth()->user()->name }}
                    </div>

                    <div class="text-xs text-slate-500">
                        {{ auth()->user()->email }}
                    </div>
                </div>

                <div
                    class="oy-avatar oy-avatar-round"
                    aria-label="{{ auth()->user()->name }}"
                >
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>

            </div>

        </header>

        {{-- Success Message --}}
        @if(session('status'))
            <div class="border-b border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 sm:px-6">
                {{ session('status') }}
            </div>
        @endif

        {{-- Validation / Error Message --}}
        @if($errors->any())
            <div class="border-b border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 sm:px-6">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Page Content --}}
        <main class="oy-content min-h-0 flex-1">

            {{ $slot ?? '' }}

            @yield('content')

        </main>

    </div>

</div>

</body>
</html>
