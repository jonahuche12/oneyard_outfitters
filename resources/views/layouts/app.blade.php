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

    {{-- Desktop Sidebar --}}
    <aside
        class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col overflow-hidden border-r border-slate-200 bg-white lg:flex"
        aria-label="Application sidebar"
    >

        {{-- Brand --}}
        <div class="flex min-h-[88px] items-center border-b border-slate-200 px-6 py-4">
            <a
                href="{{ route('dashboard') }}"
                class="flex w-full items-center rounded-xl px-2 py-1 transition hover:bg-slate-50"
                aria-label="Oneyard Outfitters dashboard"
            >
                <img
                    src="{{ asset('images/oneyard/oneyard_logo.png') }}"
                    alt="Oneyard Outfitters"
                    class="h-12 w-auto max-w-[190px] object-contain"
                >
            </a>
        </div>

        {{-- Navigation --}}
        <nav
            class="min-h-0 flex-1 overflow-y-auto px-5 py-5"
            aria-label="Application navigation"
        >

            {{-- Dashboard --}}
            <a
                href="{{ route('dashboard') }}"
                class="oy-nav-link
                    {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
            >
                <span>Dashboard</span>
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

    {{-- Mobile Navigation --}}
    <div
        id="app-mobile-menu"
        class="pointer-events-none fixed inset-0 z-[60] invisible lg:hidden"
        aria-hidden="true"
    >

        {{-- Backdrop --}}
        <button
            id="app-mobile-backdrop"
            type="button"
            class="absolute inset-0 bg-slate-950/50 opacity-0 transition-opacity duration-300"
            aria-label="Close navigation menu"
            tabindex="-1"
        ></button>

        {{-- Mobile Sidebar --}}
        <aside
            id="app-mobile-sidebar"
            class="absolute left-0 top-0 flex h-full w-[min(86vw,320px)] -translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300 ease-out"
            aria-label="Application navigation"
            aria-modal="true"
            role="dialog"
        >

            {{-- Sidebar Header --}}
            <div class="flex min-h-[88px] shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">

                <a
                    href="{{ route('dashboard') }}"
                    class="flex items-center rounded-xl px-1 py-1 transition hover:bg-slate-50"
                    aria-label="Oneyard Outfitters dashboard"
                >
                    <img
                        src="{{ asset('images/oneyard/oneyard_logo.png') }}"
                        alt="Oneyard Outfitters"
                        class="h-11 w-auto max-w-[175px] object-contain"
                    >
                </a>

                {{-- Close --}}
                <button
                    id="app-mobile-menu-close"
                    type="button"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950"
                    aria-label="Close navigation menu"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 6l12 12M18 6L6 18"
                        />
                    </svg>
                </button>

            </div>

            {{-- Navigation --}}
            <nav
                class="flex-1 overflow-y-auto px-4 py-5"
                aria-label="Application navigation"
            >

                <div class="space-y-1">

                    {{-- Dashboard --}}
                    <a
                        href="{{ route('dashboard') }}"
                        class="oy-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
                        data-app-mobile-menu-link
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
                            class="oy-nav-link {{ request()->routeIs('organizations.*') ? 'is-active' : '' }}"
                            data-app-mobile-menu-link
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
                            class="oy-nav-link {{ request()->routeIs('orders.*') ? 'is-active' : '' }}"
                            data-app-mobile-menu-link
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
                            class="oy-nav-link {{ request()->routeIs('quality-control.*') ? 'is-active' : '' }}"
                            data-app-mobile-menu-link
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
                            class="oy-nav-link {{ request()->routeIs('procurements.*') ? 'is-active' : '' }}"
                            data-app-mobile-menu-link
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
                            class="oy-nav-link {{ request()->routeIs('staff.*') ? 'is-active' : '' }}"
                            data-app-mobile-menu-link
                        >
                            <span>Staff</span>

                            @if(request()->routeIs('staff.*'))
                                <span class="text-xs opacity-70">Active</span>
                            @endif
                        </a>
                    @endcan

                </div>

            </nav>

            {{-- Mobile User --}}
            <div class="border-t border-slate-200 p-4">

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
    </div>

    {{-- Main Application Area --}}
    <div class="oy-main flex min-w-0 flex-1 flex-col">

        {{-- Top Header --}}
        <header
            class="oy-topbar sticky top-0 z-50 px-4 sm:px-6 lg:px-8"
        >

            <div class="flex min-w-0 items-center gap-4">

                {{-- Mobile Menu Button --}}
                <button
                    id="app-mobile-menu-open"
                    type="button"
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-950 transition hover:border-slate-300 hover:bg-slate-50 lg:hidden"
                    aria-label="Open navigation menu"
                    aria-controls="app-mobile-sidebar"
                    aria-expanded="false"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                </button>

                {{-- Mobile Brand --}}
                <a
                    href="{{ route('dashboard') }}"
                    class="flex min-w-0 items-center lg:hidden"
                    aria-label="Oneyard Outfitters dashboard"
                >
                    <img
                        src="{{ asset('images/oneyard/oneyard_logo.png') }}"
                        alt="Oneyard Outfitters"
                        class="h-10 w-auto max-w-[160px] object-contain"
                    >
                </a>

                <div class="hidden min-w-0 lg:block">
                    <h1 class="text-lg font-semibold text-slate-900">
                        {{ $title ?? 'Dashboard' }}
                    </h1>
                </div>
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
