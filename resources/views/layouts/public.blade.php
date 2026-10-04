<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Oneyard Outfitters' }}</title>

    <meta
        name="description"
        content="{{ $description ?? 'Oneyard Outfitters provides institutional supply, uniforms, clothing and procurement services from specification through production and delivery.' }}"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-900 antialiased">

    <!-- Header -->
    <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-6 lg:px-8">

            <!-- Brand -->
            <a
                href="{{ url('/') }}"
                class="flex items-center gap-3"
                aria-label="Oneyard Outfitters home"
            >
                <img
                    src="{{ asset('images/oneyard/oneyard_logo.png') }}"
                    alt="Oneyard Outfitters"
                    class="h-11 w-auto object-contain"
                >
            </a>

            <!-- Desktop Navigation -->
            <nav
                class="hidden items-center gap-7 md:flex"
                aria-label="Main navigation"
            >
                <a
                    href="{{ url('/') }}"
                    class="text-sm font-semibold text-slate-950 transition hover:text-amber-700"
                >
                    Home
                </a>

                <a
                    href="{{ url('/about') }}"
                    class="text-sm font-medium text-slate-600 transition hover:text-slate-950"
                >
                    About
                </a>

                <a
                    href="{{ url('/how-it-works') }}"
                    class="text-sm font-medium text-slate-600 transition hover:text-slate-950"
                >
                    How It Works
                </a>

                <a
                    href="{{ url('/faq') }}"
                    class="text-sm font-medium text-slate-600 transition hover:text-slate-950"
                >
                    FAQ
                </a>

                <a
                    href="{{ url('/contact') }}"
                    class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800"
                >
                    Contact Us
                </a>
            </nav>

            <!-- Mobile Menu Button -->
            <button
                id="public-mobile-menu-open"
                type="button"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-950 transition hover:border-slate-300 hover:bg-slate-50 md:hidden"
                aria-label="Open navigation menu"
                aria-controls="public-mobile-sidebar"
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
        </div>
    </header>

    <!-- Mobile Navigation Overlay -->
    <div
        id="public-mobile-menu"
        class="pointer-events-none fixed inset-0 z-[60] invisible"
        aria-hidden="true"
    >

        <!-- Backdrop -->
        <button
            id="public-mobile-backdrop"
            type="button"
            class="absolute inset-0 bg-slate-950/50 opacity-0 transition-opacity duration-300"
            aria-label="Close navigation menu"
            tabindex="-1"
        ></button>

        <!-- Sidebar -->
        <aside
            id="public-mobile-sidebar"
            class="absolute right-0 top-0 flex h-full w-[min(86vw,380px)] translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300 ease-out"
            aria-label="Mobile navigation"
            aria-modal="true"
            role="dialog"
        >

            <!-- Sidebar Header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-5">
                <a
                    href="{{ url('/') }}"
                    class="flex items-center gap-3"
                    data-mobile-menu-link
                >
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-950 text-sm font-black tracking-tight text-white">
                        OY
                    </span>

                    <span>
                        <span class="block text-base font-bold tracking-tight text-slate-950">
                            Oneyard
                        </span>
                        <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">
                            Outfitters
                        </span>
                    </span>
                </a>

                <!-- Close Button -->
                <button
                    id="public-mobile-menu-close"
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

            <!-- Sidebar Navigation -->
            <nav
                class="flex-1 overflow-y-auto px-5 py-6"
                aria-label="Mobile main navigation"
            >
                <div class="space-y-2">

                    <a
                        href="{{ url('/') }}"
                        class="flex items-center justify-between rounded-xl bg-slate-950 px-4 py-3.5 text-sm font-bold text-white"
                        data-mobile-menu-link
                    >
                        <span>Home</span>

                        <span aria-hidden="true">→</span>
                    </a>

                    <a
                        href="{{ url('/about') }}"
                        class="flex items-center justify-between rounded-xl px-4 py-3.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-950"
                        data-mobile-menu-link
                    >
                        <span>About</span>
                        <span aria-hidden="true">→</span>
                    </a>

                    <a
                        href="{{ url('/how-it-works') }}"
                        class="flex items-center justify-between rounded-xl px-4 py-3.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-950"
                        data-mobile-menu-link
                    >
                        <span>How It Works</span>
                        <span aria-hidden="true">→</span>
                    </a>

                    <a
                        href="{{ url('/faq') }}"
                        class="flex items-center justify-between rounded-xl px-4 py-3.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-950"
                        data-mobile-menu-link
                    >
                        <span>FAQ</span>
                        <span aria-hidden="true">→</span>
                    </a>

                    <a
                        href="{{ url('/contact') }}"
                        class="mt-5 flex items-center justify-between rounded-xl bg-amber-600 px-4 py-3.5 text-sm font-bold text-white transition hover:bg-amber-700"
                        data-mobile-menu-link
                    >
                        <span>Contact Us</span>
                        <span aria-hidden="true">→</span>
                    </a>

                </div>

                <div class="mt-10 rounded-2xl bg-slate-950 p-5 text-white">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-400">
                        Oneyard Outfitters
                    </p>

                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        Uniforms, institutional supplies, clothing and procurement
                        coordinated from requirement through delivery.
                    </p>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="border-t border-slate-200 px-5 py-5">
                <p class="text-xs leading-5 text-slate-500">
                    Tell us what your organization needs and we will help you
                    structure the requirement.
                </p>
            </div>
        </aside>
    </div>

    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-slate-950 text-white">
        <div class="mx-auto max-w-7xl px-5 py-12 sm:px-6 lg:px-8">

            <div class="grid gap-10 md:grid-cols-3">

                <div>
                    <div class="text-lg font-bold tracking-tight">
                        Oneyard Outfitters
                    </div>

                    <p class="mt-3 max-w-sm text-sm leading-6 text-slate-400">
                        Institutional supply, uniforms, clothing and procurement —
                        from specification through production and delivery.
                    </p>
                </div>

                <div>
                    <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-amber-400">
                        Explore
                    </h2>

                    <div class="mt-4 space-y-2 text-sm">
                        <a href="{{ url('/') }}" class="block text-slate-300 hover:text-white">
                            Home
                        </a>

                        <a href="{{ url('/about') }}" class="block text-slate-300 hover:text-white">
                            About
                        </a>

                        <a href="{{ url('/how-it-works') }}" class="block text-slate-300 hover:text-white">
                            How It Works
                        </a>

                        <a href="{{ url('/faq') }}" class="block text-slate-300 hover:text-white">
                            FAQ
                        </a>

                        <a href="{{ url('/contact') }}" class="block text-slate-300 hover:text-white">
                            Contact
                        </a>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-amber-400">
                        Business
                    </h2>

                    <p class="mt-4 text-sm leading-6 text-slate-400">
                        Talk to us about uniforms, institutional supplies,
                        clothing requirements or procurement needs.
                    </p>

                    <a
                        href="{{ url('/contact') }}"
                        class="mt-5 inline-flex items-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-slate-100"
                    >
                        Start a Conversation
                    </a>
                </div>

            </div>

            <div class="mt-10 border-t border-slate-800 pt-6 text-xs text-slate-500">
                © {{ date('Y') }} Oneyard Outfitters. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Public Mobile Navigation -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menu = document.getElementById('public-mobile-menu');
            const sidebar = document.getElementById('public-mobile-sidebar');
            const openButton = document.getElementById('public-mobile-menu-open');
            const closeButton = document.getElementById('public-mobile-menu-close');
            const backdrop = document.getElementById('public-mobile-backdrop');
            const links = document.querySelectorAll('[data-mobile-menu-link]');

            if (!menu || !sidebar || !openButton || !closeButton || !backdrop) {
                return;
            }

            function openMenu() {
                menu.classList.remove('invisible');
                menu.classList.remove('pointer-events-none');
                menu.setAttribute('aria-hidden', 'false');

                openButton.setAttribute('aria-expanded', 'true');

                requestAnimationFrame(function () {
                    sidebar.classList.remove('translate-x-full');
                    backdrop.classList.remove('opacity-0');
                });

                document.body.classList.add('overflow-hidden');

                window.setTimeout(function () {
                    closeButton.focus();
                }, 100);
            }

            function closeMenu() {
                sidebar.classList.add('translate-x-full');
                backdrop.classList.add('opacity-0');

                menu.setAttribute('aria-hidden', 'true');
                openButton.setAttribute('aria-expanded', 'false');

                document.body.classList.remove('overflow-hidden');

                window.setTimeout(function () {
                    menu.classList.add('invisible');
                    menu.classList.add('pointer-events-none');
                }, 300);

                openButton.focus();
            }

            openButton.addEventListener('click', openMenu);
            closeButton.addEventListener('click', closeMenu);
            backdrop.addEventListener('click', closeMenu);

            links.forEach(function (link) {
                link.addEventListener('click', closeMenu);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && menu.getAttribute('aria-hidden') === 'false') {
                    closeMenu();
                }
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 768 && menu.getAttribute('aria-hidden') === 'false') {
                    closeMenu();
                }
            });
        });
    </script>

<!-- Oneyard WhatsApp CTA -->
<a
    href="https://wa.me/2349036518913"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Chat with Oneyard Outfitters on WhatsApp"
    class="fixed bottom-5 right-5 z-50 inline-flex items-center gap-2 rounded-full bg-amber-400 px-4 py-3 text-sm font-semibold text-slate-950 shadow-lg transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 sm:px-5"
>
    <span aria-hidden="true" class="text-base">WhatsApp</span>
    <span class="hidden sm:inline">Chat with us</span>
</a>

</body>
</html>
