@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Edit Product Specification</h1>

            <p class="oy-page-description">
                Update the production requirement while preserving its supporting artifact history.
            </p>
        </div>

        <div class="oy-page-actions">
            <a
                href="{{ route('product-specifications.show', $productSpecification) }}"
                class="oy-btn oy-btn-secondary"
            >
                Back
            </a>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('product-specifications.update', $productSpecification) }}"
        class="oy-form"
    >
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="oy-alert oy-alert-danger mb-6">
                Please correct the highlighted fields and try again.
            </div>
        @endif

        <div class="oy-card">

            <div class="oy-card-header">
                <h2 class="oy-card-title">Specification Context</h2>
                <p class="oy-card-description">
                    Update the organization contact, date and specification status.
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
                            @foreach($productSpecification->organization ? [$productSpecification->organization] : [] as $organization)
                                <option value="{{ $organization->id }}" selected>
                                    {{ $organization->name }} — {{ $organization->organization_code }}
                                </option>
                            @endforeach
                        </select>

                        @error('organization_id')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror

                        <div class="oy-form-help">
                            The organization is intentionally fixed after creation.
                        </div>
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
                                    @selected(old('contact_id', $productSpecification->contact_id) == $contact->id)
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
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Specification Date <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="date"
                            name="specification_date"
                            value="{{ old('specification_date', $productSpecification->specification_date?->format('Y-m-d')) }}"
                            class="oy-input @error('specification_date') is-invalid @enderror"
                            required
                        >

                        @error('specification_date')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Status <span class="oy-form-required">*</span>
                        </label>

                        <select
                            name="status"
                            class="oy-select @error('status') is-invalid @enderror"
                            required
                        >
                            <option value="draft" @selected(old('status', $productSpecification->status) === 'draft')>
                                Draft
                            </option>
                            <option value="confirmed" @selected(old('status', $productSpecification->status) === 'confirmed')>
                                Confirmed
                            </option>
                            <option value="cancelled" @selected(old('status', $productSpecification->status) === 'cancelled')>
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
                            value="{{ old('item_name', $productSpecification->item_name) }}"
                            class="oy-input @error('item_name') is-invalid @enderror"
                            required
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
                                    @selected(old('product_type', $productSpecification->product_type) === $value)
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
                            value="{{ old('unit', $productSpecification->unit) }}"
                            class="oy-input @error('unit') is-invalid @enderror"
                            required
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
                            value="{{ old('unit_price', $productSpecification->unit_price) }}"
                            min="0"
                            step="0.01"
                            class="oy-input @error('unit_price') is-invalid @enderror"
                            required
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
                        >{{ old('description', $productSpecification->description) }}</textarea>

                        @error('description')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="oy-card-header">
                <h2 class="oy-card-title">Production Details</h2>
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
                            >{{ old($field, $productSpecification->{$field}) }}</textarea>

                            @error($field)
                                <div class="oy-error">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach

                </div>
            </div>

            <div class="oy-card-footer flex flex-wrap justify-end gap-2">
                <a
                    href="{{ route('product-specifications.show', $productSpecification) }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Cancel
                </a>

                <button type="submit" class="oy-btn oy-btn-primary">
                    Save Changes
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
