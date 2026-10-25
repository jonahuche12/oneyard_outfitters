@extends('layouts.public')

@section('title', 'Frequently Asked Questions')

@section('description', 'Answers to common questions about Oneyard Outfitters, including fabrics, clothing, uniforms, institutional supplies, procurement, and the ordering process.')

@section('content')

{{-- ============================================================
     HERO
     ============================================================ --}}
<section class="relative overflow-hidden bg-slate-950 text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(245,158,11,0.16),_transparent_38%)]"></div>

    <div class="relative mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-24">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-400">
                Frequently Asked Questions
            </p>

            <h1 class="mt-5 text-4xl font-semibold tracking-tight sm:text-5xl">
                Straight answers before you request a quote.
            </h1>

            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                What we supply, who we work with, how an order works and how to get started.
            </p>
        </div>
    </div>
</section>


{{-- ============================================================
     FAQ
     ============================================================ --}}
<section class="bg-white">
    <div class="mx-auto max-w-4xl px-6 py-20 sm:px-8 lg:py-24">

        <div class="space-y-4">

            {{-- FAQ 01 --}}
            <article class="rounded-2xl border border-slate-200 bg-white">
                <h2>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-6 px-6 py-5 text-left"
                        aria-expanded="false"
                        aria-controls="faq-panel-1"
                        data-faq-trigger
                    >
                        <span class="text-base font-semibold text-slate-950">
                            What can Oneyard supply for my organization?
                        </span>

                        <span
                            class="shrink-0 text-2xl font-light text-slate-500"
                            aria-hidden="true"
                            data-faq-icon
                        >+</span>
                    </button>
                </h2>

                <div
                    id="faq-panel-1"
                    class="hidden border-t border-slate-100 px-6 pb-6 pt-5"
                    data-faq-panel
                >
                    <p class="text-sm leading-7 text-slate-600">
                        Uniforms, clothing, fabrics and institutional supplies, plus the sourcing, procurement and production behind them. You deal with one team instead of coordinating several vendors.
                    </p>
                </div>
            </article>


            {{-- FAQ 02 --}}
            <article class="rounded-2xl border border-slate-200 bg-white">
                <h2>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-6 px-6 py-5 text-left"
                        aria-expanded="false"
                        aria-controls="faq-panel-2"
                        data-faq-trigger
                    >
                        <span class="text-base font-semibold text-slate-950">
                            Who do you work with?
                        </span>

                        <span
                            class="shrink-0 text-2xl font-light text-slate-500"
                            aria-hidden="true"
                            data-faq-icon
                        >+</span>
                    </button>
                </h2>

                <div
                    id="faq-panel-2"
                    class="hidden border-t border-slate-100 px-6 pb-6 pt-5"
                    data-faq-panel
                >
                    <p class="text-sm leading-7 text-slate-600">
                        Schools, businesses, organizations and institutions that need uniforms, clothing, supplies or sourcing handled properly. If you are responsible for getting the order right, this service is built for you.
                    </p>
                </div>
            </article>


            {{-- FAQ 03 --}}
            <article class="rounded-2xl border border-slate-200 bg-white">
                <h2>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-6 px-6 py-5 text-left"
                        aria-expanded="false"
                        aria-controls="faq-panel-3"
                        data-faq-trigger
                    >
                        <span class="text-base font-semibold text-slate-950">
                            Can you make uniforms or clothing to our design and sizes?
                        </span>

                        <span
                            class="shrink-0 text-2xl font-light text-slate-500"
                            aria-hidden="true"
                            data-faq-icon
                        >+</span>
                    </button>
                </h2>

                <div
                    id="faq-panel-3"
                    class="hidden border-t border-slate-100 px-6 pb-6 pt-5"
                    data-faq-panel
                >
                    <p class="text-sm leading-7 text-slate-600">
                        Yes. Tell us the design, materials, quantities, sizing and branding you need. We turn that into a clear specification and a quotation for your approval before production starts.
                    </p>
                </div>
            </article>


            {{-- FAQ 04 --}}
            <article class="rounded-2xl border border-slate-200 bg-white">
                <h2>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-6 px-6 py-5 text-left"
                        aria-expanded="false"
                        aria-controls="faq-panel-4"
                        data-faq-trigger
                    >
                        <span class="text-base font-semibold text-slate-950">
                            Can you source items that are not on your list?
                        </span>

                        <span
                            class="shrink-0 text-2xl font-light text-slate-500"
                            aria-hidden="true"
                            data-faq-icon
                        >+</span>
                    </button>
                </h2>

                <div
                    id="faq-panel-4"
                    class="hidden border-t border-slate-100 px-6 pb-6 pt-5"
                    data-faq-panel
                >
                    <p class="text-sm leading-7 text-slate-600">
                        Often, yes. Send us what you need and we will assess it, then come back with a clear answer and a quotation.
                    </p>
                </div>
            </article>


            {{-- FAQ 05 --}}
            <article class="rounded-2xl border border-slate-200 bg-white">
                <h2>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-6 px-6 py-5 text-left"
                        aria-expanded="false"
                        aria-controls="faq-panel-5"
                        data-faq-trigger
                    >
                        <span class="text-base font-semibold text-slate-950">
                            What happens after I request a quote?
                        </span>

                        <span
                            class="shrink-0 text-2xl font-light text-slate-500"
                            aria-hidden="true"
                            data-faq-icon
                        >+</span>
                    </button>
                </h2>

                <div
                    id="faq-panel-5"
                    class="hidden border-t border-slate-100 px-6 pb-6 pt-5"
                    data-faq-panel
                >
                    <p class="text-sm leading-7 text-slate-600">
                        We confirm your requirement and define the specification. You receive a quotation to review and approve. Once approved, we source or produce, inspect the work and deliver.
                    </p>
                </div>
            </article>


            {{-- FAQ 06 --}}
            <article class="rounded-2xl border border-slate-200 bg-white">
                <h2>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-6 px-6 py-5 text-left"
                        aria-expanded="false"
                        aria-controls="faq-panel-6"
                        data-faq-trigger
                    >
                        <span class="text-base font-semibold text-slate-950">
                            I do not have a final list yet. Can I still start?
                        </span>

                        <span
                            class="shrink-0 text-2xl font-light text-slate-500"
                            aria-hidden="true"
                            data-faq-icon
                        >+</span>
                    </button>
                </h2>

                <div
                    id="faq-panel-6"
                    class="hidden border-t border-slate-100 px-6 pb-6 pt-5"
                    data-faq-panel
                >
                    <p class="text-sm leading-7 text-slate-600">
                        Yes. Start with what you have, even a rough list, a headcount or a description of the problem. We will help you define the rest.
                    </p>
                </div>
            </article>


            {{-- FAQ 07 --}}
            <article class="rounded-2xl border border-slate-200 bg-white">
                <h2>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-6 px-6 py-5 text-left"
                        aria-expanded="false"
                        aria-controls="faq-panel-7"
                        data-faq-trigger
                    >
                        <span class="text-base font-semibold text-slate-950">
                            Can you match our branding and organization details?
                        </span>

                        <span
                            class="shrink-0 text-2xl font-light text-slate-500"
                            aria-hidden="true"
                            data-faq-icon
                        >+</span>
                    </button>
                </h2>

                <div
                    id="faq-panel-7"
                    class="hidden border-t border-slate-100 px-6 pb-6 pt-5"
                    data-faq-panel
                >
                    <p class="text-sm leading-7 text-slate-600">
                        Yes. Branding, design references, measurements, samples and other organization-specific details can all form part of the specification, so the finished order matches what you approved.
                    </p>
                </div>
            </article>


            {{-- FAQ 08 --}}
            <article class="rounded-2xl border border-slate-200 bg-white">
                <h2>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-6 px-6 py-5 text-left"
                        aria-expanded="false"
                        aria-controls="faq-panel-8"
                        data-faq-trigger
                    >
                        <span class="text-base font-semibold text-slate-950">
                            How do I get started?
                        </span>

                        <span
                            class="shrink-0 text-2xl font-light text-slate-500"
                            aria-hidden="true"
                            data-faq-icon
                        >+</span>
                    </button>
                </h2>

                <div
                    id="faq-panel-8"
                    class="hidden border-t border-slate-100 px-6 pb-6 pt-5"
                    data-faq-panel
                >
                    <p class="text-sm leading-7 text-slate-600">
                        Use the contact page to request a quote. Tell us the products you need, quantities if known, your timing and any reference materials you have.
                    </p>
                </div>
            </article>

        </div>
    </div>
</section>


{{-- ============================================================
     CTA
     ============================================================ --}}
<section class="bg-slate-50">
    <div class="mx-auto max-w-4xl px-6 py-20 text-center sm:px-8 lg:py-24">

        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
            Still have questions?
        </p>

        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
            Ready for a clear quote?
        </h2>

        <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-slate-600">
            Send us your requirement, even a rough list. We will respond with a clear plan and a quotation you can review and approve.
        </p>

        <a
            href="{{ url('/contact') }}"
            class="mt-8 inline-flex items-center justify-center rounded-full bg-slate-950 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-950 focus:ring-offset-2"
        >
            Request a Quote
        </a>

    </div>
</section>

@endsection
