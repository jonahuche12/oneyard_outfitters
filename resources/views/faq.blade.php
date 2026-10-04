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
                Questions about working with Oneyard Outfitters.
            </h1>

            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                Here are answers to some common questions about our products,
                sourcing, production, and organizational supply process.
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
                            What does Oneyard Outfitters provide?
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
                        Oneyard Outfitters works across fabrics, clothing, uniforms,
                        institutional supplies, procurement, sourcing, and related
                        production requirements.
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
                            Who can work with Oneyard Outfitters?
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
                        Our services are designed for schools, businesses,
                        organizations, and institutions with defined supply,
                        clothing, uniform, sourcing, or production requirements.
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
                            Can I request uniforms or clothing for an organization?
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
                        Yes. Uniforms and clothing can be discussed as an
                        organization-specific requirement. The details can include
                        the design, materials, quantities, sizing, branding,
                        and other relevant specifications.
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
                            Can Oneyard source products or materials that are not already listed?
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
                        Procurement and sourcing requirements can be discussed
                        based on the organization's specific need. The first step
                        is to provide enough information for the requirement to
                        be understood and assessed.
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
                            How does the process usually begin?
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
                        The process starts with understanding the requirement.
                        We then clarify the specification, determine the appropriate
                        sourcing or production approach, and proceed through the
                        relevant stages toward delivery.
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
                            Do I need to know exactly what I want before contacting you?
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
                        Not necessarily. The more information you can provide,
                        the easier it is to understand the requirement, but the
                        conversation can begin with the need you are trying to solve.
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
                            Can requirements include branding or organization-specific details?
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
                        Yes. Where relevant, organization-specific requirements
                        such as branding, design references, measurements, samples,
                        or other specifications can form part of the requirement.
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
                            How do I start a conversation?
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
                        Use the contact page to tell us what you need, including
                        the type of product or service, quantities where known,
                        timing, and any available reference materials.
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
            Tell us what you are trying to get done.
        </h2>

        <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-slate-600">
            Start with the requirement. We can work through the relevant details
            from there.
        </p>

        <a
            href="{{ url('/contact') }}"
            class="mt-8 inline-flex items-center justify-center rounded-full bg-slate-950 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-950 focus:ring-offset-2"
        >
            Contact Oneyard Outfitters
        </a>

    </div>
</section>

@endsection
