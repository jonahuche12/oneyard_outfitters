@php
    $contact = $contact ?? null;

    $organizationLocked = $organizationLocked
        ?? (!$contact && request()->filled('organization_id'));

    $selectedOrganizationId = $organizationLocked
        ? request('organization_id')
        : old(
            'organization_id',
            $contact?->organization_id ?? request('organization_id')
        );
@endphp

<form method="POST" action="{{ $action }}" class="oy-form" id="contact-form">
    @csrf

    @if ($contact)
        @method('PUT')
    @endif

    {{-- Organization & Identity --}}
    <div class="oy-card">
        <div class="oy-card-header">
            <h2 class="oy-card-title">Organization & Identity</h2>
            <p class="oy-card-description">
                The organization this person represents and how to address them.
            </p>
        </div>

        <div class="oy-card-body">
            <div class="oy-form-grid oy-form-grid-2">

                <div class="oy-form-group sm:col-span-2">
                    <label for="organization_id" class="oy-form-label">
                        Organization <span class="oy-form-required">*</span>
                    </label>

                    @if ($organizationLocked)
                        <input
                            type="hidden"
                            name="organization_id"
                            value="{{ $selectedOrganizationId }}"
                        >
                    @endif

                    <select
                        id="organization_id"
                        name="organization_id"
                        required
                        @disabled($organizationLocked)
                        class="oy-select @error('organization_id') is-invalid @enderror {{ $organizationLocked ? 'cursor-not-allowed bg-slate-100 text-slate-600' : '' }}"
                    >
                        <option value="">Select organization</option>

                        @foreach ($organizations as $organization)
                            <option
                                value="{{ $organization->id }}"
                                @selected((string) $selectedOrganizationId === (string) $organization->id)
                            >
                                {{ $organization->name }} — {{ $organization->organization_code }}
                            </option>
                        @endforeach
                    </select>

                    @if ($organizationLocked)
                        <div class="oy-form-help">
                            This contact will be created under the selected organization.
                        </div>
                    @endif

                    @error('organization_id')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="first_name" class="oy-form-label">
                        First Name <span class="oy-form-required">*</span>
                    </label>

                    <input
                        id="first_name"
                        type="text"
                        name="first_name"
                        value="{{ old('first_name', $contact?->first_name) }}"
                        maxlength="100"
                        autocomplete="off"
                        required
                        class="oy-input @error('first_name') is-invalid @enderror"
                    >

                    @error('first_name')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="last_name" class="oy-form-label">
                        Last Name <span class="oy-form-required">*</span>
                    </label>

                    <input
                        id="last_name"
                        type="text"
                        name="last_name"
                        value="{{ old('last_name', $contact?->last_name) }}"
                        maxlength="100"
                        autocomplete="off"
                        required
                        class="oy-input @error('last_name') is-invalid @enderror"
                    >

                    @error('last_name')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="middle_name" class="oy-form-label">Middle Name</label>

                    <input
                        id="middle_name"
                        type="text"
                        name="middle_name"
                        value="{{ old('middle_name', $contact?->middle_name) }}"
                        maxlength="100"
                        autocomplete="off"
                        class="oy-input @error('middle_name') is-invalid @enderror"
                    >

                    @error('middle_name')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="position" class="oy-form-label">Position</label>

                    <input
                        id="position"
                        type="text"
                        name="position"
                        value="{{ old('position', $contact?->position) }}"
                        maxlength="150"
                        placeholder="e.g. Principal, Director, Procurement Officer"
                        class="oy-input @error('position') is-invalid @enderror"
                    >

                    @error('position')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Communication --}}
    <div class="oy-card mt-6">
        <div class="oy-card-header">
            <h2 class="oy-card-title">Communication</h2>
            <p class="oy-card-description">
                How Oneyard reaches this contact. A contact needs an email address to receive
                quotations and delivery activation links.
            </p>
        </div>

        <div class="oy-card-body">
            <div class="oy-form-grid oy-form-grid-2">

                <div class="oy-form-group">
                    <label for="phone" class="oy-form-label">
                        Phone <span class="oy-form-required">*</span>
                    </label>

                    <input
                        id="phone"
                        type="tel"
                        name="phone"
                        value="{{ old('phone', $contact?->phone) }}"
                        maxlength="50"
                        inputmode="tel"
                        autocomplete="off"
                        required
                        class="oy-input @error('phone') is-invalid @enderror"
                    >

                    @error('phone')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="alternate_phone" class="oy-form-label">Alternate Phone</label>

                    <input
                        id="alternate_phone"
                        type="tel"
                        name="alternate_phone"
                        value="{{ old('alternate_phone', $contact?->alternate_phone) }}"
                        maxlength="50"
                        inputmode="tel"
                        autocomplete="off"
                        class="oy-input @error('alternate_phone') is-invalid @enderror"
                    >

                    @error('alternate_phone')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="email" class="oy-form-label">Email</label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $contact?->email) }}"
                        maxlength="255"
                        autocomplete="off"
                        class="oy-input @error('email') is-invalid @enderror"
                    >

                    @error('email')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="oy-form-group">
                    <label for="whatsapp" class="oy-form-label">WhatsApp</label>

                    <input
                        id="whatsapp"
                        type="tel"
                        name="whatsapp"
                        value="{{ old('whatsapp', $contact?->whatsapp) }}"
                        maxlength="50"
                        inputmode="tel"
                        autocomplete="off"
                        placeholder="+2348012345678"
                        class="oy-input @error('whatsapp') is-invalid @enderror"
                    >

                    <div class="oy-form-help">
                        Include the country code, for example +2348012345678.
                    </div>

                    @error('whatsapp')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Contact Settings --}}
    <div class="oy-card mt-6">
        <div class="oy-card-header">
            <h2 class="oy-card-title">Contact Settings</h2>
            <p class="oy-card-description">
                Primary status and the notes currently held on this contact.
            </p>
        </div>

        <div class="oy-card-body">

            <label class="flex items-start gap-3">
                <input type="hidden" name="is_primary" value="0">

                <input
                    type="checkbox"
                    name="is_primary"
                    value="1"
                    @checked(old('is_primary', $contact?->is_primary ?? false))
                    class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                >

                <span>
                    <span class="block text-sm font-medium text-slate-700">Primary contact</span>
                    <span class="block text-xs text-slate-500">
                        Making this contact primary will remove primary status from the organization's current primary contact.
                    </span>
                </span>
            </label>

            @error('is_primary')
                <div class="oy-error mt-2">{{ $message }}</div>
            @enderror

            <div class="oy-form-group mt-5">
                <label for="notes" class="oy-form-label">Current Notes</label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="4"
                    class="oy-textarea @error('notes') is-invalid @enderror"
                >{{ old('notes', $contact?->notes) }}</textarea>

                @error('notes')
                    <div class="oy-error">{{ $message }}</div>
                @enderror
            </div>

        </div>

        <div class="oy-card-footer flex justify-end gap-2">
            <a href="{{ $cancelUrl }}" class="oy-btn oy-btn-secondary">
                Cancel
            </a>

            <button type="submit" class="oy-btn oy-btn-primary">
                {{ $submitLabel }}
            </button>
        </div>
    </div>

</form>
