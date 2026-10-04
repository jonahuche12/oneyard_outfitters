@extends('layouts.public')

@section('content')

{{-- ================================================================
     IMAGE MAP — WELCOME PAGE
     OY-HOME-01 — Hero / institutional supply and clothing
     OY-HOME-02 — Uniforms and clothing
     OY-HOME-03 — Institutional supplies
     OY-HOME-04 — Procurement / sourcing
     OY-HOME-05 — Production / quality / delivery
     OY-HOME-06 — Closing brand image
     ================================================================ --}}

{{-- ================================================================
     HERO
     ================================================================ --}}
<section class="overflow-hidden bg-slate-950">
    <div class="mx-auto max-w-7xl px-5 py-16 sm:px-6 sm:py-24 lg:px-8 lg:py-28">
        <div class="grid items-center gap-12 lg:grid-cols-[1fr_0.9fr] lg:gap-16">

            {{-- Hero Content --}}
            <div>
                <div class="inline-flex rounded-full border border-slate-700 bg-slate-900 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-amber-400">
                    Oneyard Outfitters
                </div>

                <h1 class="mt-7 max-w-3xl text-4xl font-black leading-[1.04] tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Institutional supply,
                    <span class="text-amber-400">organized properly.</span>
                </h1>

                <p class="mt-7 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg sm:leading-8">
                    Uniforms, clothing, institutional supplies and procurement
                    coordinated from requirement through specification,
                    production, quality control and delivery.
                </p>

                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a
                        href="{{ url('/contact') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-6 py-3.5 text-sm font-bold text-slate-950 transition hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-950"
                    >
                        Discuss Your Requirement
                    </a>

                    <a
                        href="{{ url('/how-it-works') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-900 px-6 py-3.5 text-sm font-bold text-white transition hover:border-slate-500 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 focus:ring-offset-slate-950"
                    >
                        See How It Works
                    </a>
                </div>
            </div>

            {{-- ====================================================
                 HERO CAROUSEL
                 ==================================================== --}}
            <div
                class="group relative"
                data-home-carousel
                aria-roledescription="carousel"
                aria-label="Oneyard Outfitters products and services"
            >
                <div class="relative aspect-[4/3] overflow-hidden rounded-3xl border border-slate-700 bg-slate-900 shadow-2xl">

                    {{-- Slide 1 --}}
                    <div
                        class="absolute inset-0 opacity-100 transition-opacity duration-700 ease-in-out"
                        data-carousel-slide
                        data-slide-index="0"
                        role="group"
                        aria-roledescription="slide"
                        aria-label="1 of 5"
                    >
                        <img
                            src="{{ asset('images/oneyard/home/oy-home-01.webp') }}"
                            alt="Oneyard Outfitters institutional supply and clothing"
                            class="h-full w-full object-cover"
                            loading="eager"
                            decoding="async"
                        >

                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/20 to-transparent"></div>

                        <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                            <div class="text-xs font-bold uppercase tracking-[0.16em] text-amber-400">
                                Institutional Supply
                            </div>

                            <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">
                                Clothing and supply requirements, coordinated.
                            </h2>
                        </div>
                    </div>

                    {{-- Slide 2 --}}
                    <div
                        class="absolute inset-0 opacity-0 transition-opacity duration-700 ease-in-out"
                        data-carousel-slide
                        data-slide-index="1"
                        role="group"
                        aria-roledescription="slide"
                        aria-label="2 of 5"
                        aria-hidden="true"
                    >
                        <img
                            src="{{ asset('images/oneyard/home/oy-home-02.webp') }}"
                            alt="Oneyard Outfitters uniforms and clothing"
                            class="h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                        >

                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/20 to-transparent"></div>

                        <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                            <div class="text-xs font-bold uppercase tracking-[0.16em] text-amber-400">
                                Uniforms & Clothing
                            </div>

                            <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">
                                Clothing requirements built around the organization.
                            </h2>
                        </div>
                    </div>

                    {{-- Slide 3 --}}
                    <div
                        class="absolute inset-0 opacity-0 transition-opacity duration-700 ease-in-out"
                        data-carousel-slide
                        data-slide-index="2"
                        role="group"
                        aria-roledescription="slide"
                        aria-label="3 of 5"
                        aria-hidden="true"
                    >
                        <img
                            src="{{ asset('images/oneyard/home/oy-home-03.webp') }}"
                            alt="Oneyard Outfitters institutional supplies"
                            class="h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                        >

                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/20 to-transparent"></div>

                        <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                            <div class="text-xs font-bold uppercase tracking-[0.16em] text-amber-400">
                                Institutional Supplies
                            </div>

                            <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">
                                More than uniforms — broader supply needs.
                            </h2>
                        </div>
                    </div>

                    {{-- Slide 4 --}}
                    <div
                        class="absolute inset-0 opacity-0 transition-opacity duration-700 ease-in-out"
                        data-carousel-slide
                        data-slide-index="3"
                        role="group"
                        aria-roledescription="slide"
                        aria-label="4 of 5"
                        aria-hidden="true"
                    >
                        <img
                            src="{{ asset('images/oneyard/home/oy-home-04.webp') }}"
                            alt="Oneyard Outfitters procurement and sourcing"
                            class="h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                        >

                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/20 to-transparent"></div>

                        <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                            <div class="text-xs font-bold uppercase tracking-[0.16em] text-amber-400">
                                Procurement
                            </div>

                            <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">
                                Defined requirements. Structured sourcing.
                            </h2>
                        </div>
                    </div>

                    {{-- Slide 5 --}}
                    <div
                        class="absolute inset-0 opacity-0 transition-opacity duration-700 ease-in-out"
                        data-carousel-slide
                        data-slide-index="4"
                        role="group"
                        aria-roledescription="slide"
                        aria-label="5 of 5"
                        aria-hidden="true"
                    >
                        <img
                            src="{{ asset('images/oneyard/home/oy-home-05.webp') }}"
                            alt="Oneyard Outfitters production, quality control and delivery"
                            class="h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                        >

                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/20 to-transparent"></div>

                        <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                            <div class="text-xs font-bold uppercase tracking-[0.16em] text-amber-400">
                                Production to Delivery
                            </div>

                            <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">
                                Production, quality control and delivery.
                            </h2>
                        </div>
                    </div>

                    {{-- Previous --}}
                    <button
                        type="button"
                        data-carousel-prev
                        aria-label="Previous slide"
                        class="absolute left-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-slate-950/60 text-xl text-white opacity-0 backdrop-blur-sm transition hover:bg-slate-950/90 focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-amber-400 group-hover:opacity-100"
                    >
                        <span aria-hidden="true">‹</span>
                    </button>

                    {{-- Next --}}
                    <button
                        type="button"
                        data-carousel-next
                        aria-label="Next slide"
                        class="absolute right-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-slate-950/60 text-xl text-white opacity-0 backdrop-blur-sm transition hover:bg-slate-950/90 focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-amber-400 group-hover:opacity-100"
                    >
                        <span aria-hidden="true">›</span>
                    </button>

                    {{-- Indicators --}}
                    <div
                        class="absolute bottom-5 right-5 z-10 flex items-center gap-2"
                        aria-label="Carousel navigation"
                    >
                        @foreach(range(0, 4) as $index)
                            <button
                                type="button"
                                data-carousel-dot="{{ $index }}"
                                aria-label="Go to slide {{ $index + 1 }}"
                                aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                class="{{ $index === 0 ? 'w-7 bg-amber-400' : 'w-2.5 bg-white/60' }} h-2.5 rounded-full transition-all duration-300 hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-950"
                            ></button>
                        @endforeach
                    </div>
                </div>

                {{-- Screen-reader status --}}
                <p
                    class="sr-only"
                    data-carousel-status
                    aria-live="polite"
                >
                    Slide 1 of 5
                </p>
            </div>

        </div>
    </div>
