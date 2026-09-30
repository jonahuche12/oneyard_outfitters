@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">New Procurement Requirement</h1>

            <p class="oy-page-description">
                Record the material, product or service that needs to be sourced.
                Procurement requirements can be created independently or against an assigned order.
            </p>
        </div>

        <div class="oy-page-actions">
            @if($order)
                <a
                    href="{{ route('orders.show', $order) }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Back to Order
                </a>
            @else
                <a
                    href="{{ route('procurements.index') }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Back to Procurement
                </a>
            @endif
        </div>
    </div>

    <form
        method="POST"
        action="{{ $order
            ? route('orders.procurements.store', $order)
            : route('procurements.store') }}"
        class="oy-form"
        enctype="multipart/form-data"
    >
        @csrf

        @if($errors->any())
            <div class="oy-alert oy-alert-danger mb-6">
                Please correct the highlighted fields and try again.
            </div>
        @endif

        <div class="oy-card">

            {{-- Procurement Context --}}
            <div class="oy-card-header">
                <h2 class="oy-card-title">Procurement Context</h2>

                <p class="oy-card-description">
                    Identify whether this requirement belongs to an existing order.
                </p>
            </div>

            <div class="oy-card-body">
                @if($order)
                    <div class="oy-form-grid oy-form-grid-2">

                        <div class="oy-form-group">
                            <label class="oy-form-label">
                                Order
                            </label>

                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="font-medium text-slate-900">
                                    {{ $order->order_number }}
                                </div>

                                @if($order->organization)
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ $order->organization->name }}
                                    </div>
                                @endif
                            </div>

                            <div class="oy-form-help">
                                This procurement requirement will be attached to this order.
                            </div>
                        </div>

                        <div class="oy-form-group">
                            <label class="oy-form-label">
                                Procurement Type
                            </label>

                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="font-medium text-slate-900">
                                    Order-linked Procurement
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    The order association is fixed and cannot be changed here.
                                </div>
                            </div>
                        </div>

                    </div>
                @else
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="font-medium text-slate-900">
                            Independent Procurement
                        </div>

                        <div class="mt-1 text-sm text-slate-500">
                            This requirement is not currently associated with an order.
                        </div>
                    </div>
                @endif
            </div>

            {{-- Requirement --}}
            <div class="oy-card-header">
                <h2 class="oy-card-title">Requirement Details</h2>

                <p class="oy-card-description">
                    Describe exactly what needs to be sourced and the quantity required.
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
                            placeholder="e.g. Navy Blue Uniform Fabric"
                        >

                        @error('item_name')
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
                            maxlength="50"
                            placeholder="e.g. Yard, Piece, Pair"
                        >

                        @error('unit')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Quantity <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="number"
                            name="quantity"
                            value="{{ old('quantity') }}"
                            min="0.01"
                            step="0.01"
                            class="oy-input @error('quantity') is-invalid @enderror"
                            required
                            placeholder="0.00"
                        >

                        @error('quantity')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Maximum Unit Price <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="number"
                            name="maximum_unit_price"
                            value="{{ old('maximum_unit_price') }}"
                            min="0"
                            step="0.01"
                            class="oy-input @error('maximum_unit_price') is-invalid @enderror"
                            required
                            placeholder="0.00"
                        >

                        @error('maximum_unit_price')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror

                        <div class="oy-form-help">
                            Maximum acceptable price per unit for this procurement requirement.
                        </div>
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Commission per Unit <span class="oy-form-required">*</span>
                        </label>

                        <input
                            type="number"
                            name="commission_per_unit"
                            value="{{ old('commission_per_unit', '0') }}"
                            min="0"
                            step="0.01"
                            class="oy-input @error('commission_per_unit') is-invalid @enderror"
                            required
                            placeholder="0.00"
                        >

                        @error('commission_per_unit')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror

                        <div class="oy-form-help">
                            Internal incentive paid per unit to the staff member whose procurement offer is selected.
                        </div>
                    </div>

                    <div class="oy-form-group sm:col-span-2">
                        <label class="oy-form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="oy-textarea @error('description') is-invalid @enderror"
                            placeholder="Describe the required material, product or service..."
                        >{{ old('description') }}</textarea>

                        @error('description')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Procurement Planning --}}
            <div class="oy-card-header">
                <h2 class="oy-card-title">Procurement Planning</h2>

                <p class="oy-card-description">
                    Set the sourcing priority and important dates for the requirement.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-grid oy-form-grid-2">

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Priority <span class="oy-form-required">*</span>
                        </label>

                        <select
                            name="priority"
                            class="oy-select @error('priority') is-invalid @enderror"
                            required
                        >
                            @foreach([
                                'low' => 'Low',
                                'normal' => 'Normal',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ] as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('priority', 'normal') === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @error('priority')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Required By
                        </label>

                        <input
                            type="date"
                            name="required_by"
                            value="{{ old('required_by') }}"
                            class="oy-input @error('required_by') is-invalid @enderror"
                        >

                        @error('required_by')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror

                        <div class="oy-form-help">
                            Target date by which the sourced item should be available.
                        </div>
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Offer Deadline
                        </label>

                        <input
                            type="datetime-local"
                            name="offer_deadline"
                            value="{{ old('offer_deadline') }}"
                            class="oy-input @error('offer_deadline') is-invalid @enderror"
                        >

                        @error('offer_deadline')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror

                        <div class="oy-form-help">
                            Optional deadline for supplier or procurement offers.
                        </div>
                    </div>

                </div>
            </div>

            {{-- Notes --}}
            <div class="oy-card-header">
                <h2 class="oy-card-title">Internal Notes</h2>

                <p class="oy-card-description">
                    Add any internal information that will help the procurement process.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-group">
                    <label class="oy-form-label">
                        Notes
                    </label>

                    <textarea
                        name="notes"
                        class="oy-textarea @error('notes') is-invalid @enderror"
                        placeholder="Add internal procurement notes..."
                    >{{ old('notes') }}</textarea>

                    @error('notes')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Reference Photos --}}
            <div class="oy-card-header">
                <h2 class="oy-card-title">Reference Photos</h2>

                <p class="oy-card-description">
                    Add photos showing what needs to be procured so staff can
                    clearly understand the requirement.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="oy-form-group">
                    <label class="oy-form-label">
                        Photos
                    </label>

                    <input
                        id="procurement-photos"
                        type="file"
                        name="photos[]"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        data-maximum-photos="3"
                        class="oy-input @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror"
                    >

                    @error('photos')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror

                    @error('photos.*')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror

                    <div class="oy-form-help">
                        Upload up to 3 reference photos. JPEG, PNG or WebP only,
                        maximum 5 MB per photo.
                    </div>

                    <div
                        id="procurement-photo-preview"
                        class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3"
                    ></div>
                </div>
            </div>

            <div class="oy-card-footer flex flex-wrap justify-end gap-2">
                @if($order)
                    <a
                        href="{{ route('orders.show', $order) }}"
                        class="oy-btn oy-btn-secondary"
                    >
                        Cancel
                    </a>
                @else
                    <a
                        href="{{ route('procurements.index') }}"
                        class="oy-btn oy-btn-secondary"
                    >
                        Cancel
                    </a>
                @endif

                <button
                    type="submit"
                    class="oy-btn oy-btn-primary"
                >
                    Create Procurement
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
