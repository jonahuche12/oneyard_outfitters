@extends('layouts.app')

@section('title', 'Lead Details')

@section('content')
@php
    $statusBadge = match (strtolower((string) $lead->status)) {
        'new' => 'oy-badge-info',
        'contacted', 'qualified', 'in_progress' => 'oy-badge-warning',
        'converted', 'won' => 'oy-badge-success',
        'lost', 'rejected', 'closed' => 'oy-badge-danger',
        default => 'oy-badge-neutral',
    };
@endphp

<div class="oy-page">

    {{-- Feedback --}}
    @if (session('success'))
        <div class="oy-alert oy-alert-success mb-5">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="oy-alert oy-alert-danger mb-5">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <a
        href="{{ route('leads.index') }}"
        class="mb-4 inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
    >
        ← Back to Leads
    </a>

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">{{ $lead->name }}</h1>

                <span class="oy-badge {{ $statusBadge }}">
                    <span class="oy-status-dot"></span>
                    {{ ucfirst($lead->status) }}
                </span>
            </div>

            <p class="oy-page-description">
                <span class="oy-code">Lead #{{ $lead->id }}</span>
                <span class="mx-1 text-slate-300">•</span>
                Updated {{ $lead->updated_at?->format('d M Y, h:i A') }}
            </p>
        </div>

        <div class="oy-page-actions">
            @can('update', $lead)
                <a href="{{ route('leads.edit', $lead) }}" class="oy-btn oy-btn-secondary">
                    Edit Lead
                </a>
            @endcan

            @can('delete', $lead)
                <form
                    method="POST"
                    action="{{ route('leads.destroy', $lead) }}"
                    onsubmit="return confirm('Delete this lead?');"
                >
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="oy-btn oy-btn-danger">
                        Delete
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Main column --}}
        <div class="space-y-6 lg:col-span-2">

            <div class="oy-card">
                <div class="oy-card-header">
                    <h2 class="oy-card-title">Lead Information</h2>
                    <p class="oy-card-description">
                        Contact and classification details submitted for this lead.
                    </p>
                </div>

                <div class="oy-card-body">
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</dt>
                            <dd class="mt-1.5 break-all text-sm font-medium text-slate-900">
                                @if ($lead->email)
                                    <a href="mailto:{{ $lead->email }}" class="hover:underline">{{ $lead->email }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</dt>
                            <dd class="mt-1.5 text-sm font-medium text-slate-900">
                                @if ($lead->phone)
                                    <a href="tel:{{ $lead->phone }}" class="hover:underline">{{ $lead->phone }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Organization</dt>
                            <dd class="mt-1.5 text-sm font-medium text-slate-900">
                                {{ $lead->organization_name ?: '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</dt>
                            <dd class="mt-1.5 text-sm font-medium text-slate-900">
                                {{ $lead->category ?: '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Source</dt>
                            <dd class="mt-1.5 text-sm font-medium text-slate-900">
                                {{ ucfirst(str_replace('_', ' ', $lead->source)) }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</dt>
                            <dd class="mt-1.5">
                                <span class="oy-badge {{ $statusBadge }}">
                                    <span class="oy-status-dot"></span>
                                    {{ ucfirst($lead->status) }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if ($lead->message)
                <div class="oy-card">
                    <div class="oy-card-header">
                        <h2 class="oy-card-title">Message</h2>
                        <p class="oy-card-description">What the lead wrote when they got in touch.</p>
                    </div>

                    <div class="oy-card-body">
                        <div class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ $lead->message }}</div>
                    </div>
                </div>
            @endif

            @if ($lead->notes)
                <div class="oy-card">
                    <div class="oy-card-header">
                        <h2 class="oy-card-title">Internal Notes</h2>
                        <p class="oy-card-description">Visible to staff only.</p>
                    </div>

                    <div class="oy-card-body">
                        <div class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ $lead->notes }}</div>
                    </div>
                </div>
            @endif

        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">

            <div class="oy-card">
                <div class="oy-card-header">
                    <h2 class="oy-card-title">Ownership</h2>
                </div>

                <div class="oy-card-body">
                    <dl class="divide-y divide-slate-100 text-sm">
                        <div class="pb-4">
                            <dt class="text-xs text-slate-500">Created by</dt>
                            <dd class="mt-1 font-medium text-slate-900">
                                {{ $lead->createdBy?->name ?: 'Public Website' }}
                            </dd>
                        </div>

                        <div class="py-4">
                            <dt class="text-xs text-slate-500">Assigned to</dt>
                            <dd class="mt-1 font-medium text-slate-900">
                                {{ $lead->assignedTo?->name ?: 'Unassigned' }}
                            </dd>
                        </div>

                        <div class="py-4">
                            <dt class="text-xs text-slate-500">Created</dt>
                            <dd class="mt-1 text-slate-900">
                                {{ $lead->created_at?->format('d M Y, h:i A') }}
                            </dd>
                        </div>

                        <div class="pt-4">
                            <dt class="text-xs text-slate-500">Last updated</dt>
                            <dd class="mt-1 text-slate-900">
                                {{ $lead->updated_at?->format('d M Y, h:i A') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            @can('assign', $lead)
                <div class="oy-card">
                    <div class="oy-card-header">
                        <h2 class="oy-card-title">Assignment</h2>
                        <p class="oy-card-description">Choose the staff member responsible for this lead.</p>
                    </div>

                    <div class="oy-card-body">
                        <form method="POST" action="{{ route('leads.assign', $lead) }}" class="oy-form">
                            @csrf
                            @method('PATCH')

                            <div class="oy-form-group">
                                <label for="assigned_to" class="oy-form-label">Assigned to</label>

                                <select
                                    id="assigned_to"
                                    name="assigned_to"
                                    class="oy-select @error('assigned_to') is-invalid @enderror"
                                >
                                    <option value="">Unassigned</option>
                                    @foreach (\App\Models\User::query()->where('is_active', true)->orderBy('name')->get() as $user)
                                        <option value="{{ $user->id }}" @selected($lead->assigned_to === $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('assigned_to')
                                    <p class="oy-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="oy-btn oy-btn-primary oy-btn-block mt-4">
                                Update Assignment
                            </button>
                        </form>
                    </div>
                </div>
            @endcan

            @if ($lead->organization_id)

                <div class="oy-card overflow-hidden">
                    <div class="border-b border-emerald-200 bg-emerald-50 px-6 py-5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                ✓
                            </div>

                            <div>
                                <h2 class="text-base font-semibold text-emerald-900">Lead Converted</h2>
                                <p class="mt-1 text-sm leading-5 text-emerald-800">
                                    This lead has already been converted to an organization.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="oy-card-body">
                        <a
                            href="{{ route('organizations.show', $lead->organization) }}"
                            class="oy-btn oy-btn-primary oy-btn-lg oy-btn-block"
                        >
                            <span>View Organization</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>

            @else
                @can('convert', $lead)

                    <div class="oy-card">
                        <div class="oy-card-header">
                            <h2 class="oy-card-title">Convert to Organization</h2>
                            <p class="oy-card-description">
                                Complete the details below to turn this lead into an organization record.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('leads.convert', $lead) }}" class="oy-form">
                            @csrf

                            <div class="oy-card-body space-y-8">

                                {{-- Identity --}}
                                <section>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Organization Identity
                                    </h3>

                                    <div class="oy-form-grid mt-4">
                                        <div class="oy-form-group">
                                            <label for="convert-name" class="oy-form-label">
                                                Organization Name <span class="oy-form-required">*</span>
                                            </label>

                                            <input
                                                id="convert-name"
                                                name="name"
                                                type="text"
                                                required
                                                value="{{ old('name', $lead->organization_name ?: $lead->name) }}"
                                                placeholder="Enter organization name"
                                                class="oy-input @error('name') is-invalid @enderror"
                                            >

                                            @error('name')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="oy-form-group">
                                            <label for="convert-type" class="oy-form-label">
                                                Organization Type <span class="oy-form-required">*</span>
                                            </label>

                                            <select
                                                id="convert-type"
                                                name="type"
                                                required
                                                class="oy-select @error('type') is-invalid @enderror"
                                            >
                                                <option value="">Select type</option>

                                                @foreach ([
                                                    'school' => 'School',
                                                    'company' => 'Company',
                                                    'church' => 'Church',
                                                    'ngo' => 'NGO',
                                                    'government' => 'Government',
                                                    'association' => 'Association',
                                                    'other' => 'Other',
                                                ] as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('type') === $value)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            @error('type')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="oy-form-group">
                                            <label for="convert-ownership" class="oy-form-label">
                                                Ownership <span class="oy-form-required">*</span>
                                            </label>

                                            <select
                                                id="convert-ownership"
                                                name="ownership"
                                                required
                                                class="oy-select @error('ownership') is-invalid @enderror"
                                            >
                                                <option value="">Select ownership</option>

                                                @foreach ([
                                                    'private' => 'Private',
                                                    'public' => 'Public',
                                                    'government' => 'Government',
                                                    'non_profit' => 'Non-profit',
                                                    'other' => 'Other',
                                                ] as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('ownership') === $value)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            @error('ownership')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </section>

                                {{-- Contact --}}
                                <section class="border-t border-slate-200 pt-8">
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Contact Information
                                    </h3>

                                    <div class="oy-form-grid mt-4">
                                        <div class="oy-form-group">
                                            <label for="convert-phone" class="oy-form-label">Phone</label>

                                            <input
                                                id="convert-phone"
                                                name="phone"
                                                type="text"
                                                value="{{ old('phone', $lead->phone) }}"
                                                placeholder="Phone number"
                                                class="oy-input @error('phone') is-invalid @enderror"
                                            >

                                            @error('phone')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="oy-form-group">
                                            <label for="convert-email" class="oy-form-label">Email</label>

                                            <input
                                                id="convert-email"
                                                name="email"
                                                type="email"
                                                value="{{ old('email', $lead->email) }}"
                                                placeholder="name@example.com"
                                                class="oy-input @error('email') is-invalid @enderror"
                                            >

                                            @error('email')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="oy-form-group">
                                            <label for="convert-website" class="oy-form-label">Website</label>

                                            <input
                                                id="convert-website"
                                                name="website"
                                                type="url"
                                                value="{{ old('website') }}"
                                                placeholder="https://example.com"
                                                class="oy-input @error('website') is-invalid @enderror"
                                            >

                                            @error('website')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </section>

                                {{-- Location --}}
                                <section class="border-t border-slate-200 pt-8">
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Location
                                    </h3>

                                    <div class="oy-form-grid mt-4">
                                        <div class="oy-form-group">
                                            <label for="convert-address" class="oy-form-label">Address</label>

                                            <textarea
                                                id="convert-address"
                                                name="address"
                                                rows="3"
                                                placeholder="Enter street address"
                                                class="oy-textarea @error('address') is-invalid @enderror"
                                            >{{ old('address') }}</textarea>

                                            @error('address')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="oy-form-group">
                                            <label for="convert-area" class="oy-form-label">Area</label>

                                            <input
                                                id="convert-area"
                                                name="area"
                                                type="text"
                                                value="{{ old('area') }}"
                                                placeholder="Area"
                                                class="oy-input @error('area') is-invalid @enderror"
                                            >

                                            @error('area')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="oy-form-group">
                                            <label for="convert-city" class="oy-form-label">City</label>

                                            <input
                                                id="convert-city"
                                                name="city"
                                                type="text"
                                                value="{{ old('city') }}"
                                                placeholder="City"
                                                class="oy-input @error('city') is-invalid @enderror"
                                            >

                                            @error('city')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="oy-form-group">
                                            <label for="convert-lga" class="oy-form-label">LGA</label>

                                            <input
                                                id="convert-lga"
                                                name="lga"
                                                type="text"
                                                value="{{ old('lga') }}"
                                                placeholder="Local Government Area"
                                                class="oy-input @error('lga') is-invalid @enderror"
                                            >

                                            @error('lga')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="oy-form-group">
                                            <label for="convert-state" class="oy-form-label">State</label>

                                            <input
                                                id="convert-state"
                                                name="state"
                                                type="text"
                                                value="{{ old('state') }}"
                                                placeholder="State"
                                                class="oy-input @error('state') is-invalid @enderror"
                                            >

                                            @error('state')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="oy-form-group">
                                            <label for="convert-country" class="oy-form-label">Country</label>

                                            <input
                                                id="convert-country"
                                                name="country"
                                                type="text"
                                                value="{{ old('country', 'Nigeria') }}"
                                                placeholder="Country"
                                                class="oy-input @error('country') is-invalid @enderror"
                                            >

                                            @error('country')
                                                <p class="oy-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </section>

                                {{-- Notes --}}
                                <section class="border-t border-slate-200 pt-8">
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Organization Notes
                                    </h3>

                                    <div class="oy-form-group mt-4">
                                        <label for="convert-notes" class="oy-form-label">Internal notes</label>

                                        <textarea
                                            id="convert-notes"
                                            name="notes"
                                            rows="4"
                                            placeholder="Add any useful internal notes..."
                                            class="oy-textarea @error('notes') is-invalid @enderror"
                                        >{{ old('notes') }}</textarea>

                                        <p class="oy-form-help">Optional. Visible to staff only.</p>

                                        @error('notes')
                                            <p class="oy-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </section>

                            </div>

                            <div class="oy-card-footer">
                                <button type="submit" class="oy-btn oy-btn-primary oy-btn-lg oy-btn-block">
                                    <span>Convert Lead to Organization</span>
                                    <span aria-hidden="true">→</span>
                                </button>

                                <p class="mt-3 text-center text-xs text-slate-500">
                                    Required fields are marked with <span class="text-red-500">*</span>
                                </p>
                            </div>
                        </form>
                    </div>

                @endcan
            @endif

        </div>
    </div>
</div>
@endsection
