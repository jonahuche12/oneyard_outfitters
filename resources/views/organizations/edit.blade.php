@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl">

    {{-- Page Header --}}
    <a
        href="{{ route('organizations.show', $organization) }}"
        class="mb-4 inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
    >
        ← Back to {{ $organization->name }}
    </a>

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Edit Organization</h1>
            <p class="oy-page-description">
                Update the organization's information. Its organization code remains
                stable once assigned.
            </p>
        </div>
    </div>

    {{-- Identity Reference --}}
    <div class="oy-card mb-6">
        <div class="oy-card-body flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Organization Code
                </div>

                <div class="oy-code mt-1 text-sm font-semibold text-slate-900">
                    {{ $organization->organization_code }}
                </div>
            </div>

            <div>
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
        </div>
    </div>

    {{-- Form --}}
    <form
        method="POST"
        action="{{ route('organizations.update', $organization) }}"
        class="oy-card oy-form"
    >
        @csrf
        @method('PUT')

        {{-- Identity --}}
        <section class="oy-card-body">
            <h2 class="oy-card-title">Organization Identity</h2>
            <p class="oy-card-description mb-5">
                Update the organization's classification and name.
            </p>

            <div class="oy-form-grid oy-form-grid-2">

                <div class="oy-form-group sm:col-span-2">
                    <label for="name" class="oy-form-label">
                        Organization name <span class="oy-form-required">*</span>
                    </label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $organization->name) }}"
                        autocomplete="organization"
                        required
                        autofocus
                        class="oy-input @error('name') is-invalid @enderror"
                    >

                    @error('name')
                        <p class="oy-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="type" class="oy-form-label">
                        Organization type <span class="oy-form-required">*</span>
                    </label>

                    <select
                        id="type"
                        name="type"
                        required
                        class="oy-select @error('type') is-invalid @enderror"
                    >
                        @foreach ([
                            'school' => 'School',
                            'company' => 'Company',
                            'church' => 'Church',
                            'ngo' => 'NGO',
                            'government' => 'Government',
                            'association' => 'Association',
                            'other' => 'Other',
                        ] as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(old('type', $organization->type) === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    @error('type')
                        <p class="oy-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="ownership" class="oy-form-label">
                        Ownership <span class="oy-form-required">*</span>
                    </label>

                    <select
                        id="ownership"
                        name="ownership"
                        required
                        class="oy-select @error('ownership') is-invalid @enderror"
                    >
                        @foreach ([
                            'private' => 'Private',
                            'public' => 'Public',
                            'government' => 'Government',
                            'non_profit' => 'Non-profit',
                            'other' => 'Other',
                        ] as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(old('ownership', $organization->ownership) === $value)
                            >
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

        {{-- Contact Information --}}
        <section class="oy-card-body border-t border-slate-200">
            <h2 class="oy-card-title">Contact Information</h2>
            <p class="oy-card-description mb-5">
                Update the organization's contact channels.
            </p>

            <div class="oy-form-grid oy-form-grid-2">

                <div class="oy-form-group">
                    <label for="phone" class="oy-form-label">Phone</label>

                    <input
                        id="phone"
                        type="text"
                        name="phone"
                        value="{{ old('phone', $organization->phone) }}"
                        autocomplete="tel"
                        class="oy-input @error('phone') is-invalid @enderror"
                    >

                    @error('phone')
                        <p class="oy-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="email" class="oy-form-label">Email address</label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $organization->email) }}"
                        autocomplete="email"
                        class="oy-input @error('email') is-invalid @enderror"
                    >

                    @error('email')
                        <p class="oy-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="oy-form-group sm:col-span-2">
                    <label for="website" class="oy-form-label">Website</label>

                    <input
                        id="website"
                        type="url"
                        name="website"
                        value="{{ old('website', $organization->website) }}"
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
        <section class="oy-card-body border-t border-slate-200">
            <h2 class="oy-card-title">Location</h2>
            <p class="oy-card-description mb-5">
                Update the organization's operating location.
            </p>

            <div class="oy-form-grid oy-form-grid-2">

                <div class="oy-form-group sm:col-span-2">
                    <label for="address" class="oy-form-label">Address</label>

                    <textarea
                        id="address"
                        name="address"
                        rows="3"
                        class="oy-textarea @error('address') is-invalid @enderror"
                    >{{ old('address', $organization->address) }}</textarea>

                    @error('address')
                        <p class="oy-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="area" class="oy-form-label">Area / Neighbourhood</label>

                    <input
                        id="area"
                        type="text"
                        name="area"
                        value="{{ old('area', $organization->area) }}"
                        class="oy-input @error('area') is-invalid @enderror"
                    >

                    @error('area')
                        <p class="oy-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="city" class="oy-form-label">City</label>

                    <input
                        id="city"
                        type="text"
                        name="city"
                        value="{{ old('city', $organization->city) }}"
                        class="oy-input @error('city') is-invalid @enderror"
                    >

                    @error('city')
                        <p class="oy-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="lga" class="oy-form-label">LGA</label>

                    <input
                        id="lga"
                        type="text"
                        name="lga"
                        value="{{ old('lga', $organization->lga) }}"
                        class="oy-input @error('lga') is-invalid @enderror"
                    >

                    @error('lga')
                        <p class="oy-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="state" class="oy-form-label">State</label>

                    <input
                        id="state"
                        type="text"
                        name="state"
                        value="{{ old('state', $organization->state) }}"
                        class="oy-input @error('state') is-invalid @enderror"
                    >

                    @error('state')
                        <p class="oy-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="country" class="oy-form-label">Country</label>

                    <input
                        id="country"
                        type="text"
                        name="country"
                        value="{{ old('country', $organization->country ?: 'Nigeria') }}"
                        class="oy-input @error('country') is-invalid @enderror"
                    >

                    @error('country')
                        <p class="oy-error">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </section>

        {{-- Notes --}}
        <section class="oy-card-body border-t border-slate-200">
            <h2 class="oy-card-title">Notes</h2>
            <p class="oy-card-description mb-5">
                Update useful context about this organization.
            </p>

            <div class="oy-form-group">
                <label for="notes" class="oy-form-label sr-only">Notes</label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="5"
                    placeholder="Additional notes..."
                    class="oy-textarea @error('notes') is-invalid @enderror"
                >{{ old('notes', $organization->notes) }}</textarea>

                @error('notes')
                    <p class="oy-error">{{ $message }}</p>
                @enderror
            </div>
        </section>

        {{-- Form Actions --}}
        <div class="oy-card-footer flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('organizations.show', $organization) }}" class="oy-btn oy-btn-secondary">
                Cancel
            </a>

            <button type="submit" class="oy-btn oy-btn-primary">
                Save Changes
            </button>
        </div>
    </form>

</div>
@endsection
