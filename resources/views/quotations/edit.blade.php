@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Edit {{ $quotation->quotation_number }}</h1>
            <p class="oy-page-description">
                Update the draft quotation header. Quotation items remain managed from the quotation record.
            </p>
        </div>

        <div class="oy-page-actions">
            <a href="{{ route('quotations.show', $quotation) }}" class="oy-btn oy-btn-secondary">
                Back to Quotation
            </a>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('quotations.update', $quotation) }}"
        class="oy-form"
    >
        @csrf
        @method('PUT')

        <div class="oy-card">

            <div class="oy-card-header">
                <h2 class="oy-card-title">Quotation Details</h2>
                <p class="oy-card-description">
                    Quotation number and organization are immutable after creation.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    <div class="oy-form-group">
                        <label class="oy-form-label">Quotation Number</label>

                        <input
                            type="text"
                            value="{{ $quotation->quotation_number }}"
                            class="oy-input bg-slate-50"
                            disabled
                        >
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Organization</label>

                        <input
                            type="text"
                            value="{{ $quotation->organization->name }} — {{ $quotation->organization->organization_code }}"
                            class="oy-input bg-slate-50"
                            disabled
                        >
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Quotation Date <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="date"
                            name="quotation_date"
                            value="{{ old('quotation_date', $quotation->quotation_date->format('Y-m-d')) }}"
                            class="oy-input @error('quotation_date') is-invalid @enderror"
                            required
                        >

                        @error('quotation_date')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Valid Until</label>

                        <input
                            type="date"
                            name="valid_until"
                            value="{{ old('valid_until', $quotation->valid_until?->format('Y-m-d')) }}"
                            class="oy-input @error('valid_until') is-invalid @enderror"
                        >

                        @error('valid_until')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Expected Delivery Days <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="number"
                            name="expected_delivery_days"
                            value="{{ old('expected_delivery_days', $quotation->expected_delivery_days) }}"
                            min="1"
                            max="365"
                            step="1"
                            class="oy-input @error('expected_delivery_days') is-invalid @enderror"
                            required
                        >

                        <div class="oy-form-help">
                            Expected delivery period in calendar days.
                        </div>

                        @error('expected_delivery_days')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Discount</label>

                        <input
                            type="number"
                            name="discount"
                            value="{{ old('discount', $quotation->discount) }}"
                            min="0"
                            step="0.01"
                            class="oy-input @error('discount') is-invalid @enderror"
                        >

                        @error('discount')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Additional Charges</label>

                        <input
                            type="number"
                            name="additional_charges"
                            value="{{ old('additional_charges', $quotation->additional_charges) }}"
                            min="0"
                            step="0.01"
                            class="oy-input @error('additional_charges') is-invalid @enderror"
                        >

                        @error('additional_charges')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group sm:col-span-2">
                        <label class="oy-form-label">Terms</label>

                        <textarea
                            name="terms"
                            class="oy-textarea @error('terms') is-invalid @enderror"
                        >{{ old('terms', $quotation->terms) }}</textarea>

                        @error('terms')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group sm:col-span-2">
                        <label class="oy-form-label">Internal Notes</label>

                        <textarea
                            name="notes"
                            class="oy-textarea @error('notes') is-invalid @enderror"
                        >{{ old('notes', $quotation->notes) }}</textarea>

                        @error('notes')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="oy-card-footer flex justify-end gap-2">
                <a href="{{ route('quotations.show', $quotation) }}" class="oy-btn oy-btn-secondary">
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
