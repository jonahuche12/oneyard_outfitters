@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">New Product Specification</h1>

            <p class="oy-page-description">
                Capture the exact production requirement for an organization. Supporting designs, samples and other artifacts can be added after the specification is created.
            </p>
        </div>

        <div class="oy-page-actions">
            <a href="{{ route('product-specifications.index') }}" class="oy-btn oy-btn-secondary">
                Back
            </a>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('product-specifications.store') }}"
        class="oy-form"
    >
        @csrf

        @if($errors->any())
            <div class="oy-alert oy-alert-danger mb-6">
                Please correct the highlighted fields and try again.
            </div>
        @endif

        <div class="oy-card">

            <div class="oy-card-header">
                <h2 class="oy-card-title">Specification Context</h2>
                <p class="oy-card-description">
                    Identify the organization, responsible contact and date of the requirement.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Organization <span class="oy-form-required">*</span>
                        </label>

                        <select
                            name="organization_id"
                            class="oy-select @error('organization_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Select organization</option>

                            @foreach($organizations as $organization)
                                <option
                                    value="{{ $organization->id }}"
                                    @selected(old('organization_id', $organizationId) == $organization->id)
                                >
                                    {{ $organization->name }} — {{ $organization->organization_code }}
                                </option>
                            @endforeach
                        </select>

                        @error('organization_id')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Organization Contact</label>

                        <select
                            name="contact_id"
                            class="oy-select @error('contact_id') is-invalid @enderror"
                        >
                            <option value="">No specific contact</option>

                            @foreach($contacts as $contact)
                                <option
                                    value="{{ $contact->id }}"
                                    @selected(old('contact_id') == $contact->id)
                                >
                                    {{ $contact->first_name }} {{ $contact->last_name }}
                                    @if($contact->position)
                                        — {{ $contact->position }}
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        @error('contact_id')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror

                        <div class="oy-form-help">
                            Only active contacts belonging to the selected organization are available.
                        </div>
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Specification Date <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="date"
                            name="specification_date"
                            value="{{ old('specification_date', now()->format('Y-m-d')) }}"
                            class="oy-input @error('specification_date') is-invalid @enderror"
                            required
                        >

                        @error('specification_date')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="oy-select @error('status') is-invalid @enderror"
                        >
                            <option value="draft" @selected(old('status', 'draft') === 'draft')>
                                Draft
                            </option>
                            <option value="confirmed" @selected(old('status') === 'confirmed')>
                                Confirmed
                            </option>
                            <option value="cancelled" @selected(old('status') === 'cancelled')>
                                Cancelled
                            </option>
                        </select>

                        @error('status')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="oy-card-header">
                <h2 class="oy-card-title">Product Requirement</h2>
                <p class="oy-card-description">
                    Define what is required and the commercial unit used for the specification.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Item Name <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="text"
                            name="item_name"
                            value="{{ old('item_name') }}"
                            class="oy-input @error('item_name') is-invalid @enderror"
                            required
                            placeholder="e.g. White School Shirt"
                        >

                        @error('item_name')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Product Type <span class="oy-form-required">*</span>
                        </label>

                        <select
                            name="product_type"
                            class="oy-select @error('product_type') is-invalid @enderror"
                            required
                        >
                            <option value="">Select type</option>

                            @foreach([
                                'uniform' => 'Uniform',
                                'sportswear' => 'Sportswear',
                                'bag' => 'Bag',
                                'shoe' => 'Shoe',
                                'branded_item' => 'Branded Item',
                                'school_supply' => 'School Supply',
                                'other' => 'Other',
                            ] as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('product_type') === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @error('product_type')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Unit <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="text"
                            name="unit"
                            value="{{ old('unit') }}"
                            class="oy-input @error('unit') is-invalid @enderror"
                            required
                            placeholder="e.g. piece, pair, set"
                        >

                        @error('unit')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Unit Price <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="number"
                            name="unit_price"
                            value="{{ old('unit_price') }}"
                            min="0"
                            step="0.01"
                            class="oy-input @error('unit_price') is-invalid @enderror"
                            required
                            placeholder="0.00"
                        >

                        @error('unit_price')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group sm:col-span-2">
                        <label class="oy-form-label">
                            Description <span class="oy-form-required">*</span>
                        </label>

                        <textarea
                            name="description"
                            class="oy-textarea @error('description') is-invalid @enderror"
                            required
                            placeholder="Describe the product requirement..."
                        >{{ old('description') }}</textarea>

                        @error('description')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="oy-card-header">
                <h2 class="oy-card-title">Production Details</h2>
                <p class="oy-card-description">
                    Capture materials, design, sizing, branding and quality requirements.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    @foreach([
                        'material' => 'Material',
                        'material_details' => 'Material Details',
                        'design_details' => 'Design Details',
                        'size_details' => 'Size / Measurement Details',
                        'branding_details' => 'Branding Details',
                        'quality_requirements' => 'Quality Requirements',
                        'special_instructions' => 'Special Instructions',
                        'notes' => 'Internal Notes',
                    ] as $field => $label)
                        <div class="oy-form-group">
                            <label class="oy-form-label">{{ $label }}</label>

                            <textarea
                                name="{{ $field }}"
                                class="oy-textarea @error($field) is-invalid @enderror"
                                placeholder="{{ $label }}..."
                            >{{ old($field) }}</textarea>

                            @error($field)
                                <div class="oy-error">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach

                </div>
            </div>

            <div class="oy-card-footer flex flex-wrap justify-end gap-2">
                <a
                    href="{{ route('product-specifications.index') }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Cancel
                </a>

                <button type="submit" class="oy-btn oy-btn-primary">
                    Create Specification
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
