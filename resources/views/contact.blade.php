@extends('layouts.public')

@section('title', 'Contact Oneyard Outfitters')

@section('description', 'Contact Oneyard Outfitters about fabrics, clothing, uniforms, institutional supplies, procurement, sourcing, and production requirements.')

@section('content')

{{-- ============================================================
     HERO
     ============================================================ --}}
<section class="relative overflow-hidden bg-slate-950 text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(245,158,11,0.16),_transparent_38%)]"></div>

    <div class="relative mx-auto max-w-7xl px-6 py-20 sm:px-8 lg:px-10 lg:py-24">

        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-400">
                Contact Oneyard Outfitters
            </p>

            <h1 class="mt-5 text-4xl font-semibold tracking-tight sm:text-5xl">
                Tell us what you need.
            </h1>

            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                Give us the basic details of your requirement and continue the
                conversation with Oneyard Outfitters on WhatsApp.
            </p>
        </div>

    </div>
</section>


{{-- ============================================================
     CONTACT CONTENT
     ============================================================ --}}
<section class="bg-white">
    <div class="mx-auto grid max-w-7xl gap-12 px-6 py-20 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:px-10 lg:py-24">

        {{-- CONTACT INFORMATION --}}
        <div>

            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
                Start a conversation
            </p>

            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                Let's understand the requirement.
            </h2>

            <p class="mt-6 text-base leading-8 text-slate-600">
                Whether you need fabrics, uniforms, clothing, institutional
                supplies, procurement, or sourcing support, start by telling
                us what you are trying to get done.
            </p>

            <div class="mt-10 rounded-3xl bg-slate-950 p-7 text-white">

                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-400">
                    WhatsApp
                </p>

                <h3 class="mt-3 text-2xl font-semibold">
                    +234 903 621 9812
                </h3>

                <p class="mt-3 text-sm leading-7 text-slate-300">
                    Send your requirement directly and continue the conversation
                    with our team.
                </p>

                <a
                    href="https://wa.me/2349036518913"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-6 inline-flex items-center justify-center rounded-full bg-amber-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-950"
                >
                    Chat on WhatsApp
                </a>

            </div>

        </div>


        {{-- REQUIREMENT FORM --}}
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6 sm:p-8">

            <div class="mb-8">
                <h2 class="text-2xl font-semibold tracking-tight text-slate-950">
                    Tell us about your requirement
                </h2>

                <p class="mt-2 text-sm leading-7 text-slate-600">
                    You do not need to provide every detail. Give us enough
                    information to understand what you need.
                </p>
            </div>

            <form
                id="contact-whatsapp-form"
                class="space-y-6"
                data-whatsapp-form
            >

                <div class="grid gap-6 sm:grid-cols-2">

                    <div>
                        <label
                            for="contact-name"
                            class="block text-sm font-medium text-slate-900"
                        >
                            Your name
                        </label>

                        <input
                            id="contact-name"
                            name="name"
                            type="text"
                            autocomplete="name"
                            required
                            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-200"
                            placeholder="Your name"
                        >
                    </div>

                    <div>
                        <label
                            for="contact-organization"
                            class="block text-sm font-medium text-slate-900"
                        >
                            Organization
                        </label>

                        <input
                            id="contact-organization"
                            name="organization"
                            type="text"
                            autocomplete="organization"
                            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-200"
                            placeholder="School, business, organization..."
                        >
                    </div>

                </div>


                <div class="grid gap-6 sm:grid-cols-2">

                    <div>
                        <label
                            for="contact-phone"
                            class="block text-sm font-medium text-slate-900"
                        >
                            Phone number
                        </label>

                        <input
                            id="contact-phone"
                            name="phone"
                            type="tel"
                            autocomplete="tel"
                            required
                            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-200"
                            placeholder="080..."
                        >
                    </div>

                    <div>
                        <label
                            for="contact-category"
                            class="block text-sm font-medium text-slate-900"
                        >
                            What do you need?
                        </label>

                        <select
                            id="contact-category"
                            name="category"
                            required
                            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200"
                        >
                            <option value="">Select a category</option>
                            <option value="Fabrics / Materials">Fabrics / Materials</option>
                            <option value="Clothing">Clothing</option>
                            <option value="Uniforms">Uniforms</option>
                            <option value="Institutional Supplies">Institutional Supplies</option>
                            <option value="Procurement / Sourcing">Procurement / Sourcing</option>
                            <option value="Other Requirement">Other Requirement</option>
                        </select>
                    </div>

                </div>


                <div>
                    <label
                        for="contact-message"
                        class="block text-sm font-medium text-slate-900"
                    >
                        Tell us about the requirement
                    </label>

                    <textarea
                        id="contact-message"
                        name="message"
                        rows="7"
                        required
                        class="mt-2 block w-full resize-y rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-200"
                        placeholder="Tell us what you need, quantities if known, timing, sizes, references, or any other useful details..."
                    ></textarea>
                </div>


                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm leading-6 text-slate-700">
                        When you continue, WhatsApp will open with your
                        information already prepared as a message. You can
                        review it before sending.
                    </p>
                </div>


                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-slate-950 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-950 focus:ring-offset-2 sm:w-auto"
                >
                    Continue on WhatsApp
                </button>

            </form>

        </div>

    </div>
</section>


{{-- ============================================================
     BOTTOM CTA
     ============================================================ --}}
<section class="bg-slate-50">
    <div class="mx-auto max-w-4xl px-6 py-20 text-center sm:px-8 lg:py-24">

        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
            Prefer a direct message?
        </p>

        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
            Chat with Oneyard Outfitters directly.
        </h2>

        <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-slate-600">
            You can also open WhatsApp without filling out the form.
        </p>

        <a
            href="https://wa.me/2349036518913"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-8 inline-flex items-center justify-center rounded-full bg-amber-400 px-7 py-3.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2"
        >
            Open WhatsApp
        </a>

    </div>
</section>

@endsection
