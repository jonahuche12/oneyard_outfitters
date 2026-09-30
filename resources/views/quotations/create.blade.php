@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">New Quotation</h1>

            <p class="oy-page-description">
                Select the organization's requirements and expected quantities.
                Current Product Specification prices are used to calculate the initial quotation amounts.
            </p>
        </div>

        <div class="oy-page-actions">
            <a href="{{ route('organizations.show', $organization) }}" class="oy-btn oy-btn-secondary">
                Back to Organization
            </a>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('quotations.store') }}"
        class="oy-form"
        id="quotation-create-form"
    >
        @csrf

        <input
            type="hidden"
            name="organization_id"
            value="{{ $organization->id }}"
        >

        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Quotation Context</h2>

                <p class="oy-card-description">
                    This quotation is being prepared for this organization.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Organization
                        </label>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="font-medium text-slate-900">
                                {{ $organization->name }}
                            </div>

                            <div class="mt-1 text-sm text-slate-500">
                                {{ $organization->organization_code }}
                            </div>
                        </div>

                        <div class="oy-form-help">
                            Organization is fixed from the organization account.
                        </div>
                    </div>

                    <div class="oy-form-group">
                        <label
                            for="quotation-contact"
                            class="oy-form-label"
                        >
                            Organization Contact
                        </label>

                        <select
                            id="quotation-contact"
                            name="contact_id"
                            class="oy-select @error('contact_id') is-invalid @enderror"
                        >
                            <option value="">
                                No specific contact
                            </option>

                            @foreach($contacts as $contact)
                                <option
                                    value="{{ $contact->id }}"
                                    @selected(old('contact_id') == $contact->id)
                                >
                                    {{ trim(implode(' ', array_filter([
                                        $contact->first_name,
                                        $contact->middle_name,
                                        $contact->last_name,
                                    ]))) }}
                                    @if($contact->position)
                                        — {{ $contact->position }}
                                    @endif
                                    @if($contact->is_primary)
                                        — Primary
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        <div class="oy-form-help">
                            Select the contact associated with this quotation, if applicable.
                        </div>

                        @error('contact_id')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        <div class="oy-card mt-6">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Product Specifications</h2>

                <p class="oy-card-description">
                    Select the organization's requirements, enter quantities,
                    and review the estimated amounts using the current specification prices.
                </p>
            </div>

            <div class="oy-card-body">
                @if($productSpecifications->isEmpty())
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        No active Product Specifications are available for this organization.
                        Create a Product Specification before creating a quotation.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="oy-table min-w-[850px]">
                            <thead>
                                <tr>
                                    <th class="w-12">Select</th>
                                    <th>Product Specification</th>
                                    <th>Unit</th>
                                    <th>Unit Price</th>
                                    <th class="w-32">Quantity</th>
                                    <th>Estimated Amount</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($productSpecifications as $specification)
                                    @php
                                        $selected = in_array(
                                            $specification->id,
                                            old('product_specification_ids', []),
                                            true
                                        );

                                        $quantity = old(
                                            "quantities.{$specification->id}"
                                        );
                                    @endphp

                                    <tr
                                        class="quotation-specification-row"
                                        data-unit-price="{{ $specification->unit_price }}"
                                    >
                                        <td>
                                            <input
                                                type="checkbox"
                                                id="quotation-specification-{{ $specification->id }}"
                                                name="product_specification_ids[]"
                                                value="{{ $specification->id }}"
                                                class="quotation-specification-checkbox rounded border-slate-300"
                                                @checked($selected)
                                            >
                                        </td>

                                        <td>
                                            <label
                                                for="quotation-specification-{{ $specification->id }}"
                                                class="cursor-pointer"
                                            >
                                                <div class="font-medium text-slate-900">
                                                    {{ $specification->item_name }}
                                                </div>

                                                <div class="mt-1 text-sm text-slate-500">
                                                    {{ $specification->product_type }}
                                                </div>

                                                @if($specification->description)
                                                    <div class="mt-1 text-sm text-slate-600">
                                                        {{ $specification->description }}
                                                    </div>
                                                @endif
                                            </label>
                                        </td>

                                        <td>
                                            {{ $specification->unit ?: 'piece' }}
                                        </td>

                                        <td class="whitespace-nowrap font-medium text-slate-900">
                                            ₦{{ number_format((float) $specification->unit_price, 2) }}
                                        </td>

                                        <td>
                                            <input
                                                id="quantity-{{ $specification->id }}"
                                                type="number"
                                                name="quantities[{{ $specification->id }}]"
                                                value="{{ $quantity }}"
                                                min="0.01"
                                                step="0.01"
                                                class="oy-input quotation-specification-quantity w-28"
                                                placeholder="Qty"
                                            >

                                            @error("quantities.{$specification->id}")
                                                <p class="mt-1 text-xs text-red-600">
                                                    {{ $message }}
                                                </p>
                                            @enderror
                                        </td>

                                        <td class="whitespace-nowrap font-semibold text-slate-900">
                                            <span class="quotation-specification-line-total">
                                                ₦0.00
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-end border-t border-slate-200 pt-4">
                        <div class="text-right">
                            <div class="text-sm text-slate-500">
                                Estimated Subtotal
                            </div>

                            <div
                                id="quotation-create-estimated-subtotal"
                                class="mt-1 text-xl font-semibold text-slate-900"
                            >
                                ₦0.00
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="oy-card mt-6">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Quotation Details</h2>

                <p class="oy-card-description">
                    These details belong to the quotation itself. Unit prices will be
                    entered after the draft is created.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Quotation Date <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="date"
                            name="quotation_date"
                            value="{{ old('quotation_date', now()->format('Y-m-d')) }}"
                            class="oy-input @error('quotation_date') is-invalid @enderror"
                            required
                        >

                        @error('quotation_date')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Valid Until
                        </label>

                        <input
                            type="date"
                            name="valid_until"
                            value="{{ old('valid_until') }}"
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
                            value="{{ old('expected_delivery_days', 30) }}"
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
                        <label class="oy-form-label">
                            Discount
                        </label>

                        <input
                            type="number"
                            name="discount"
                            value="{{ old('discount', 0) }}"
                            min="0"
                            step="0.01"
                            class="oy-input @error('discount') is-invalid @enderror"
                        >

                        @error('discount')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Additional Charges
                        </label>

                        <input
                            type="number"
                            name="additional_charges"
                            value="{{ old('additional_charges', 0) }}"
                            min="0"
                            step="0.01"
                            class="oy-input @error('additional_charges') is-invalid @enderror"
                        >

                        @error('additional_charges')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="font-medium text-slate-900">
                        Pricing is completed after the draft is created.
                    </div>

                    <p class="mt-1 text-sm text-slate-600">
                        The selected requirements and quantities will become quotation
                        items. Staff can then enter the actual quoted unit price for
                        each item on the quotation.
                    </p>
                </div>
            </div>
        </div>

        <div class="oy-card mt-6">
            <div class="oy-card-body">

                <div class="oy-form-group">
                    <label class="oy-form-label">
                        Terms
                    </label>

                    <textarea
                        name="terms"
                        class="oy-textarea @error('terms') is-invalid @enderror"
                        placeholder="Quotation terms, payment terms, delivery conditions..."
                    >{{ old('terms') }}</textarea>

                    @error('terms')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="oy-form-group mt-4">
                    <label class="oy-form-label">
                        Internal Notes
                    </label>

                    <textarea
                        name="notes"
                        class="oy-textarea @error('notes') is-invalid @enderror"
                        placeholder="Internal notes for staff..."
                    >{{ old('notes') }}</textarea>

                    @error('notes')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <div class="oy-card-footer flex justify-end gap-2">

                <a
                    href="{{ route('organizations.show', $organization) }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="oy-btn oy-btn-primary"
                    @disabled($productSpecifications->isEmpty())
                >
                    Create Draft Quotation
                </button>

            </div>
        </div>

    </form>
</div>
@endsection
