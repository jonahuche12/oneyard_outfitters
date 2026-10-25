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
                From first request to delivery, one clear process you can follow.
            </h1>

            <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300">
                Tell us what your organization needs. We define the specification, agree the quotation, then source or produce, inspect and deliver, so you always know what was agreed and what happens next.
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
                    Every good order starts with getting the requirement right.
                </h2>

                <div class="mt-6 space-y-5 text-base leading-8 text-slate-600">
                    <p>
                        Every organization is different. A school may need uniforms and supplies; a business may need workwear, fabrics or sourced items.
                    </p>

                    <p>
                        We do not work from assumptions. We confirm what is actually needed and organize the details before anything is made or bought.
                    </p>

                    <p>
                        From there, sourcing, production, quality checks and delivery are coordinated around one agreed requirement.
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
                A clear path from first request to delivery.
            </h2>

            <p class="mt-5 text-base leading-8 text-slate-600">
                Every order is different, but the path is the same: understand, specify, produce, inspect, deliver.
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
                        We confirm what you need.
                    </h3>

                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                        Share what you have: the products, quantities, timing, reference materials or existing designs. A rough list is enough to start.
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
                        We define exactly what will be made.
                    </h3>

                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                        Materials, designs, measurements, quantities and branding are defined and agreed, with a quotation for your approval, before any production begins.
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
                        We source it or produce it, to the agreed specification.
                    </h3>

                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                        Depending on the order, we source materials or products, coordinate procurement, or produce clothing and uniforms exactly to what was approved.
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
                        Every order is inspected before it ships.
                    </h3>

                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                        Before delivery, completed work is checked against the agreed requirement, and any corrections are addressed before it reaches you.
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
                        Your order is delivered.
                    </h3>

                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                        Once the work is complete and checked, your order is delivered to your organization.
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
                Nothing gets lost between the request and the delivery.
            </h2>

            <p class="mt-5 text-base leading-8 text-slate-600">
                Uniform and supply orders have many moving parts. Keeping them in one process means you can always see what was requested, what is being made and what gets checked before delivery.
            </p>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-3">

            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-7">
                <h3 class="text-xl font-semibold text-slate-950">
                    Agreed up front
                </h3>

                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Details are settled before execution, so everyone works from the same reference point.
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-7">
                <h3 class="text-xl font-semibold text-slate-950">
                    One team, one plan
                </h3>

                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Sourcing, production and logistics are organized around your defined requirement.
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-7">
                <h3 class="text-xl font-semibold text-slate-950">
                    Checked before it ships
                </h3>

                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Completed work is inspected against the agreement before it moves to delivery.
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
                    Tell us what you need. Get a clear quote.
                </h2>

                <p class="mt-5 text-base leading-8 text-slate-300">
                    Send your requirement, the products involved and whatever information you already have. We will respond with a clear plan and a quotation to review.
                </p>

            </div>

            <a
                href="{{ url('/contact') }}"
                class="inline-flex items-center justify-center rounded-full bg-amber-400 px-7 py-3.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-950"
            >
                Request a Quote
            </a>

        </div>
    </div>
</section>

@endsection
