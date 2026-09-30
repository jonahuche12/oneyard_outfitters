@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Edit Procurement Requirement</h1>

            <p class="oy-page-description">
                Record the material, product or service that needs to be sourced.
                Procurement requirements can be created independently or against an assigned order.
            </p>
        </div>

        <div class="oy-page-actions">
            <a
                href="{{ route('procurements.show', $procurement) }}"
                class="oy-btn oy-btn-secondary"
            >
                Back to Procurement
            </a>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('procurements.update', $procurement) }}"
        class="oy-form"
        enctype="multipart/form-data"
    >
        @csrf
        @method('PUT')

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
                @if($procurement->order)
                    <div class="oy-form-grid oy-form-grid-2">

                        <div class="oy-form-group">
                            <label class="oy-form-label">
                                Order
                            </label>

                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="font-medium text-slate-900">
                                    {{ $procurement->order->order_number }}
                                </div>

                                @if($procurement->order->organization)
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ $procurement->order->organization->name }}
                                    </div>
                                @endif
                            </div>

                            <div class="oy-form-help">
                                This procurement requirement is attached to this order.
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
                            value="{{ old('item_name', $procurement->item_name) }}"
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
                            value="{{ old('unit', $procurement->unit) }}"
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
                            value="{{ old('quantity', $procurement->quantity) }}"
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
                            value="{{ old('maximum_unit_price', $procurement->maximum_unit_price) }}"
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
                            value="{{ old('commission_per_unit', $procurement->commission_per_unit) }}"
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
                        >{{ old('description', $procurement->description) }}</textarea>

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
                            Status <span class="oy-form-required">*</span>
                        </label>

                        <select
                            name="status"
                            class="oy-select @error('status') is-invalid @enderror"
                            required
                        >
                            @foreach([
                                'draft' => 'Draft',
                                'ready' => 'Ready',
                                'in_progress' => 'In Progress',
                                'fulfilled' => 'Fulfilled',
                                'cancelled' => 'Cancelled',
                            ] as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('status', $procurement->status) === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @error('status')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror
                    </div>

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
                                    @selected(old('priority', $procurement->priority) === $value)
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
                            value="{{ old('required_by', optional($procurement->required_by)->format('Y-m-d')) }}"
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
                            value="{{ old('offer_deadline', optional($procurement->offer_deadline)->format('Y-m-d\TH:i')) }}"
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
                    >{{ old('notes', $procurement->notes) }}</textarea>

                    @error('notes')
                        <div class="oy-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Reference Photos --}}
            <div class="oy-card-header">
                <h2 class="oy-card-title">Reference Photos</h2>

                <p class="oy-card-description">
                    Existing reference photos are retained. Add more photos
                    until the maximum of 3 is reached.
                </p>
            </div>

            <div class="oy-card-body">
                @php
                    $photoMimeTypes = [
                        'image/jpeg',
                        'image/jpg',
                        'image/png',
                        'image/webp',
                    ];

                    $existingPhotos = $procurement->attachments
                        ->filter(function ($attachment) use ($photoMimeTypes) {
                            return $attachment->product_specification_artifact_id === null
                                && in_array($attachment->mime_type, $photoMimeTypes, true);
                        })
                        ->values();

                    $photoCount = $existingPhotos->count();
                    $remainingPhotoSlots = max(0, 3 - $photoCount);
                @endphp

                @if ($photoCount > 0)
                    <div class="mb-6">
                        <div class="mb-3 text-sm font-medium text-slate-700">
                            Existing Photos ({{ $photoCount }}/3)
                        </div>

                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            @foreach ($existingPhotos as $attachment)
                                <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                    <div class="aspect-square bg-slate-100">
                                        <img
                                            src="{{ route('procurements.attachments.show', [$procurement, $attachment]) }}"
                                            alt="{{ $attachment->original_name ?: 'Procurement reference photo' }}"
                                            class="h-full w-full object-cover"
                                        >
                                    </div>

                                    <div class="truncate px-3 py-2 text-xs text-slate-600">
                                        {{ $attachment->original_name ?: 'Reference photo' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($remainingPhotoSlots > 0)
                    <div class="oy-form-group">
                        <label class="oy-form-label">
                            Add Photos
                        </label>

                        <input
                            id="procurement-photos"
                            type="file"
                            name="photos[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            class="oy-input @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror"
                        >

                        @error('photos')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror

                        @error('photos.*')
                            <div class="oy-error">{{ $message }}</div>
                        @enderror

                        <div class="oy-form-help">
                            You can add up to {{ $remainingPhotoSlots }}
                            {{ $remainingPhotoSlots === 1 ? 'more photo' : 'more photos' }}.
                            JPEG, PNG or WebP only, maximum 5 MB per photo.
                        </div>

                        <div
                            id="procurement-photo-preview"
                            class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3"
                        ></div>
                    </div>
                @else
                    <div class="oy-form-help">
                        The maximum of 3 reference photos has already been reached.
                    </div>
                @endif
            </div>

            <div class="oy-card-footer flex flex-wrap justify-end gap-2">
                <a
                    href="{{ route('procurements.show', $procurement) }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="oy-btn oy-btn-primary"
                >
                    Update Procurement
                </button>
            </div>

        </div>
    </form>
</div>

<script>
$(function () {
    const input = $('#procurement-photos');
    const preview = $('#procurement-photo-preview');
    const maximumPhotos = {{ $remainingPhotoSlots }};

    if (!input.length || !preview.length) {
        return;
    }

    input.on('change', function () {
        preview.empty();

        const files = Array.from(this.files || []);

        if (files.length > maximumPhotos) {
            alert(
                'You can add a maximum of ' +
                maximumPhotos +
                (maximumPhotos === 1 ? ' photo.' : ' photos.')
            );

            this.value = '';
            return;
        }

        files.forEach(function (file) {
            if (!file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                const wrapper = $('<div>', {
                    class: 'overflow-hidden rounded-xl border border-slate-200 bg-slate-50'
                });

                const imageContainer = $('<div>', {
                    class: 'aspect-square bg-slate-100'
                });

                const image = $('<img>', {
                    src: event.target.result,
                    alt: file.name,
                    class: 'h-full w-full object-cover'
                });

                const filename = $('<div>', {
                    class: 'truncate px-3 py-2 text-xs text-slate-600',
                    text: file.name
                });

                imageContainer.append(image);
                wrapper.append(imageContainer);
                wrapper.append(filename);
                preview.append(wrapper);
            };

            reader.readAsDataURL(file);
        });
    });
});
</script>

@endsection