</section>


{{-- ================================================================
     INTRODUCTION
     ================================================================ --}}
<section class="bg-white py-20 sm:py-24">
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">

        <div class="grid items-center gap-12 lg:grid-cols-[0.8fr_1.2fr]">

            {{-- OY-HOME-02 --}}
            <div
                class="aspect-[4/3] overflow-hidden rounded-3xl border border-slate-200 bg-slate-100"
                data-image-slot="OY-HOME-02"
            >
                <img
                    src="{{ asset('images/oneyard/home/oy-home-02.webp') }}"
                    alt="Oneyard Outfitters uniforms and clothing"
                    class="h-full w-full object-cover"
                    loading="lazy"
                    decoding="async"
                >
            </div>

            <div>
                <div class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">
                    What We Do
                </div>

                <h2 class="mt-3 max-w-2xl text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                    We turn organizational requirements into completed supply.
                </h2>

                <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600">
                    A requirement can involve uniforms, clothing, school
                    supplies, procurement or custom production. Our approach
                    begins by understanding the requirement and creating a
                    clear path toward delivery.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="text-sm font-bold text-slate-950">
                            Uniforms & Clothing
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Shirts, trousers, uniforms and other clothing
                            requirements.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="text-sm font-bold text-slate-950">
                            Institutional Supplies
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Products and supplies needed by schools and
                            organizations.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="text-sm font-bold text-slate-950">
                            Procurement
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Sourcing and supplier coordination around defined
                            requirements.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="text-sm font-bold text-slate-950">
                            Production
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Approved specifications can move into organized
                            production.
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>


