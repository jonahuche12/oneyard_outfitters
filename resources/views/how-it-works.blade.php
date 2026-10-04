@extends('layouts.public')

@section('title', 'How It Works')

@section('description', 'See how Oneyard Outfitters takes organizational requirements from initial discussion through specification, sourcing or production, quality checking, and delivery.')

@section('content')

{{-- ============================================================
     HERO
     ============================================================ --}}
<section class="relative overflow-hidden bg-slate-950 text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(245,158,11,0.16),_transparent_38%)]"></div>

    <div class="relative mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-28">
        <div class="max-w-3xl">

            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-400">
                How It Works
            </p>

            <h1 class="mt-5 text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                From requirement to delivery, one clear process.
            </h1>

            <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300">
                We start by understanding what your organization needs, then
                organize the specification, sourcing or production, quality
                checking, and delivery around that requirement.
            </p>

        </div>
    </div>
</section>


{{-- ============================================================
     INTRODUCTION
     ============================================================ --}}
<section class="bg-white">
    <div class="mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-24">

        <div class="grid gap-12 lg:grid-cols-2 lg:items-center">

            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
                    Start with the need
                </p>

                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                    Good execution begins with understanding the requirement.
                </h2>

                <div class="mt-6 space-y-5 text-base leading-8 text-slate-600">
                    <p>
                        Every requirement is different. A school may need uniforms
                        and supplies, while a business may need clothing, fabrics,
                        or other sourced items.
                    </p>

                    <p>
                        Rather than beginning with assumptions, we first establish
                        what is actually needed and organize the important details.
                    </p>

                    <p>
                        From there, the appropriate sourcing, production, quality,
                        and delivery steps can be coordinated.
                    </p>
                </div>
            </div>

            <div class="overflow-hidden rounded-3xl bg-slate-100 shadow-sm">
                <img
                    src="{{ asset('images/oneyard/home/oy-home-04.webp') }}"
                    alt="Oneyard Outfitters procurement and sourcing"
                    class="h-full min-h-[320px] w-full object-cover"
                    loading="lazy"
                >
            </div>

        </div>
    </div>
</section>


{{-- ============================================================
     PROCESS
     ============================================================ --}}
