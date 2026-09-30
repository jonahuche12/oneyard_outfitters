@extends('layouts.app')

@section('content')
<div
    class="oy-page"
    id="quotation-show-page"
    data-organization-id="{{ $quotation->organization_id }}"
    data-options-url="{{ url('/quotations/organization-options') }}"
>
 
    <div class="oy-page-header">
        <div class="oy-page-header-content">

            <div class="oy-page-title-row">
                <h1 class="oy-page-title">{{ $quotation->quotation_number }}</h1>

                @if($quotation->status === 'accepted')
                    <span class="oy-badge oy-badge-success">Accepted</span>
                @elseif(in_array($quotation->status, ['rejected', 'cancelled'], true))
                    <span class="oy-badge oy-badge-danger">{{ ucfirst($quotation->status) }}</span>
                @elseif($quotation->status === 'sent')
                    <span class="oy-badge oy-badge-neutral">Sent</span>
                @elseif($quotation->status === 'expired')
                    <span class="oy-badge oy-badge-warning">Expired</span>
                @else
                    <span class="oy-badge oy-badge-warning">Draft</span>
                @endif
            </div>

            <p class="oy-page-description">
                {{ $quotation->organization->name }}
                · {{ $quotation->quotation_date->format('d M Y') }}
            </p>
        </div>

        <div class="oy-page-actions">
            <a href="{{ route('quotations.index') }}" class="oy-btn oy-btn-secondary">
                All Quotations
            </a>

            @can('update', $quotation)
                <a href="{{ route('quotations.edit', $quotation) }}" class="oy-btn oy-btn-primary">
                    Edit Quotation
                </a>
            @endcan
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        <div class="oy-card lg:col-span-2">

            <div class="oy-card-header">
                <h2 class="oy-card-title">Quotation Details</h2>
                <p class="oy-card-description">
                    The commercial offer captured at this point in time.
                </p>
            </div>

            <div class="oy-card-body">
                <dl class="grid gap-5 sm:grid-cols-2">

                    <div>
                        <dt class="oy-meta">Organization</dt>
                        <dd class="mt-1 font-medium text-slate-900">
                            {{ $quotation->organization->name }}
                        </dd>
                        <dd class="oy-code mt-1">
                            {{ $quotation->organization->organization_code }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Contact</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            @if($quotation->contact)
                                {{ $quotation->contact->first_name }}
                                {{ $quotation->contact->last_name }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Quotation Date</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            {{ $quotation->quotation_date->format('d M Y') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Valid Until</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            {{ $quotation->valid_until?->format('d M Y') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Expected Delivery</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            {{ $quotation->expected_delivery_days }} calendar days
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Created By</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            {{ $quotation->createdBy?->name ?? 'Staff member removed' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Items</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ $quotation->items->count() }}
                        </dd>
                    </div>

                </dl>
            </div>

        </div>

        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Commercial Summary</h2>
            </div>

            <div class="oy-card-body">
                <dl class="space-y-3 text-sm">

                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Subtotal</dt>
                        <dd class="font-medium text-slate-900">
                            ₦{{ number_format((float) $quotation->subtotal, 2) }}
                        </dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Discount</dt>
                        <dd class="font-medium text-slate-900">
                            ₦{{ number_format((float) $quotation->discount, 2) }}
                        </dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Additional Charges</dt>
                        <dd class="font-medium text-slate-900">
                            ₦{{ number_format((float) $quotation->additional_charges, 2) }}
                        </dd>
                    </div>

                    <div class="border-t border-slate-200 pt-3">
                        <div class="flex justify-between gap-4">
                            <dt class="font-semibold text-slate-900">Total</dt>
                            <dd class="text-lg font-bold text-slate-900">
                                ₦{{ number_format((float) $quotation->total, 2) }}
                            </dd>
                        </div>
                    </div>

                </dl>
            </div>
        </div>

    </div>

    <div class="oy-card oy-section">

        <div class="oy-card-header">
            @can('update', $quotation)
            <div class="oy-card mt-6">
                <div class="oy-card-header">
                    <div>
                        <h2 class="oy-card-title">Send Quotation</h2>
                        <p class="oy-card-description">
                            Send this quotation to one or more active contacts belonging to this organization.
                        </p>
                    </div>
                </div>

                <div class="oy-card-body">
                    <form
                        method="POST"
                        action="{{ route('quotations.send-to-contacts', $quotation) }}"
                        class="space-y-4"
                    >
                        @csrf

                        @php
                            $organizationContacts = $quotation->organization
                                ->contacts()
                                ->where('is_active', true)
                                ->orderByDesc('is_primary')
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get();
                        @endphp

                        @if($organizationContacts->isEmpty())
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                This organization has no active contacts available for quotation delivery.
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($organizationContacts as $contact)
                                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4">
                                        <input
                                            type="checkbox"
                                            name="contact_ids[]"
                                            value="{{ $contact->id }}"
                                            class="mt-1 rounded border-slate-300"
                                            @checked(in_array(
                                                $contact->id,
                                                old('contact_ids', []),
                                                true
                                            ))
                                        >

                                        <span>
                                            <span class="block font-medium text-slate-900">
                                                {{ $contact->first_name }}
                                                {{ $contact->middle_name }}
                                                {{ $contact->last_name }}
                                            </span>

                                            <span class="block text-sm text-slate-500">
                                                {{ $contact->email ?: 'No email address' }}
                                            </span>

                                            @if($contact->is_primary)
                                                <span class="mt-1 inline-block text-xs font-medium text-slate-600">
                                                    Primary Contact
                                                </span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            @error('contact_ids')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <button type="submit" class="oy-btn oy-btn-primary">
                                Send Quotation
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        @endcan

        <h2 class="oy-card-title">Quotation Items</h2>
            <p class="oy-card-description">
                These values are snapshots of the commercial offer and do not change when the underlying Product Specification changes.
            </p>
        </div>

        <div class="oy-card-body">

            @if($quotation->items->isEmpty())

                <div class="oy-empty-state">
                    <div class="oy-empty-state-title">No quotation items</div>
                    <div class="oy-empty-state-description">
                        Add the products or services included in this commercial offer.
                    </div>
                </div>

            @else

                <div class="overflow-x-auto">
                    <table class="oy-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Unit Price</th>
                                <th>Line Total</th>
                                @can('update', $quotation)
                                    <th class="text-right">Actions</th>
                                @endcan
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($quotation->items as $item)
                                <tr>
                                    <td>
                                        <div class="font-medium text-slate-900">
                                            {{ $item->item_name }}
                                        </div>

                                        @if($item->description)
                                            <div class="mt-1 max-w-xl whitespace-pre-line text-xs text-slate-500">
                                                {{ $item->description }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>{{ number_format((float) $item->quantity, 2) }}</td>
                                    <td>{{ $item->unit }}</td>
                                    <td>₦{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="font-medium text-slate-900">
                                        ₦{{ number_format((float) $item->line_total, 2) }}
                                    </td>

                                    @can('update', $quotation)
                                        <td>
                                            <div class="flex justify-end gap-2">
                                                <details class="relative">
                                                    <summary class="oy-btn oy-btn-secondary oy-btn-sm cursor-pointer list-none">
                                                        Edit
                                                    </summary>

                                                    <div class="absolute right-0 z-10 mt-2 w-[min(42rem,calc(100vw-3rem))] rounded-lg border border-slate-200 bg-white p-4 shadow-lg">
                                                        <form
                                                            method="POST"
                                                            action="{{ route('quotation-items.update', [$quotation, $item]) }}"
                                                            class="oy-form"
                                                        >
                                                            @csrf
                                                            @method('PATCH')

                                                            <div class="grid gap-4 sm:grid-cols-2">

                                                                <div class="oy-form-group sm:col-span-2">
                                                                    <label class="oy-form-label">
                                                                        Item Name <span class="oy-form-required">*</span>
                                                                    </label>

                                                                    <input
                                                                        type="text"
                                                                        name="item_name"
                                                                        value="{{ $item->item_name }}"
                                                                        class="oy-input"
                                                                        required
                                                                    >
                                                                </div>

                                                                <div class="oy-form-group">
                                                                    <label class="oy-form-label">
                                                                        Quantity <span class="oy-form-required">*</span>
                                                                    </label>

                                                                    <input
                                                                        type="number"
                                                                        name="quantity"
                                                                        value="{{ $item->quantity }}"
                                                                        min="0.01"
                                                                        step="0.01"
                                                                        class="oy-input"
                                                                        required
                                                                    >
                                                                </div>

                                                                <div class="oy-form-group">
                                                                    <label class="oy-form-label">
                                                                        Unit <span class="oy-form-required">*</span>
                                                                    </label>

                                                                    <input
                                                                        type="text"
                                                                        name="unit"
                                                                        value="{{ $item->unit }}"
                                                                        class="oy-input"
                                                                        required
                                                                    >
                                                                </div>

                                                                <div class="oy-form-group">
                                                                    <label class="oy-form-label">
                                                                        Agreed Unit Price <span class="oy-form-required">*</span>
                                                                    </label>

                                                                    <input
                                                                        type="number"
                                                                        name="unit_price"
                                                                        value="{{ $item->unit_price }}"
                                                                        min="0"
                                                                        step="0.01"
                                                                        class="oy-input"
                                                                        required
                                                                    >
                                                                </div>

                                                                <div class="oy-form-group sm:col-span-2">
                                                                    <label class="oy-form-label">
                                                                        Description
                                                                    </label>

                                                                    <textarea
                                                                        name="description"
                                                                        class="oy-textarea"
                                                                    >{{ $item->description }}</textarea>
                                                                </div>

                                                            </div>

                                                            <div class="mt-4 flex justify-end gap-2">
                                                                <button
                                                                    type="submit"
                                                                    class="oy-btn oy-btn-primary oy-btn-sm"
                                                                >
                                                                    Save Item
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </details>

                                                <form
                                                    method="POST"
                                                    action="{{ route('quotation-items.destroy', [$quotation, $item]) }}"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="oy-btn oy-btn-danger oy-btn-sm"
                                                    >
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @endif

        </div>

        @can('update', $quotation)
            <div class="oy-card-footer">

                <form
                    method="POST"
                    action="{{ route('quotation-items.store', $quotation) }}"
                    class="oy-form"
                >
                    @csrf

                    <div class="mb-5">
                        <h3 class="text-sm font-semibold text-slate-900">
                            Add Item
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Enter the exact commercial values being offered. The line total is calculated on the server.
                        </p>
                    </div>

                    <div class="oy-form-grid oy-form-grid-3">

                        <div class="oy-form-group">
                            <label class="oy-form-label">Product Specification</label>

                            <select
                                id="quotation-product-specification"
                                name="product_specification_id"
                                class="oy-select @error('product_specification_id') is-invalid @enderror"
                            >
                                <option value="">Custom item</option>
                            </select>

                            <div class="oy-form-help">
                                Select an organization's active Product Specification or use a custom item.
                            </div>

                            @error('product_specification_id')
                                <div class="oy-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="oy-form-group">
                            <label class="oy-form-label">
                                Item Name <span class="oy-form-required">*</span>
                            </label>

                            <input
                                id="quotation-item-name"
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
                                Unit <span class="oy-form-required">*</span>
                            </label>

                            <input
                                id="quotation-item-unit"
                                type="text"
                                name="unit"
                                value="{{ old('unit', 'piece') }}"
                                class="oy-input @error('unit') is-invalid @enderror"
                                required
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
                                value="{{ old('quantity', 1) }}"
                                min="0.01"
                                step="0.01"
                                class="oy-input @error('quantity') is-invalid @enderror"
                                required
                            >

                            @error('quantity')
                                <div class="oy-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="oy-form-group">
                            <label class="oy-form-label">
                                Agreed Unit Price <span class="oy-form-required">*</span>
                            </label>

                            <input
                                id="quotation-item-unit-price"
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

                        <div class="oy-form-group sm:col-span-2 lg:col-span-3">
                            <label class="oy-form-label">Description</label>

                            <textarea
                                id="quotation-item-description"
                                name="description"
                                class="oy-textarea @error('description') is-invalid @enderror"
                                placeholder="Commercial description or quoted specification..."
                            >{{ old('description') }}</textarea>

                            @error('description')
                                <div class="oy-error">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="oy-btn oy-btn-primary">
                            Add Quotation Item
                        </button>
                    </div>

                </form>

            </div>
        @endcan

    </div>

    @if($quotation->terms)
        <div class="oy-card oy-section">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Terms</h2>
            </div>

            <div class="oy-card-body whitespace-pre-line text-sm leading-6 text-slate-700">
                {{ $quotation->terms }}
            </div>
        </div>
    @endif

    @if($quotation->notes)
        <div class="oy-card oy-section">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Internal Notes</h2>
            </div>

            <div class="oy-card-body whitespace-pre-line text-sm leading-6 text-slate-700">
                {{ $quotation->notes }}
            </div>
        </div>
    @endif

</div>
    @include('quotations.partials.lifecycle-actions', ['quotation' => $quotation])

@endsection