{{-- ================================================================
     SUPPLY AREAS
     ================================================================ --}}
<section class="bg-slate-100 py-20 sm:py-24">
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">

        <div class="max-w-2xl">
            <div class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">
                Supply Areas
            </div>

            <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                One brand. Multiple supply needs.
            </h2>

            <p class="mt-4 text-base leading-7 text-slate-600">
                We are building Oneyard as a broader institutional supply
                brand rather than limiting the business to one product type.
            </p>
        </div>

        <div class="mt-12 grid gap-6 lg:grid-cols-3">

            {{-- OY-HOME-03 --}}
            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div
                    class="aspect-[16/10] bg-slate-100"
                    data-image-slot="OY-HOME-03"
                >
                    <img
                        src="{{ asset('images/oneyard/home/oy-home-03.webp') }}"
                        alt="Oneyard Outfitters institutional supplies"
                        class="h-full w-full object-cover"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-950">
                        Institutional Supplies
                    </h3>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        School and organizational items that support everyday
                        institutional needs.
                    </p>
                </div>
            </article>

            {{-- OY-HOME-04 --}}
            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div
                    class="aspect-[16/10] bg-slate-100"
                    data-image-slot="OY-HOME-04"
                >
                    <img
                        src="{{ asset('images/oneyard/home/oy-home-04.webp') }}"
                        alt="Oneyard Outfitters procurement and sourcing"
                        class="h-full w-full object-cover"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-950">
                        Procurement
                    </h3>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Requirements can be sourced and coordinated through
                        structured procurement.
                    </p>
                </div>
            </article>

            {{-- OY-HOME-05 --}}
            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div
                    class="aspect-[16/10] bg-slate-100"
                    data-image-slot="OY-HOME-05"
                >
                    <img
                        src="{{ asset('images/oneyard/home/oy-home-05.webp') }}"
                        alt="Oneyard Outfitters production, quality control and delivery"
                        class="h-full w-full object-cover"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-950">
                        Production & Delivery
                    </h3>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Approved requirements move through production,
                        quality control and delivery.
                    </p>
                </div>
            </article>

        </div>
    </div>
</section>