<section class="bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-24">

        <div class="mx-auto max-w-3xl text-center">

            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
                The process
            </p>

            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                Five stages that keep the work organized.
            </h2>

            <p class="mt-5 text-base leading-8 text-slate-600">
                The exact work varies according to the requirement, but the overall
                approach remains simple: understand, define, execute, check, and deliver.
            </p>

        </div>


        <div class="mt-14 space-y-6">

            {{-- STEP 01 --}}
            <article class="grid gap-6 rounded-3xl border border-slate-200 bg-white p-7 shadow-sm md:grid-cols-[90px_1fr] md:p-9">

                <div>
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-950 text-xl font-bold text-amber-400">
                        01
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">
                        Understand
                    </p>

                    <h3 class="mt-2 text-2xl font-semibold text-slate-950">
                        We understand what you need.
                    </h3>

                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                        We begin with the requirement. This may include the type of
                        product, intended use, quantities, timing, reference materials,
                        existing designs, or other information relevant to the request.
                    </p>
                </div>

            </article>


            {{-- STEP 02 --}}
            <article class="grid gap-6 rounded-3xl border border-slate-200 bg-white p-7 shadow-sm md:grid-cols-[90px_1fr] md:p-9">

                <div>
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-950 text-xl font-bold text-amber-400">
                        02
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">
                        Specify
                    </p>

                    <h3 class="mt-2 text-2xl font-semibold text-slate-950">
                        We turn the requirement into clear specifications.
                    </h3>

                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                        Details such as materials, designs, measurements, quantities,
                        branding, product references, and other relevant requirements
                        are clarified before execution begins.
                    </p>
                </div>

            </article>


            {{-- STEP 03 --}}
            <article class="grid gap-6 rounded-3xl border border-slate-200 bg-white p-7 shadow-sm md:grid-cols-[90px_1fr] md:p-9">

                <div>
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-950 text-xl font-bold text-amber-400">
                        03
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">
                        Source / Produce
                    </p>

                    <h3 class="mt-2 text-2xl font-semibold text-slate-950">
                        We source the requirement or move it into production.
                    </h3>

                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                        Depending on the requirement, the work may involve sourcing
                        materials or products, coordinating procurement, or producing
                        clothing and uniforms according to the agreed specification.
                    </p>
                </div>

            </article>


            {{-- STEP 04 --}}
            <article class="grid gap-6 rounded-3xl border border-slate-200 bg-white p-7 shadow-sm md:grid-cols-[90px_1fr] md:p-9">

                <div>
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-950 text-xl font-bold text-amber-400">
                        04
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">
                        Quality Check
                    </p>

                    <h3 class="mt-2 text-2xl font-semibold text-slate-950">
                        Completed work is checked against the requirement.
                    </h3>

                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                        Before delivery, the completed work goes through the relevant
                        checking stage so that it can be assessed against the defined
                        requirement and identified corrections can be addressed.
                    </p>
                </div>

            </article>


            {{-- STEP 05 --}}
            <article class="grid gap-6 rounded-3xl border border-slate-200 bg-white p-7 shadow-sm md:grid-cols-[90px_1fr] md:p-9">

                <div>
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-950 text-xl font-bold text-amber-400">
                        05
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">
                        Deliver
                    </p>

                    <h3 class="mt-2 text-2xl font-semibold text-slate-950">
                        The completed requirement moves to delivery.
                    </h3>

                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                        Once the relevant work has been completed and checked, the
                        order proceeds to the delivery stage for the organization.
                    </p>
                </div>

            </article>

        </div>
    </div>
</section>


{{-- ============================================================
     WHAT MAKES THE PROCESS USEFUL
     ============================================================ --}}
<section class="bg-white">
    <div class="mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-24">

        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
                Why the process matters
            </p>

            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                The details stay connected from beginning to end.
            </h2>

            <p class="mt-5 text-base leading-8 text-slate-600">
                A supply requirement can involve several moving parts. Keeping those
                parts organized makes it easier to understand what was requested,
                what is being sourced or produced, and what needs to be checked before delivery.
            </p>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-3">

            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-7">
                <h3 class="text-xl font-semibold text-slate-950">
                    Clear requirements
                </h3>

                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Important details are established before execution so the work
                    has a clear reference point.
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-7">
                <h3 class="text-xl font-semibold text-slate-950">
                    Coordinated execution
                </h3>

                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Sourcing, production, and other relevant activities are organized
                    around the defined requirement.
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-7">
                <h3 class="text-xl font-semibold text-slate-950">
                    Quality before delivery
                </h3>

                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Completed work is checked before it moves through the final
                    delivery stage.
                </p>
            </article>

        </div>
    </div>
</section>


{{-- ============================================================
     CTA
     ============================================================ --}}
<section class="bg-slate-950 text-white">
    <div class="mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-24">

        <div class="grid gap-8 rounded-3xl border border-white/10 bg-white/5 p-8 sm:p-10 lg:grid-cols-[1fr_auto] lg:items-center lg:p-12">

            <div class="max-w-3xl">

                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-400">
                    Ready to begin?
                </p>

                <h2 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                    Start with what you need.
                </h2>

                <p class="mt-5 text-base leading-8 text-slate-300">
                    Tell us about the requirement, the products involved, and any
                    information you already have. We can take it from there.
                </p>

            </div>

            <a
                href="{{ url('/contact') }}"
                class="inline-flex items-center justify-center rounded-full bg-amber-400 px-7 py-3.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-950"
            >
                Start a Conversation
            </a>

        </div>
    </div>
</section>

@endsection
