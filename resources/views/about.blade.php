@extends('layouts.public')

@section('title', 'About Oneyard Outfitters')

@section('description', 'Oneyard Outfitters provides fabrics, clothing, uniforms, institutional supplies, and procurement support for schools, businesses, and organizations.')

@section('content')

{{-- ============================================================
     HERO
     ============================================================ --}}
<section class="relative overflow-hidden bg-slate-950 text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(245,158,11,0.16),_transparent_38%)]"></div>

    <div class="relative mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-28">
        <div class="max-w-3xl">
            <p class="mb-5 text-sm font-semibold uppercase tracking-[0.24em] text-amber-400">
                About Oneyard Outfitters
            </p>

            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                We help organizations turn supply needs into finished, usable solutions.
            </h1>

            <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300">
                Oneyard Outfitters brings fabrics, clothing, uniforms, institutional supplies,
                sourcing, and production coordination into one organized process.
            </p>
        </div>
    </div>
</section>


{{-- ============================================================
     WHO WE ARE
     ============================================================ --}}
<section class="bg-white">
    <div class="mx-auto grid max-w-7xl gap-12 px-6 py-20 sm:px-8 lg:grid-cols-2 lg:items-center lg:px-10 lg:py-24">

        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
                Who we are
            </p>

            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                A practical supply and production partner.
            </h2>

            <div class="mt-6 space-y-5 text-base leading-8 text-slate-600">
                <p>
                    Oneyard Outfitters is built around a simple idea:
                    organizations should not have to coordinate every part of a supply,
                    clothing, or uniform requirement through disconnected conversations.
                </p>

                <p>
                    We help bring requirements together, clarify what is needed,
                    source or produce the right items, coordinate the work, and
                    deliver the completed order.
                </p>

                <p>
                    Our focus is practical execution — understanding the requirement,
                    organizing the details, and moving the work through the right stages.
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl bg-slate-100 shadow-sm">
            <img
                src="{{ asset('images/oneyard/home/oy-home-02.webp') }}"
                alt="Oneyard Outfitters clothing and uniform supply"
                class="h-full min-h-[320px] w-full object-cover"
                loading="lazy"
            >
        </div>

    </div>
</section>


{{-- ============================================================
     WHAT WE DO
     ============================================================ --}}
<section class="bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-24">

        <div class="max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
                What we do
            </p>

            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                From materials to finished requirements.
            </h2>

            <p class="mt-5 text-base leading-8 text-slate-600">
                Our work covers the products and coordination needed to move an
                institutional requirement from an idea or specification to delivery.
            </p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">

            <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <span class="text-lg font-bold">01</span>
                </div>

                <h3 class="mt-6 text-xl font-semibold text-slate-950">
                    Fabrics & Materials
                </h3>

                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Fabric and material sourcing for clothing, uniforms, and other
                    requirements.
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <span class="text-lg font-bold">02</span>
                </div>

                <h3 class="mt-6 text-xl font-semibold text-slate-950">
                    Clothing & Uniforms
                </h3>

                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Clothing and uniform production based on the requirements of
                    the organization being served.
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <span class="text-lg font-bold">03</span>
                </div>

                <h3 class="mt-6 text-xl font-semibold text-slate-950">
                    Institutional Supplies
                </h3>

                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Practical supplies that support schools, businesses, and
                    other organizations.
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <span class="text-lg font-bold">04</span>
                </div>

                <h3 class="mt-6 text-xl font-semibold text-slate-950">
                    Procurement & Sourcing
                </h3>

                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Coordinated sourcing for requirements that need products,
                    materials, or production support.
                </p>
            </article>

        </div>
    </div>
</section>


{{-- ============================================================
     WHO WE SERVE
     ============================================================ --}}
<section class="bg-white">
    <div class="mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-24">

        <div class="grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">

            <div class="overflow-hidden rounded-3xl bg-slate-100 shadow-sm">
                <img
                    src="{{ asset('images/oneyard/home/oy-home-03.webp') }}"
                    alt="Institutional supplies from Oneyard Outfitters"
                    class="h-full min-h-[340px] w-full object-cover"
                    loading="lazy"
                >
            </div>

            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
                    Who we serve
                </p>

                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                    Built around organizational requirements.
                </h2>

                <p class="mt-6 text-base leading-8 text-slate-600">
                    Our services are designed for organizations that need coordinated
                    sourcing, clothing, uniforms, fabrics, or institutional supplies.
                </p>

                <div class="mt-8 space-y-4">

                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-950">Schools</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Uniforms, clothing, supplies, and other school-related requirements.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-950">Businesses</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Clothing, uniforms, fabrics, and other operational supply needs.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-950">Organizations & Institutions</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Coordinated sourcing and production for defined organizational requirements.
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>


{{-- ============================================================
     HOW WE WORK
     ============================================================ --}}
<section class="bg-slate-950 text-white">
    <div class="mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-24">

        <div class="max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-400">
                How we work
            </p>

            <h2 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                A clear path from requirement to delivery.
            </h2>

            <p class="mt-5 text-base leading-8 text-slate-300">
                Good execution starts with understanding exactly what is required.
                We organize the work into clear stages so important details are not lost.
            </p>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-5">

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <span class="text-sm font-semibold text-amber-400">01</span>
                <h3 class="mt-4 font-semibold">Understand</h3>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                    We understand the organization's requirement.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <span class="text-sm font-semibold text-amber-400">02</span>
                <h3 class="mt-4 font-semibold">Specify</h3>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                    Requirements, quantities, materials, designs, and details are clarified.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <span class="text-sm font-semibold text-amber-400">03</span>
                <h3 class="mt-4 font-semibold">Source / Produce</h3>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                    The required materials or products are sourced or produced.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <span class="text-sm font-semibold text-amber-400">04</span>
                <h3 class="mt-4 font-semibold">Quality-check</h3>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                    Completed work is checked against the defined requirement.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <span class="text-sm font-semibold text-amber-400">05</span>
                <h3 class="mt-4 font-semibold">Deliver</h3>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                    The completed requirement moves to the organization for delivery.
                </p>
            </div>

        </div>
    </div>
</section>


{{-- ============================================================
     POSITIONING / CLOSING
     ============================================================ --}}
<section class="bg-white">
    <div class="mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-24">

        <div class="grid gap-10 rounded-3xl bg-amber-50 p-8 sm:p-10 lg:grid-cols-[1fr_auto] lg:items-center lg:p-12">

            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">
                    Let's work from the requirement
                </p>

                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                    Tell us what you need. We will help organize the next step.
                </h2>

                <p class="mt-5 text-base leading-8 text-slate-600">
                    Whether the requirement involves fabrics, uniforms, clothing,
                    institutional supplies, or procurement, start with the details
                    and let the work take shape from there.
                </p>
            </div>

            <a
                href="{{ url('/contact') }}"
                class="inline-flex items-center justify-center rounded-full bg-slate-950 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-950 focus:ring-offset-2"
            >
                Start a Conversation
            </a>

        </div>

    </div>
</section>

@endsection
