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
                    Outfit your entire organization
                    <span class="text-amber-400">with one accountable supplier.</span>
                </h1>

                <p class="mt-7 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg sm:leading-8">
                    Oneyard Outfitters manages uniforms, clothing and institutional supplies from specification and approved quotation through production, quality inspection and delivery, so you deal with one team instead of a chain of vendors.
                </p>

                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a
                        href="{{ url('/contact') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-6 py-3.5 text-sm font-bold text-slate-950 transition hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-950"
                    >
                        Request a Quote
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
                                One Accountable Supplier
                            </div>

                            <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">
                                Uniforms, clothing and supplies, managed end to end by one team.
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
                                Uniforms Made to Specification
                            </div>

                            <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">
                                Defined garment specifications, organized sizing, consistent production.
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
                                Supplies Beyond Uniforms
                            </div>

                            <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">
                                The everyday items schools and organizations depend on, sourced and supplied.
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
                                Structured Procurement
                            </div>

                            <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">
                                A defined requirement, a clear quotation, a managed supply chain.
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
                                Inspected Before Delivery
                            </div>

                            <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">
                                Every order moves through production, quality control and confirmed delivery.
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
                    Our Service
                </div>

                <h2 class="mt-3 max-w-2xl text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                    From requirement to completed supply, handled by one team.
                </h2>

                <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600">
                    Bulk orders go wrong when responsibility is scattered across tailors, suppliers and endless follow-ups. Oneyard brings it together: we define the requirement, price it, produce it, check it and deliver it, with a clear record at every step.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="text-sm font-bold text-slate-950">
                            Uniforms & Clothing
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Shirts, trousers, pinafores and complete uniform sets, produced to an agreed specification.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="text-sm font-bold text-slate-950">
                            Institutional Supplies
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Everyday school and organizational supplies, sourced and delivered alongside your uniforms.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="text-sm font-bold text-slate-950">
                            Procurement
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Sourcing and supplier management for specific requirements, so you never chase a vendor.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="text-sm font-bold text-slate-950">
                            Production
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Once you approve the specification and quotation, production runs to exactly what was agreed.
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
                One relationship. Every supply need.
            </h2>

            <p class="mt-4 text-base leading-7 text-slate-600">
                Consolidate uniforms, supplies, sourcing and production under a single supplier with a single point of accountability.
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
                        School and organizational essentials, supplied to a defined list and quantity.
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
                        Hand us the list. We source and coordinate the suppliers, and you receive one clear quotation.
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
                        Approved orders move through production, quality inspection and delivery, with every stage managed.
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
                    Built for the people accountable for getting it right.
                </h2>

                <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">
                    Whether you are a school administrator, HR manager or procurement officer, Oneyard takes the follow-ups, the sizing confusion and the vendor coordination off your workload.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">

                @foreach([
                    ['Schools', 'Uniforms and supplies for primary, secondary, private and government schools, with organized sizing for large student populations.'],
                    ['Businesses', 'Workwear and uniforms for staff and teams, consistent across the whole workforce.'],
                    ['Institutions', 'Structured supply and procurement for institutions with recurring needs.'],
                    ['Organizations', 'A documented process from first request to final delivery, built for accountability.'],
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
                    How It Works
                </div>

                <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">
                    Six defined steps from request to delivery.
                </h2>

                <p class="mt-5 max-w-xl text-base leading-7 text-slate-300">
                    Every order follows the same documented path, so you always know what was agreed, what is in production and what happens next.
                </p>

                <a
                    href="{{ url('/how-it-works') }}"
                    class="mt-8 inline-flex items-center rounded-xl bg-white px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-slate-950"
                >
                    See the Full Process
                </a>
            </div>

            <div class="space-y-3">
                @foreach([
                    ['01', 'Share your requirement'],
                    ['02', 'We define the specification'],
                    ['03', 'Review and approve your quote'],
                    ['04', 'We source and produce'],
                    ['05', 'Quality inspection'],
                    ['06', 'Delivery and confirmation'],
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
    TESTIMONIALS
================================================================ --}}
@php
    // REPLACE every placeholder below with real client details and real words.
    $testimonials = [
        [
            'name'  => 'Mrs. Amaka Nwosu',
            'role'  => 'Proprietress, ***eld Academy, Port Harcourt, Rivers State',
            'photo' => null, // later: 'images/testimonials/client-1.jpg'
            'quote' => 'Preparing for a new school term requires proper planning, especially when it comes to uniforms and essential supplies. Oneyard Outfitters offers a practical approach to coordinating these needs, helping schools plan their requirements more effectively. I would recommend considering them as a school supply partner.',
        ],
        [
            'name'  => 'Mr. Tunde Adebayo',
            'role'  => 'Principal, ***ights School, Lagos State',
            'photo' => null, // later: 'images/testimonials/client-2.jpg'
            'quote' => 'A school\'s uniform contributes to its identity and the way students present themselves. Having a supplier that understands uniform specifications and the importance of organised delivery can make procurement easier. Oneyard Outfitters is worth considering for schools looking for uniforms and other school essentials.',
        ],
        [
            'name'  => 'Mrs. Chinyere Okeke',
            'role'  => 'Bursar and Head of Administration, ***eld International School, Enugu State',
            'photo' => null, // later: 'images/testimonials/client-3.jpg'
            'quote' => 'Good procurement begins with clear specifications, careful budgeting and reliable coordination. Oneyard Outfitters\' focus on uniforms and wider school essentials makes it a brand schools can explore when looking for a more organised way to manage their supply needs.',
        ],
    ];
@endphp

<style>
    .oy-testi { background: var(--oy-surface-muted, #f8fafc); border-top: 1px solid var(--oy-border, #e2e8f0); border-bottom: 1px solid var(--oy-border, #e2e8f0); }
    .oy-testi__wrap { max-width: 56rem; margin: 0 auto; padding: 4rem 1.25rem; }
    .oy-testi__eyebrow { font-size: .75rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--oy-accent, #b7791f); text-align: center; }
    .oy-testi__title { margin: .6rem 0 0; font-size: clamp(1.6rem, 3.2vw, 2.25rem); line-height: 1.2; font-weight: 700; color: var(--oy-primary, #102a43); text-align: center; }
    .oy-testi__viewport { position: relative; margin-top: 2.25rem; overflow: hidden; border-radius: var(--oy-radius-lg, 1rem); }
    .oy-testi__track { display: flex; transition: transform .6s cubic-bezier(.22,.61,.36,1); will-change: transform; }
    .oy-testi__slide { flex: 0 0 100%; min-width: 100%; box-sizing: border-box; padding: .25rem; }
    .oy-testi__card { background: var(--oy-surface, #fff); border: 1px solid var(--oy-border, #e2e8f0); border-radius: var(--oy-radius-lg, 1rem); box-shadow: var(--oy-shadow-md, 0 5px 16px rgb(15 23 42 / .07)); padding: 2rem 1.5rem; text-align: center; }
    .oy-testi__photo { position: relative; width: 4.75rem; height: 4.75rem; margin: 0 auto; }
    .oy-testi__photo img, .oy-testi__initial { width: 100%; height: 100%; border-radius: 9999px; object-fit: cover; border: 3px solid var(--oy-surface, #fff); box-shadow: 0 0 0 2px var(--oy-accent, #b7791f); }
    .oy-testi__initial { display: none; align-items: center; justify-content: center; background: var(--oy-primary-soft, #eaf1f7); color: var(--oy-primary, #102a43); font-weight: 700; font-size: 1.4rem; }
    .oy-testi__mark { margin-top: 1rem; font-size: 2.5rem; line-height: 1; color: var(--oy-accent, #b7791f); font-family: Georgia, serif; }
    .oy-testi__quote { margin: .25rem auto 0; max-width: 40rem; font-size: 1.05rem; line-height: 1.7; color: var(--oy-text, #0f172a); }
    .oy-testi__name { margin-top: 1.25rem; font-weight: 700; color: var(--oy-primary, #102a43); }
    .oy-testi__role { margin-top: .15rem; font-size: .875rem; color: var(--oy-text-muted, #64748b); }
    .oy-testi__controls { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 1.25rem; }
    .oy-testi__btn { width: 2.5rem; height: 2.5rem; border-radius: 9999px; border: 1px solid var(--oy-border-strong, #cbd5e1); background: var(--oy-surface, #fff); color: var(--oy-primary, #102a43); cursor: pointer; font-size: 1.1rem; line-height: 1; }
    .oy-testi__btn:hover { background: var(--oy-primary-soft, #eaf1f7); }
    .oy-testi__btn:focus-visible, .oy-testi__dot:focus-visible { outline: none; box-shadow: var(--oy-focus-ring-strong, 0 0 0 3px rgb(16 42 67 / .24)); }
    .oy-testi__dots { display: flex; gap: .5rem; }
    .oy-testi__dot { width: .6rem; height: .6rem; border-radius: 9999px; border: 0; padding: 0; background: var(--oy-border-strong, #cbd5e1); cursor: pointer; transition: background .2s, transform .2s; }
    .oy-testi__dot[aria-current="true"] { background: var(--oy-accent, #b7791f); transform: scale(1.25); }
    @media (min-width: 640px) { .oy-testi__card { padding: 2.5rem 3rem; } }
    @media (prefers-reduced-motion: reduce) { .oy-testi__track { transition: none; } }
</style>

<section class="oy-testi" data-testimonials aria-roledescription="carousel" aria-label="Client testimonials">
    <div class="oy-testi__wrap">
        <p class="oy-testi__eyebrow">In their words</p>
        <h2 class="oy-testi__title">Organizations that needed it done right</h2>

        <div class="oy-testi__viewport">
            <div class="oy-testi__track" data-testi-track>
                @foreach ($testimonials as $idx => $t)
                    @php
                        $initial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', preg_replace('/^(Mrs?|Ms|Dr|Prof|Chief|Engr)\.?\s+/i', '', $t['name'])) ?: 'O', 0, 1));
                    @endphp
                    <figure class="oy-testi__slide" role="group" aria-roledescription="slide" aria-label="{{ $idx + 1 }} of {{ count($testimonials) }}" data-testi-slide>
                        <div class="oy-testi__card">
                            <div class="oy-testi__photo">
                                @if (!empty($t['photo']))
                                    <img src="{{ asset($t['photo']) }}" alt="{{ $t['name'] }}" loading="lazy"
                                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <div class="oy-testi__initial" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="38" height="38" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg></div>
                                @else
                                    <div class="oy-testi__initial" style="display:flex" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="38" height="38" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg></div>
                                @endif
                            </div>
                            <div class="oy-testi__mark" aria-hidden="true">&ldquo;</div>
                            <blockquote class="oy-testi__quote">{{ $t['quote'] }}</blockquote>
                            <figcaption>
                                <div class="oy-testi__name">{{ $t['name'] }}</div>
                                <div class="oy-testi__role">{{ $t['role'] }}</div>
                            </figcaption>
                        </div>
                    </figure>
                @endforeach
            </div>
        </div>

        <div class="oy-testi__controls">
            <button type="button" class="oy-testi__btn" data-testi-prev aria-label="Previous testimonial">&larr;</button>
            <div class="oy-testi__dots" data-testi-dots>
                @foreach ($testimonials as $idx => $t)
                    <button type="button" class="oy-testi__dot" data-testi-dot="{{ $idx }}" aria-label="Show testimonial {{ $idx + 1 }}" @if ($idx === 0) aria-current="true" @endif></button>
                @endforeach
            </div>
            <button type="button" class="oy-testi__btn" data-testi-next aria-label="Next testimonial">&rarr;</button>
        </div>
    </div>
</section>

<script>
(function () {
    var root = document.querySelector('[data-testimonials]');
    if (!root) return;
    var track = root.querySelector('[data-testi-track]');
    var slides = [].slice.call(track.querySelectorAll('[data-testi-slide]'));
    var dots = [].slice.call(root.querySelectorAll('[data-testi-dot]'));
    var n = slides.length;
    if (n < 2) return;

    // Clone the first slide at the end so the loop runs forward with no rewind.
    var clone = slides[0].cloneNode(true);
    clone.setAttribute('aria-hidden', 'true');
    clone.removeAttribute('data-testi-slide');
    track.appendChild(clone);

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var i = 0, busy = false, timer = null, DELAY = 6500, SLIDE_MS = reduce ? 0 : 650;

    function paint() {
        var active = i % n;
        dots.forEach(function (d, k) {
            if (k === active) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current');
        });
    }
    function set(x, animate) {
        track.style.transition = animate ? '' : 'none';
        track.style.transform = 'translateX(' + (-x * 100) + '%)';
        i = x; paint();
    }
    function next() {
        if (busy) return;
        busy = true;
        set(i + 1, true);
        setTimeout(function () {
            if (i === n) { set(0, false); void track.offsetWidth; }
            busy = false;
        }, SLIDE_MS + 30);
    }
    function prev() {
        if (busy) return;
        busy = true;
        if (i === 0) { set(n, false); void track.offsetWidth; }
        set(i - 1, true);
        setTimeout(function () { busy = false; }, SLIDE_MS + 30);
    }
    function goTo(k) {
        if (busy || k === i % n) return;
        busy = true;
        set(k, true);
        setTimeout(function () { busy = false; }, SLIDE_MS + 30);
    }
    function start() { if (reduce || timer) return; timer = setInterval(next, DELAY); }
    function stop() { clearInterval(timer); timer = null; }

    root.querySelector('[data-testi-next]').addEventListener('click', function () { stop(); next(); start(); });
    root.querySelector('[data-testi-prev]').addEventListener('click', function () { stop(); prev(); start(); });
    dots.forEach(function (d) {
        d.addEventListener('click', function () { stop(); goTo(parseInt(d.getAttribute('data-testi-dot'), 10)); start(); });
    });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });

    var x0 = null;
    track.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; stop(); }, { passive: true });
    track.addEventListener('touchend', function (e) {
        if (x0 === null) return;
        var dx = e.changedTouches[0].clientX - x0;
        if (Math.abs(dx) > 40) { dx < 0 ? next() : prev(); }
        x0 = null; start();
    }, { passive: true });

    paint(); start();
})();
</script>


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
                    Ready When You Are
                </div>

                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                    Tell us what you need. Receive a clear quotation.
                </h2>

                <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">
                    Share your requirement, an approximate headcount, or simply the list you are working from. Our team will respond with a defined plan and a quotation you can review and approve.
                </p>

                <a
                    href="{{ url('/contact') }}"
                    class="mt-8 inline-flex items-center rounded-xl bg-slate-950 px-6 py-3.5 text-sm font-bold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                >
                    Request a Quote
                </a>
            </div>

        </div>

    </div>
</section>


@endsection