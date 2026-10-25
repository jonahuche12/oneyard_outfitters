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
                One accountable supplier for everything your organization wears and uses.
            </h1>

            <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300">
                Oneyard Outfitters brings fabrics, clothing, uniforms, institutional supplies, sourcing and production into one accountable process, so you deal with one team from requirement to delivery.
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
                One partner instead of a chain of vendors.
            </h2>

            <div class="mt-6 space-y-5 text-base leading-8 text-slate-600">
                <p>
                    Oneyard Outfitters exists because organizations should not have to manage every part of a uniform, clothing or supply order through scattered conversations with tailors, suppliers and couriers.
                </p>

                <p>
                    We bring it all together. We clarify what is needed, source or produce the right items, coordinate the work and deliver the completed order.
                </p>

                <p>
                    Our focus is execution: a clear requirement, organized details, and every stage managed until the order is delivered.
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
                Everything your order needs, under one roof.
            </h2>

            <p class="mt-5 text-base leading-8 text-slate-600">
                From fabric to finished garment to delivery, we cover the products and coordination needed to move a requirement from specification to your door.
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
                    Fabric and material sourcing for clothing, uniforms and other requirements, matched to your specification.
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
                    Clothing and uniform production built around the requirements of your organization, with consistent quality across every piece.
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
                    Practical supplies for schools, businesses and other organizations, supplied alongside your clothing order.
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
                    Coordinated sourcing when your requirement needs products, materials or production support, managed by us so you do not have to.
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
                    Built for the people accountable for the order.
                </h2>

                <p class="mt-6 text-base leading-8 text-slate-600">
                    If you are responsible for outfitting a school, business or institution, our service takes the coordination, follow-ups and vendor management off your desk.
                </p>

                <div class="mt-8 space-y-4">

                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-950">Schools</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Uniforms, clothing, supplies and other school requirements, organized for large student populations.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-950">Businesses</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Clothing, uniforms, fabrics and operational supplies, consistent across your whole team.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-950">Organizations & Institutions</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Coordinated sourcing and production for defined requirements and recurring needs.
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
                A clear path from request to delivery.
            </h2>

            <p class="mt-5 text-base leading-8 text-slate-300">
                Good execution starts with knowing exactly what is required. Every order moves through clear stages, so important details are never lost.
            </p>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-5">

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <span class="text-sm font-semibold text-amber-400">01</span>
                <h3 class="mt-4 font-semibold">Understand</h3>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                    We confirm exactly what your organization needs.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <span class="text-sm font-semibold text-amber-400">02</span>
                <h3 class="mt-4 font-semibold">Specify</h3>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                    Quantities, materials, designs and details are defined and agreed.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <span class="text-sm font-semibold text-amber-400">03</span>
                <h3 class="mt-4 font-semibold">Source / Produce</h3>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                    The materials or products are sourced or produced to what was agreed.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <span class="text-sm font-semibold text-amber-400">04</span>
                <h3 class="mt-4 font-semibold">Quality-check</h3>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                    Completed work is inspected against the agreed requirement.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <span class="text-sm font-semibold text-amber-400">05</span>
                <h3 class="mt-4 font-semibold">Deliver</h3>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                    The finished order is delivered to your organization.
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
                    Ready to get started?
                </p>

                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                    Tell us what you need. We will come back with a clear plan and quotation.
                </h2>

                <p class="mt-5 text-base leading-8 text-slate-600">
                    Whether it is fabrics, uniforms, clothing, institutional supplies or procurement, start with what you have, even a rough list or headcount, and we will take it from there.
                </p>
            </div>

            <a
                href="{{ url('/contact') }}"
                class="inline-flex items-center justify-center rounded-full bg-slate-950 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-950 focus:ring-offset-2"
            >
                Request a Quote
            </a>

        </div>

    </div>
</section>

@endsection
