@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a
                    href="{{ route('organizations.index') }}"
                    class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
                >
                    ← Back to Organizations
                </a>

                <div class="mt-4 flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-lg font-semibold text-white">
                        {{ strtoupper(substr($organization->name, 0, 1)) }}
                    </div>

                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                            {{ $organization->name }}
                        </h1>

                        <p class="mt-1 font-mono text-sm text-slate-500">
                            {{ $organization->organization_code }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('create', App\Models\Assessment::class)
                    <a
                        href="{{ route('assessments.create', ['organization_id' => $organization->id]) }}"
                        class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
                    >
                        Record Assessment
                    </a>
                @endcan

                @can('update', $organization)
                    <a
                        href="{{ route('organizations.edit', $organization) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Edit Organization
                    </a>
                @endcan
            </div>
        </div>


        {{-- Feedback --}}
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif


        {{-- Identity and Status --}}
        <div class="grid gap-6 lg:grid-cols-2">

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                    Organization Identity
                </h2>

                <dl class="mt-5 divide-y divide-slate-100">

                    <div class="py-4 first:pt-0">
                        <dt class="text-xs text-slate-500">
                            Organization code
                        </dt>

                        <dd class="mt-1 font-mono text-sm font-medium text-slate-900">
                            {{ $organization->organization_code }}
                        </dd>
                    </div>

                    <div class="py-4">
                        <dt class="text-xs text-slate-500">
                            Organization name
                        </dt>

                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ $organization->name }}
                        </dd>
                    </div>

                    <div class="py-4">
                        <dt class="text-xs text-slate-500">
                            Type
                        </dt>

                        <dd class="mt-1 text-sm font-medium capitalize text-slate-900">
                            {{ str_replace('_', ' ', $organization->type) }}
                        </dd>
                    </div>

                    <div class="py-4 last:pb-0">
                        <dt class="text-xs text-slate-500">
                            Ownership
                        </dt>

                        <dd class="mt-1 text-sm font-medium capitalize text-slate-900">
                            {{ str_replace('_', ' ', $organization->ownership) }}
                        </dd>
                    </div>

                </dl>
            </section>


            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                    Contact Information
                </h2>

                <dl class="mt-5 divide-y divide-slate-100">

                    <div class="py-4 first:pt-0">
                        <dt class="text-xs text-slate-500">
                            Phone
                        </dt>

                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ $organization->phone ?: '—' }}
                        </dd>
                    </div>

                    <div class="py-4">
                        <dt class="text-xs text-slate-500">
                            Email
                        </dt>

                        <dd class="mt-1 break-all text-sm font-medium text-slate-900">
                            {{ $organization->email ?: '—' }}
                        </dd>
                    </div>

                    <div class="py-4">
                        <dt class="text-xs text-slate-500">
                            Website
                        </dt>

                        <dd class="mt-1 break-all text-sm font-medium text-slate-900">
                            {{ $organization->website ?: '—' }}
                        </dd>
                    </div>

                    <div class="py-4 last:pb-0">
                        <dt class="text-xs text-slate-500">
                            Status
                        </dt>

                        <dd class="mt-2">
                            @if ($organization->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                    Inactive
                                </span>
                            @endif
                        </dd>
                    </div>

                </dl>
            </section>

        </div>


        {{-- Location --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                Location
            </h2>

            <dl class="mt-5 grid gap-x-8 gap-y-6 sm:grid-cols-2 lg:grid-cols-3">

                <div class="lg:col-span-3">
                    <dt class="text-xs text-slate-500">
                        Address
                    </dt>

                    <dd class="mt-1 whitespace-pre-line text-sm font-medium text-slate-900">
                        {{ $organization->address ?: '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">
                        Area / Neighbourhood
                    </dt>

                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        {{ $organization->area ?: '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">
                        City
                    </dt>

                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        {{ $organization->city ?: '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">
                        LGA
                    </dt>

                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        {{ $organization->lga ?: '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">
                        State
                    </dt>

                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        {{ $organization->state ?: '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">
                        Country
                    </dt>

                    <dd class="mt-1 text-sm font-medium text-slate-900">
                        {{ $organization->country ?: '—' }}
                    </dd>
                </div>

            </dl>
        </section>


        {{-- Notes --}}
        @if ($organization->notes)
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                    Notes
                </h2>

                <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">
                    {{ $organization->notes }}
                </p>
            </section>
        @endif


        {{-- Related Records --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">
                    Related Records
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Records connected to this organization.
                </p>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Contacts
                    </div>

                    <div class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $organization->contacts_count }}
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Assessments
                    </div>

                    <div class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $organization->assessments_count }}
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Follow-ups
                    </div>

                    <div class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $organization->follow_ups_count }}
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Product Specifications
                    </div>

                    <div class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $organization->product_specifications_count }}
                    </div>
                </div>

            </div>
        </section>


        {{-- Assessments --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Assessments
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Commercial and procurement intelligence recorded for this organization.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @can('viewAny', App\Models\Assessment::class)
                        <a
                            href="{{ route('assessments.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            View All Assessments
                        </a>
                    @endcan

                    @can('create', App\Models\Assessment::class)
                        <a
                            href="{{ route('assessments.create', ['organization_id' => $organization->id]) }}"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
                        >
                            Record Assessment
                        </a>
                    @endcan
                </div>
            </div>

            @php
                $organizationAssessments = $organization->assessments()
                    ->with('assessedBy')
                    ->latest('assessment_date')
                    ->latest('id')
                    ->limit(5)
                    ->get();
            @endphp

            @if ($organizationAssessments->isNotEmpty())
                <div class="divide-y divide-slate-200">
                    @foreach ($organizationAssessments as $assessment)
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    @can('view', $assessment)
                                        <a
                                            href="{{ route('assessments.show', $assessment) }}"
                                            class="font-medium text-slate-900 hover:text-emerald-700"
                                        >
                                            Assessment — {{ $assessment->assessment_date->format('d M Y') }}
                                        </a>
                                    @else
                                        <span class="font-medium text-slate-900">
                                            Assessment — {{ $assessment->assessment_date->format('d M Y') }}
                                        </span>
                                    @endcan

                                    @if ($assessment->status === 'completed')
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            Completed
                                        </span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                            Draft
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">
                                    {{ $assessment->needs }}
                                </p>

                                <div class="mt-2 text-xs text-slate-500">
                                    Assessed by:
                                    {{ $assessment->assessedBy?->name ?? 'Staff member removed' }}

                                    @if ($assessment->estimated_budget !== null)
                                        <span class="mx-1 text-slate-300">•</span>
                                        Budget:
                                        ₦{{ number_format((float) $assessment->estimated_budget, 2) }}
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                @can('view', $assessment)
                                    <a
                                        href="{{ route('assessments.show', $assessment) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                    >
                                        View
                                    </a>
                                @endcan

                                @can('update', $assessment)
                                    <a
                                        href="{{ route('assessments.edit', $assessment) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                    >
                                        Edit
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-10 text-center">
                    <p class="text-sm font-medium text-slate-900">
                        No assessments recorded.
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Record an assessment to capture this organization's current needs and procurement situation.
                    </p>

                    @can('create', App\Models\Assessment::class)
                        <a
                            href="{{ route('assessments.create', ['organization_id' => $organization->id]) }}"
                            class="mt-4 inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
                        >
                            Record First Assessment
                        </a>
                    @endcan
                </div>
            @endif
        </section>


        {{-- Follow-ups --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Follow-ups
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Historical interactions, outcomes and next actions for this organization.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @can('viewAny', App\Models\FollowUp::class)
                        <a
                            href="{{ route('follow-ups.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            View All Follow-ups
                        </a>
                    @endcan

                    @can('create', App\Models\FollowUp::class)
                        <a
                            href="{{ route('follow-ups.create', ['organization_id' => $organization->id]) }}"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
                        >
                            Record Follow-up
                        </a>
                    @endcan
                </div>
            </div>

            @php
                $organizationFollowUps = $organization->followUps()
                    ->with(['contact', 'recordedBy'])
                    ->latest('follow_up_date')
                    ->latest('id')
                    ->limit(5)
                    ->get();
            @endphp

            @if ($organizationFollowUps->isNotEmpty())
                <div class="divide-y divide-slate-200">
                    @foreach ($organizationFollowUps as $followUp)
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    @can('view', $followUp)
                                        <a
                                            href="{{ route('follow-ups.show', $followUp) }}"
                                            class="font-medium text-slate-900 hover:text-emerald-700"
                                        >
                                            {{ $followUp->subject }}
                                        </a>
                                    @else
                                        <span class="font-medium text-slate-900">
                                            {{ $followUp->subject }}
                                        </span>
                                    @endcan

                                    @if ($followUp->status === 'completed')
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            Completed
                                        </span>
                                    @elseif ($followUp->status === 'cancelled')
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                            Cancelled
                                        </span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                            Open
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">
                                    {{ $followUp->outcome }}
                                </p>

                                <div class="mt-2 text-xs text-slate-500">
                                    {{ ucfirst($followUp->type) }}
                                    <span class="mx-1 text-slate-300">•</span>
                                    {{ $followUp->follow_up_date->format('d M Y, h:i A') }}

                                    @if($followUp->contact)
                                        <span class="mx-1 text-slate-300">•</span>
                                        {{ $followUp->contact->first_name }}
                                        {{ $followUp->contact->last_name }}
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                @can('view', $followUp)
                                    <a
                                        href="{{ route('follow-ups.show', $followUp) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                    >
                                        View
                                    </a>
                                @endcan

                                @can('update', $followUp)
                                    <a
                                        href="{{ route('follow-ups.edit', $followUp) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                    >
                                        Edit
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-10 text-center">
                    <p class="text-sm font-medium text-slate-900">
                        No follow-ups recorded.
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Record the first interaction with this organization.
                    </p>

                    @can('create', App\Models\FollowUp::class)
                        <a
                            href="{{ route('follow-ups.create', ['organization_id' => $organization->id]) }}"
                            class="mt-4 inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800"
                        >
                            Record First Follow-up
                        </a>
                    @endcan
                </div>
            @endif
        </section>


        {{-- Organization Actions --}}
        @canany(['activate', 'deactivate'], $organization)
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">
                    Organization Actions
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Control whether this organization is currently active.
                </p>

                <div class="mt-5 flex flex-wrap gap-3">

                    @can('activate', $organization)
                        @if (! $organization->is_active)
                            <form
                                method="POST"
                                action="{{ route('organizations.activate', $organization) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                >
                                    Activate Organization
                                </button>
                            </form>
                        @endif
                    @endcan

                    @can('deactivate', $organization)
                        @if ($organization->is_active)
                            <form
                                method="POST"
                                action="{{ route('organizations.deactivate', $organization) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2"
                                >
                                    Deactivate Organization
                                </button>
                            </form>
                        @endif
                    @endcan

                </div>
            </section>
        @endcanany


        {{-- Contacts --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Contacts
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        People who represent or work with this organization.
                    </p>
                </div>

                @can('create', App\Models\Contact::class)
                    <a
                        href="{{ route('contacts.create', ['organization_id' => $organization->id]) }}"
                        class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
                    >
                        Add Contact
                    </a>
                @endcan
            </div>

            @php
                $organizationContacts = $organization->contacts()
                    ->orderByDesc('is_primary')
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get();
            @endphp

            @if ($organizationContacts->isNotEmpty())
                <div class="divide-y divide-slate-200">
                    @foreach ($organizationContacts as $contact)
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a
                                        href="{{ route('contacts.show', $contact) }}"
                                        class="font-medium text-slate-900 hover:text-emerald-700"
                                    >
                                        {{ $contact->first_name }}
                                        {{ $contact->middle_name }}
                                        {{ $contact->last_name }}
                                    </a>

                                    @if ($contact->is_primary)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            Primary
                                        </span>
                                    @endif

                                    @if (! $contact->is_active)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                            Inactive
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    {{ $contact->position ?: 'Organization contact' }}
                                </div>

                                <div class="mt-2 text-sm text-slate-600">
                                    {{ $contact->phone }}

                                    @if ($contact->email)
                                        <span class="mx-1 text-slate-300">•</span>
                                        {{ $contact->email }}
                                    @endif
                                </div>
                            </div>

                            <div class="flex gap-2">
                                @can('view', $contact)
                                    <a
                                        href="{{ route('contacts.show', $contact) }}"
                                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        View
                                    </a>
                                @endcan

                                @can('update', $contact)
                                    <a
                                        href="{{ route('contacts.edit', $contact) }}"
                                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Edit
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-10 text-center">
                    <p class="text-sm font-medium text-slate-900">
                        No contacts recorded.
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Add the first contact for this organization.
                    </p>

                    @can('create', App\Models\Contact::class)
                        <a
                            href="{{ route('contacts.create', ['organization_id' => $organization->id]) }}"
                            class="mt-4 inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800"
                        >
                            Add First Contact
                        </a>
                    @endcan
                </div>
            @endif
        </section>

    </div>
@endsection