{{-- ================================================================
     WHO WE SERVE
     ================================================================ --}}
<section class="bg-white py-20 sm:py-24">
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">

        <div class="grid gap-12 lg:grid-cols-2">

            <div>
                <div class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">
                    Who We Serve
                </div>

                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                    Designed around organizations with real supply requirements.
                </h2>

                <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">
                    Oneyard can support organizations that need uniforms,
                    clothing, institutional supplies or coordinated
                    procurement.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">

                @foreach([
                    ['Schools', 'Primary, secondary, private and government school requirements.'],
                    ['Businesses', 'Uniforms and clothing requirements for teams and organizations.'],
                    ['Institutions', 'Organized supply and procurement around defined needs.'],
                    ['Organizations', 'A structured process for moving from requirement to completed supply.'],
                ] as [$title, $text])

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                        <h3 class="font-bold text-slate-950">
                            {{ $title }}
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ $text }}
                        </p>
                    </div>

                @endforeach

            </div>

        </div>
    </div>
</section>


{{-- ================================================================
     PROCESS PREVIEW
     ================================================================ --}}
<section class="bg-slate-950 py-20 sm:py-24">
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">

        <div class="grid items-center gap-12 lg:grid-cols-2">

            <div>
                <div class="text-xs font-bold uppercase tracking-[0.16em] text-amber-400">
                    Our Approach
                </div>

                <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">
                    Clear requirements. Clear stages. Better coordination.
                </h2>

                <p class="mt-5 max-w-xl text-base leading-7 text-slate-300">
                    We separate the important stages of an organizational
                    supply process so requirements can be understood,
                    specified, produced, checked and delivered properly.
                </p>

                <a
                    href="{{ url('/how-it-works') }}"
                    class="mt-8 inline-flex items-center rounded-xl bg-white px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-slate-950"
                >
                    Explore the Process
                </a>
            </div>

            <div class="space-y-3">
                @foreach([
                    ['01', 'Understand the requirement'],
                    ['02', 'Define the specification'],
                    ['03', 'Quote and approve'],
                    ['04', 'Procure and produce'],
                    ['05', 'Quality control'],
                    ['06', 'Deliver and complete'],
                ] as [$number, $label])

                    <div class="flex items-center gap-4 rounded-2xl border border-slate-800 bg-slate-900 px-5 py-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-xs font-black text-slate-950">
                            {{ $number }}
                        </span>

                        <span class="text-sm font-semibold text-white">
                            {{ $label }}
                        </span>
                    </div>

                @endforeach
            </div>

        </div>
    </div>
</section>


{{-- ================================================================
     CLOSING
     ================================================================ --}}
<section class="bg-white py-20 sm:py-24">
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">

        <div class="grid items-center gap-10 overflow-hidden rounded-3xl bg-slate-100 p-6 sm:p-8 lg:grid-cols-[0.8fr_1.2fr] lg:p-10">

            {{-- OY-HOME-06 --}}
            <div
                class="aspect-[4/3] overflow-hidden rounded-2xl bg-slate-200"
                data-image-slot="OY-HOME-06"
            >
                <img
                    src="{{ asset('images/oneyard/home/oy-home-06.webp') }}"
                    alt="Oneyard Outfitters brand"
                    class="h-full w-full object-cover"
                    loading="lazy"
                    decoding="async"
                >
            </div>

            <div>
                <div class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">
                    Start With The Requirement
                </div>

                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                    Tell us what your organization needs.
                </h2>

                <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">
                    Whether you need uniforms, clothing, institutional
                    supplies or procurement support, the first step is a
                    conversation about the requirement.
                </p>

                <a
                    href="{{ url('/contact') }}"
                    class="mt-8 inline-flex items-center rounded-xl bg-slate-950 px-6 py-3.5 text-sm font-bold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                >
                    Contact Oneyard
                </a>
            </div>

        </div>

    </div>
</section>


@endsection