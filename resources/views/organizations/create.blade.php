@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl">

    {{-- Page Header --}}
    <a
        href="{{ route('organizations.index') }}"
        class="mb-4 inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
    >
        ← Back to Organizations
    </a>

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Add Organization</h1>
            <p class="oy-page-description">
                Register a school, business, institution or other organization
                that Oneyard Outfitters may serve.
            </p>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('organizations.store') }}" class="oy-card oy-form">
        @csrf

        {{-- Identity --}}
        <section class="oy-card-body">
            <h2 class="oy-card-title">Organization Identity</h2>
            <p class="oy-card-description mb-5">
                Identify the organization and classify its operating structure.
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
                        value="{{ old('name') }}"
                        autocomplete="organization"
                        required
                        autofocus
                        placeholder="Enter organization name"
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
                    <label for="ownership" class="oy-form-label">
                        Ownership <span class="oy-form-required">*</span>
                    </label>

                    <select
                        id="ownership"
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

        {{-- Contact Information --}}
        <section class="oy-card-body border-t border-slate-200">
            <h2 class="oy-card-title">Contact Information</h2>
            <p class="oy-card-description mb-5">
                Primary contact channels for the organization.
            </p>

            <div class="oy-form-grid oy-form-grid-2">

                <div class="oy-form-group">
                    <label for="phone" class="oy-form-label">Phone</label>

                    <input
                        id="phone"
                        type="text"
                        name="phone"
                        value="{{ old('phone') }}"
                        autocomplete="tel"
                        placeholder="Phone number"
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
                        value="{{ old('email') }}"
                        autocomplete="email"
                        placeholder="organization@example.com"
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
        <section class="oy-card-body border-t border-slate-200">
            <h2 class="oy-card-title">Location</h2>
            <p class="oy-card-description mb-5">
                Record where the organization operates.
            </p>

            <div class="oy-form-grid oy-form-grid-2">

                <div class="oy-form-group sm:col-span-2">
                    <label for="address" class="oy-form-label">Address</label>

                    <textarea
                        id="address"
                        name="address"
                        rows="3"
                        placeholder="Street address"
                        class="oy-textarea @error('address') is-invalid @enderror"
                    >{{ old('address') }}</textarea>

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
                        value="{{ old('area') }}"
                        placeholder="Area or neighbourhood"
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
                        value="{{ old('city') }}"
                        placeholder="City"
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
                        value="{{ old('lga') }}"
                        placeholder="Local Government Area"
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
                        value="{{ old('state') }}"
                        placeholder="State"
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
        <section class="oy-card-body border-t border-slate-200">
            <h2 class="oy-card-title">Notes</h2>
            <p class="oy-card-description mb-5">
                Record useful context about the organization.
            </p>

            <div class="oy-form-group">
                <label for="notes" class="oy-form-label sr-only">Notes</label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="5"
                    placeholder="Additional notes..."
                    class="oy-textarea @error('notes') is-invalid @enderror"
                >{{ old('notes') }}</textarea>

                @error('notes')
                    <p class="oy-error">{{ $message }}</p>
                @enderror
            </div>
        </section>

        {{-- Form Actions --}}
        <div class="oy-card-footer flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('organizations.index') }}" class="oy-btn oy-btn-secondary">
                Cancel
            </a>

            <button type="submit" class="oy-btn oy-btn-primary">
                Create Organization
            </button>
        </div>
    </form>

</div>
@endsection
