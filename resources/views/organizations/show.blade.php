@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">

    {{-- Header --}}
    <div>
        <a
            href="{{ route('organizations.index') }}"
            class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
        >
            ← Back to Organizations
        </a>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-4">
                <span class="oy-avatar">
                    {{ strtoupper(substr($organization->name, 0, 1)) }}
                </span>

                <div>
                    <div class="oy-page-title-row">
                        <h1 class="oy-page-title">{{ $organization->name }}</h1>

                        @if ($organization->is_active)
                            <span class="oy-badge oy-badge-success">
                                <span class="oy-status-dot"></span>
                                Active
                            </span>
                        @else
                            <span class="oy-badge oy-badge-danger">
                                <span class="oy-status-dot"></span>
                                Inactive
                            </span>
                        @endif
                    </div>

                    <p class="oy-code mt-1">{{ $organization->organization_code }}</p>
                </div>
            </div>

            <div class="oy-page-actions">
                @can('create', App\Models\Assessment::class)
                    <a
                        href="{{ route('assessments.create', ['organization_id' => $organization->id]) }}"
                        class="oy-btn oy-btn-primary"
                    >
                        Record Assessment
                    </a>
                @endcan

            @can('create', App\Models\Quotation::class)
                                @if (($organization->product_specifications_count ?? 0) > 0)
                                    <a
                                        href="{{ route('quotations.create', ['organization_id' => $organization->id]) }}"
                                        class="oy-btn oy-btn-primary oy-btn-sm"
                                    >
                                        Create Quotation
                                    </a>
                                @else
                                    <button
                                        type="button"
                                        disabled
                                        aria-disabled="true"
                                        title="Add at least one product specification before creating a quotation."
                                        class="oy-btn oy-btn-primary oy-btn-sm cursor-not-allowed opacity-50"
                                    >
                                        Create Quotation
                                    </button>
                                @endif
                            @endcan

                @can('update', $organization)
                    <a href="{{ route('organizations.edit', $organization) }}" class="oy-btn oy-btn-secondary">
                        Edit Organization
                    </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Feedback --}}
    @if (session('success'))
        <div class="oy-alert oy-alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Identity and Contact --}}
    <div class="grid gap-6 lg:grid-cols-2">

        <section class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Organization Identity</h2>
            </div>

            <div class="oy-card-body">
                <dl class="divide-y divide-slate-100">
                    <div class="pb-4">
                        <dt class="text-xs text-slate-500">Organization code</dt>
                        <dd class="oy-code mt-1 text-sm font-medium text-slate-900">
                            {{ $organization->organization_code }}
                        </dd>
                    </div>

                    <div class="py-4">
                        <dt class="text-xs text-slate-500">Organization name</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ $organization->name }}
                        </dd>
                    </div>

                    <div class="py-4">
                        <dt class="text-xs text-slate-500">Type</dt>
                        <dd class="mt-1 text-sm font-medium capitalize text-slate-900">
                            {{ str_replace('_', ' ', $organization->type) }}
                        </dd>
                    </div>

                    <div class="pt-4">
                        <dt class="text-xs text-slate-500">Ownership</dt>
                        <dd class="mt-1 text-sm font-medium capitalize text-slate-900">
                            {{ str_replace('_', ' ', $organization->ownership) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Contact Information</h2>
            </div>

            <div class="oy-card-body">
                <dl class="divide-y divide-slate-100">
                    <div class="pb-4">
                        <dt class="text-xs text-slate-500">Phone</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            @if ($organization->phone)
                                <a href="tel:{{ $organization->phone }}" class="hover:underline">{{ $organization->phone }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div class="py-4">
                        <dt class="text-xs text-slate-500">Email</dt>
                        <dd class="mt-1 break-all text-sm font-medium text-slate-900">
                            @if ($organization->email)
                                <a href="mailto:{{ $organization->email }}" class="hover:underline">{{ $organization->email }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div class="pt-4">
                        <dt class="text-xs text-slate-500">Website</dt>
                        <dd class="mt-1 break-all text-sm font-medium text-slate-900">
                            {{ $organization->website ?: '—' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

    </div>

    {{-- Location --}}
    <section class="oy-card">
        <div class="oy-card-header">
            <h2 class="oy-card-title">Location</h2>
        </div>

        <div class="oy-card-body">
            <dl class="grid gap-x-8 gap-y-6 sm:grid-cols-2 lg:grid-cols-3">
                <div class="lg:col-span-3">
                    <dt class="text-xs text-slate-500">Address</dt>
                    <dd class="mt-1 whitespace-pre-line text-sm font-medium text-slate-900">
                        {{ $organization->address ?: '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Area / Neighbourhood</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $organization->area ?: '—' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">City</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $organization->city ?: '—' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">LGA</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $organization->lga ?: '—' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">State</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $organization->state ?: '—' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-slate-500">Country</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $organization->country ?: '—' }}</dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- Notes --}}
    @if ($organization->notes)
        <section class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Notes</h2>
            </div>

            <div class="oy-card-body">
                <p class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ $organization->notes }}</p>
            </div>
        </section>
    @endif

    {{-- Related Records --}}
    <section class="oy-card">
        <div class="oy-card-header">
            <h2 class="oy-card-title">Related Records</h2>
            <p class="oy-card-description">Records connected to this organization.</p>
        </div>

        <div class="oy-card-body">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'Contacts' => $organization->contacts_count,
                    'Assessments' => $organization->assessments_count,
                    'Follow-ups' => $organization->follow_ups_count,
                    'Product Specifications' => $organization->product_specifications_count,
                ] as $label => $count)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            {{ $label }}
                        </div>

                        <div class="mt-2 text-2xl font-semibold text-slate-900">
                            {{ $count }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Assessments --}}
    <section class="oy-card">
        <div class="oy-card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="oy-card-title">Assessments</h2>
                <p class="oy-card-description">
                    Commercial and procurement intelligence recorded for this organization.
                </p>
            </div>

            <div class="oy-page-actions">
                @can('viewAny', App\Models\Assessment::class)
                    <a href="{{ route('assessments.index') }}" class="oy-btn oy-btn-secondary oy-btn-sm">
                        View All Assessments
                    </a>
                @endcan

                @can('create', App\Models\Assessment::class)
                    <a
                        href="{{ route('assessments.create', ['organization_id' => $organization->id]) }}"
                        class="oy-btn oy-btn-primary oy-btn-sm"
                    >
                        Record Assessment
                    </a>
                @endcan
            </div>
        </div>

        @if ($organizationAssessments->isNotEmpty())
            <div class="divide-y divide-slate-200">
                @foreach ($organizationAssessments as $assessment)
                    <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                @can('view', $assessment)
                                    <a
                                        href="{{ route('assessments.show', $assessment) }}"
                                        class="font-medium text-slate-900 hover:underline"
                                    >
                                        Assessment — {{ $assessment->assessment_date->format('d M Y') }}
                                    </a>
                                @else
                                    <span class="font-medium text-slate-900">
                                        Assessment — {{ $assessment->assessment_date->format('d M Y') }}
                                    </span>
                                @endcan

                                @if ($assessment->status === 'completed')
                                    <span class="oy-badge oy-badge-success">Completed</span>
                                @else
                                    <span class="oy-badge oy-badge-warning">Draft</span>
                                @endif
                            </div>

                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">
                                {{ $assessment->needs }}
                            </p>

                            <div class="oy-meta mt-2">
                                Assessed by:
                                {{ $assessment->assessedBy?->name ?? 'Staff member removed' }}

                                @if ($assessment->estimated_budget !== null)
                                    <span class="mx-1 text-slate-300">•</span>
                                    Budget: ₦{{ number_format((float) $assessment->estimated_budget, 2) }}
                                @endif
                            </div>
                        </div>

                        <div class="oy-action-group shrink-0">
                            @can('view', $assessment)
                                <a href="{{ route('assessments.show', $assessment) }}" class="oy-btn oy-btn-secondary oy-btn-sm">
                                    View
                                </a>
                            @endcan

                            @can('update', $assessment)
                                <a href="{{ route('assessments.edit', $assessment) }}" class="oy-btn oy-btn-secondary oy-btn-sm">
                                    Edit
                                </a>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($organizationAssessments->hasPages())
                <div class="oy-card-footer">
                    {{ $organizationAssessments->links() }}
                </div>
            @endif
        @else
            <div class="oy-empty-state">
                <div class="oy-empty-state-title">No assessments recorded.</div>
                <p class="oy-empty-state-description">
                    Record an assessment to capture this organization's current needs and procurement situation.
                </p>

                @can('create', App\Models\Assessment::class)
                    <a
                        href="{{ route('assessments.create', ['organization_id' => $organization->id]) }}"
                        class="oy-btn oy-btn-primary oy-btn-sm mt-4"
                    >
                        Record First Assessment
                    </a>
                @endcan
            </div>
        @endif
    </section>

    {{-- Follow-ups --}}
    <section class="oy-card">
        <div class="oy-card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="oy-card-title">Follow-ups</h2>
                <p class="oy-card-description">
                    Historical interactions, outcomes and next actions for this organization.
                </p>
            </div>

            <div class="oy-page-actions">
                @can('viewAny', App\Models\FollowUp::class)
                    <a href="{{ route('follow-ups.index') }}" class="oy-btn oy-btn-secondary oy-btn-sm">
                        View All Follow-ups
                    </a>
                @endcan

                @can('create', App\Models\FollowUp::class)
                    <a
                        href="{{ route('follow-ups.create', ['organization_id' => $organization->id]) }}"
                        class="oy-btn oy-btn-primary oy-btn-sm"
                    >
                        Record Follow-up
                    </a>
                @endcan
            </div>
        </div>

        @if ($organizationFollowUps->isNotEmpty())
            <div class="divide-y divide-slate-200">
                @foreach ($organizationFollowUps as $followUp)
                    <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                @can('view', $followUp)
                                    <a
                                        href="{{ route('follow-ups.show', $followUp) }}"
                                        class="font-medium text-slate-900 hover:underline"
                                    >
                                        {{ $followUp->subject }}
                                    </a>
                                @else
                                    <span class="font-medium text-slate-900">{{ $followUp->subject }}</span>
                                @endcan

                                @if ($followUp->status === 'completed')
                                    <span class="oy-badge oy-badge-success">Completed</span>
                                @elseif ($followUp->status === 'cancelled')
                                    <span class="oy-badge oy-badge-neutral">Cancelled</span>
                                @else
                                    <span class="oy-badge oy-badge-warning">Open</span>
                                @endif
                            </div>

                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">
                                {{ $followUp->outcome }}
                            </p>

                            <div class="oy-meta mt-2">
                                {{ ucfirst($followUp->type) }}
                                <span class="mx-1 text-slate-300">•</span>
                                {{ $followUp->follow_up_date->format('d M Y, h:i A') }}

                                @if ($followUp->contact)
                                    <span class="mx-1 text-slate-300">•</span>
                                    {{ $followUp->contact->first_name }}
                                    {{ $followUp->contact->last_name }}
                                @endif
                            </div>
                        </div>

                        <div class="oy-action-group shrink-0">
                            @can('view', $followUp)
                                <a href="{{ route('follow-ups.show', $followUp) }}" class="oy-btn oy-btn-secondary oy-btn-sm">
                                    View
                                </a>
                            @endcan

                            @can('update', $followUp)
                                <a href="{{ route('follow-ups.edit', $followUp) }}" class="oy-btn oy-btn-secondary oy-btn-sm">
                                    Edit
                                </a>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($organizationFollowUps->hasPages())
                <div class="oy-card-footer">
                    {{ $organizationFollowUps->links() }}
                </div>
            @endif
        @else
            <div class="oy-empty-state">
                <div class="oy-empty-state-title">No follow-ups recorded.</div>
                <p class="oy-empty-state-description">
                    Record the first interaction with this organization.
                </p>

                @can('create', App\Models\FollowUp::class)
                    <a
                        href="{{ route('follow-ups.create', ['organization_id' => $organization->id]) }}"
                        class="oy-btn oy-btn-primary oy-btn-sm mt-4"
                    >
                        Record First Follow-up
                    </a>
                @endcan
            </div>
        @endif
    </section>

    <!-- QUOTATIONS_SECTION -->
    {{-- Quotations --}}
    <div class="oy-card">
        <div class="oy-card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="oy-card-title">Quotations</h2>
                <p class="oy-card-description">
                    Commercial quotations generated for this organization.
                </p>
            </div>

            @can('create', App\Models\Quotation::class)
                @if (($organization->product_specifications_count ?? 0) > 0)
                    <a
                        href="{{ route('quotations.create', ['organization_id' => $organization->id]) }}"
                        class="oy-btn oy-btn-primary oy-btn-sm"
                    >
                        Create Quotation
                    </a>
                @else
                    <button
                        type="button"
                        disabled
                        aria-disabled="true"
                        title="Add at least one product specification before creating a quotation."
                        class="oy-btn oy-btn-primary oy-btn-sm cursor-not-allowed opacity-50"
                    >
                        Create Quotation
                    </button>
                @endif
            @endcan
        </div>

        <div id="organization-quotations-content" class="oy-card-body">
            @include('organizations.partials.quotations', [
                'organization' => $organization,
                'organizationQuotations' => $organizationQuotations,
            ])
        </div>
    </div>

    <!-- PRODUCT_SPECIFICATIONS_SECTION -->
    {{-- Product Specifications --}}
    <div class="oy-card">
        <div class="oy-card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="oy-card-title">Product Specifications</h2>
                <p class="oy-card-description">
                    Production requirements collected from this organization.
                </p>
            </div>

            <div class="oy-page-actions">
                @can('viewAny', App\Models\ProductSpecification::class)
                    <a
                        href="{{ route('product-specifications.index', ['search' => $organization->organization_code]) }}"
                        class="oy-btn oy-btn-sm oy-btn-secondary"
                    >
                        View All
                    </a>
                @endcan

                @can('create', App\Models\ProductSpecification::class)
                    <a
                        href="{{ route('product-specifications.create', ['organization_id' => $organization->id]) }}"
                        class="oy-btn oy-btn-sm oy-btn-primary"
                    >
                        Add Specification
                    </a>
                @endcan
            </div>
        </div>

        @if ($organizationProductSpecifications->isNotEmpty())
            <div class="oy-table-scroll">
                <table class="oy-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Unit Price</th>
                            <th>Artifacts</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($organizationProductSpecifications as $specification)
                            <tr>
                                <td>
                                    <div class="oy-table-primary">{{ $specification->item_name }}</div>
                                    <div class="oy-table-secondary">{{ $specification->unit }}</div>
                                </td>

                                <td>{{ ucwords(str_replace('_', ' ', $specification->product_type)) }}</td>

                                <td>{{ $specification->specification_date?->format('d M Y') }}</td>

                                <td>{{ number_format((float) $specification->unit_price, 2) }}</td>

                                <td>{{ $specification->artifacts_count }}</td>

                                <td>
                                    @if ($specification->status === 'confirmed')
                                        <span class="oy-badge oy-badge-success">Confirmed</span>
                                    @elseif ($specification->status === 'cancelled')
                                        <span class="oy-badge oy-badge-danger">Cancelled</span>
                                    @else
                                        <span class="oy-badge oy-badge-warning">Draft</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="oy-action-group">
                                        @can('view', $specification)
                                            <a
                                                href="{{ route('product-specifications.show', $specification) }}"
                                                class="oy-btn oy-btn-sm oy-btn-secondary"
                                            >
                                                View
                                            </a>
                                        @endcan

                                        @can('update', $specification)
                                            <a
                                                href="{{ route('product-specifications.edit', $specification) }}"
                                                class="oy-btn oy-btn-sm oy-btn-ghost"
                                            >
                                                Edit
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($organizationProductSpecifications->hasPages())
                <div class="oy-card-footer">
                    {{ $organizationProductSpecifications->links() }}
                </div>
            @endif
        @else
            <div class="oy-empty-state">
                <h3 class="oy-empty-state-title">No production specifications recorded</h3>

                <p class="oy-empty-state-description">
                    Capture the organization's product requirements, materials,
                    designs, sizing, branding and quality requirements before production.
                </p>

                @can('create', App\Models\ProductSpecification::class)
                    <a
                        href="{{ route('product-specifications.create', ['organization_id' => $organization->id]) }}"
                        class="oy-btn oy-btn-primary oy-btn-sm mt-4"
                    >
                        Record First Specification
                    </a>
                @endcan
            </div>
        @endif
    </div>

    {{-- Contacts --}}
    <section class="oy-card">
        <div class="oy-card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="oy-card-title">Contacts</h2>
                <p class="oy-card-description">
                    People who represent or work with this organization.
                </p>
            </div>

            @can('create', App\Models\Contact::class)
                <a
                    href="{{ route('contacts.create', ['organization_id' => $organization->id]) }}"
                    class="oy-btn oy-btn-primary oy-btn-sm"
                >
                    Add Contact
                </a>
            @endcan
        </div>

        @if ($organizationContacts->isNotEmpty())
            <div class="divide-y divide-slate-200">
                @foreach ($organizationContacts as $contact)
                    <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <a
                                    href="{{ route('contacts.show', $contact) }}"
                                    class="font-medium text-slate-900 hover:underline"
                                >
                                    {{ $contact->first_name }}
                                    {{ $contact->middle_name }}
                                    {{ $contact->last_name }}
                                </a>

                                @if ($contact->is_primary)
                                    <span class="oy-badge oy-badge-success">Primary</span>
                                @endif

                                @if (! $contact->is_active)
                                    <span class="oy-badge oy-badge-neutral">Inactive</span>
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

                        <div class="oy-action-group shrink-0">
                            @can('view', $contact)
                                <a href="{{ route('contacts.show', $contact) }}" class="oy-btn oy-btn-secondary oy-btn-sm">
                                    View
                                </a>
                            @endcan

                            @can('update', $contact)
                                <a href="{{ route('contacts.edit', $contact) }}" class="oy-btn oy-btn-secondary oy-btn-sm">
                                    Edit
                                </a>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($organizationContacts->hasPages())
                <div class="oy-card-footer">
                    {{ $organizationContacts->links() }}
                </div>
            @endif
        @else
            <div class="oy-empty-state">
                <div class="oy-empty-state-title">No contacts recorded.</div>
                <p class="oy-empty-state-description">
                    Add the first contact for this organization.
                </p>

                @can('create', App\Models\Contact::class)
                    <a
                        href="{{ route('contacts.create', ['organization_id' => $organization->id]) }}"
                        class="oy-btn oy-btn-primary oy-btn-sm mt-4"
                    >
                        Add First Contact
                    </a>
                @endcan
            </div>
        @endif
    </section>

    {{-- Organization Actions --}}
    @canany(['activate', 'deactivate', 'export'], $organization)
        <section class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Organization Actions</h2>
                <p class="oy-card-description">
                    Manage organization status and export structured intelligence data for analysis.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-page-actions">

                    @can('export', $organization)
                        <a
                            href="{{ route('organizations.export-intelligence', $organization) }}"
                            class="oy-btn oy-btn-secondary"
                            title="Download organization data without images or artifact files"
                        >
                            Export Intelligence Data
                        </a>
                    @endcan

                    @can('activate', $organization)
                        @if (! $organization->is_active)
                            <form method="POST" action="{{ route('organizations.activate', $organization) }}">
                                @csrf
                                @method('PATCH')

                                <button type="submit" class="oy-btn oy-btn-success">
                                    Activate Organization
                                </button>
                            </form>
                        @endif
                    @endcan

                    @can('deactivate', $organization)
                        @if ($organization->is_active)
                            <form method="POST" action="{{ route('organizations.deactivate', $organization) }}">
                                @csrf
                                @method('PATCH')

                                <button type="submit" class="oy-btn oy-btn-danger">
                                    Deactivate Organization
                                </button>
                            </form>
                        @endif
                    @endcan

                </div>
            </div>
        </section>
    @endcanany

</div>
@endsection
