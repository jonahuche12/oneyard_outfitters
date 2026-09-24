@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">{{ $productSpecification->item_name }}</h1>

                @if($productSpecification->status === 'confirmed')
                    <span class="oy-badge oy-badge-success">Confirmed</span>
                @elseif($productSpecification->status === 'cancelled')
                    <span class="oy-badge oy-badge-danger">Cancelled</span>
                @else
                    <span class="oy-badge oy-badge-warning">Draft</span>
                @endif
            </div>

            <p class="oy-page-description">
                {{ $productSpecification->organization->name }}
                · {{ str_replace('_', ' ', ucfirst($productSpecification->product_type)) }}
                · {{ $productSpecification->specification_date->format('d M Y') }}
            </p>
        </div>

        <div class="oy-page-actions">
            <a
                href="{{ route('product-specifications.index') }}"
                class="oy-btn oy-btn-secondary"
            >
                All Specifications
            </a>

            @can('update', $productSpecification)
                <a
                    href="{{ route('product-specifications.edit', $productSpecification) }}"
                    class="oy-btn oy-btn-primary"
                >
                    Edit Specification
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="oy-alert oy-alert-success mb-6">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">

        <div class="oy-card lg:col-span-2">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Specification Details</h2>
                <p class="oy-card-description">
                    The production requirements currently recorded for this item.
                </p>
            </div>

            <div class="oy-card-body">
                <dl class="grid gap-5 sm:grid-cols-2">

                    <div>
                        <dt class="oy-meta">Organization</dt>
                        <dd class="mt-1 font-medium text-slate-900">
                            {{ $productSpecification->organization->name }}
                        </dd>
                        <dd class="oy-code mt-1">
                            {{ $productSpecification->organization->organization_code }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Contact</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            @if($productSpecification->contact)
                                {{ $productSpecification->contact->first_name }}
                                {{ $productSpecification->contact->last_name }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Product Type</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            {{ str_replace('_', ' ', ucfirst($productSpecification->product_type)) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Unit</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            {{ $productSpecification->unit }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Unit Price</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            ₦{{ number_format((float) $productSpecification->unit_price, 2) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Price Updated</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            {{ $productSpecification->price_updated_at?->format('d M Y') ?? '—' }}
                        </dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="oy-meta">Description</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                            {{ $productSpecification->description }}
                        </dd>
                    </div>

                </dl>
            </div>
        </div>

        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Record</h2>
            </div>

            <div class="oy-card-body space-y-4">

                <div>
                    <div class="oy-meta">Created By</div>
                    <div class="mt-1 text-sm text-slate-700">
                        {{ $productSpecification->createdBy?->name ?? 'Staff member removed' }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Specification Date</div>
                    <div class="mt-1 text-sm text-slate-700">
                        {{ $productSpecification->specification_date->format('d M Y') }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Artifacts</div>
                    <div class="mt-1 text-sm font-medium text-slate-900">
                        {{ $productSpecification->artifacts->count() }}
                    </div>
                </div>

            </div>
        </div>

    </div>

    <div class="oy-card oy-section">

        <div class="oy-card-header">
            <h2 class="oy-card-title">Production Requirements</h2>
            <p class="oy-card-description">
                Material, design, sizing, branding and quality instructions.
            </p>
        </div>

        <div class="oy-card-body">
            <div class="grid gap-6 md:grid-cols-2">

                @foreach([
                    'material' => 'Material',
                    'material_details' => 'Material Details',
                    'design_details' => 'Design Details',
                    'size_details' => 'Size Details',
                    'branding_details' => 'Branding Details',
                    'quality_requirements' => 'Quality Requirements',
                    'special_instructions' => 'Special Instructions',
                ] as $field => $label)

                    <div>
                        <div class="oy-meta">{{ $label }}</div>

                        <div class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                            {{ $productSpecification->{$field} ?: '—' }}
                        </div>
                    </div>

                @endforeach

            </div>
        </div>

    </div>

    @can('update', $productSpecification)

        <div class="oy-card oy-section">

            <div class="oy-card-header">
                <h2 class="oy-card-title">Production Artifacts</h2>
                <p class="oy-card-description">
                    Keep approved designs, materials, logos, samples, measurements and other references attached to this specification.
                </p>
            </div>

            <div class="oy-card-body">

                <form
                    method="POST"
                    action="{{ route('product-specification-artifacts.store', $productSpecification) }}"
                    enctype="multipart/form-data"
                    class="oy-form"
                >
                    @csrf

                    <div class="oy-form-grid oy-form-grid-3">

                        <div class="oy-form-group">
                            <label class="oy-form-label">
                                Artifact Type <span class="oy-form-required">*</span>
                            </label>

                            <select
                                name="artifact_type"
                                class="oy-select @error('artifact_type') is-invalid @enderror"
                                required
                            >
                                <option value="">Select type</option>

                                @foreach([
                                    'design' => 'Design',
                                    'material' => 'Material',
                                    'logo' => 'Logo',
                                    'branding' => 'Branding',
                                    'sample' => 'Sample',
                                    'size_chart' => 'Size Chart',
                                    'measurement' => 'Measurement',
                                    'reference' => 'Reference',
                                    'other' => 'Other',
                                ] as $value => $label)

                                    <option
                                        value="{{ $value }}"
                                        @selected(old('artifact_type') === $value)
                                    >
                                        {{ $label }}
                                    </option>

                                @endforeach
                            </select>

                            @error('artifact_type')
                                <div class="oy-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="oy-form-group sm:col-span-2">
                            <label class="oy-form-label">
                                Title <span class="oy-form-required">*</span>
                            </label>

                            <input
                                type="text"
                                name="title"
                                value="{{ old('title') }}"
                                class="oy-input @error('title') is-invalid @enderror"
                                placeholder="e.g. Approved front shirt design"
                                required
                            >

                            @error('title')
                                <div class="oy-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="oy-form-group sm:col-span-2">
                            <label class="oy-form-label">Description</label>

                            <textarea
                                name="description"
                                class="oy-textarea @error('description') is-invalid @enderror"
                                placeholder="What does this artifact represent or what should production staff know?"
                            >{{ old('description') }}</textarea>

                            @error('description')
                                <div class="oy-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="oy-form-group">
                            <label class="oy-form-label">
                                File <span class="oy-form-required">*</span>
                            </label>

                            <input
                                id="artifact-file"
                                type="file"
                                name="file"
                                accept=".jpg,.jpeg,.png,.webp,.pdf"
                                class="oy-input @error('file') is-invalid @enderror"
                                required
                            >

                            <div class="oy-form-help">
                                JPG, PNG, WEBP or PDF. Maximum 10 MB.
                                A new artifact of the same type becomes the current version.
                            </div>

                            @error('file')
                                <div class="oy-error">{{ $message }}</div>
                            @enderror

                            <div
                                id="artifact-upload-preview"
                                class="mt-4 hidden rounded-lg border border-slate-200 bg-slate-50 p-3"
                            >
                                <div class="mb-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-slate-900">
                                            Selected File
                                        </div>

                                        <div
                                            id="artifact-upload-preview-name"
                                            class="mt-1 truncate text-xs text-slate-600"
                                        ></div>

                                        <div
                                            id="artifact-upload-preview-size"
                                            class="mt-1 text-xs text-slate-500"
                                        ></div>
                                    </div>

                                    <button
                                        id="artifact-preview-remove"
                                        type="button"
                                        class="oy-btn oy-btn-ghost oy-btn-sm"
                                    >
                                        Change
                                    </button>
                                </div>

                                <div id="artifact-upload-preview-content"></div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="oy-btn oy-btn-primary">
                            Upload Artifact
                        </button>
                    </div>

                </form>

            </div>

            <div class="oy-card-footer">

                @php
                    $imageArtifacts = $productSpecification->artifacts
                        ->filter(fn ($artifact) => in_array(
                            strtolower((string) $artifact->mime_type),
                            ['image/jpeg', 'image/png', 'image/webp'],
                            true
                        ))
                        ->values();
                @endphp

                @if($productSpecification->artifacts->isEmpty())

                    <div class="oy-empty-state">
                        <div class="oy-empty-state-title">
                            No artifacts attached
                        </div>

                        <div class="oy-empty-state-description">
                            Upload the organization's design, material, logo, sample or other production reference.
                        </div>
                    </div>

                @else

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">

                        @foreach($productSpecification->artifacts as $artifact)

                            @php
                                $isImage = in_array(
                                    strtolower((string) $artifact->mime_type),
                                    ['image/jpeg', 'image/png', 'image/webp'],
                                    true
                                );

                                $isPdf = strtolower((string) $artifact->mime_type) === 'application/pdf';

                                $galleryIndex = $isImage
                                    ? $imageArtifacts->search(
                                        fn ($imageArtifact) => $imageArtifact->id === $artifact->id
                                    )
                                    : null;
                            @endphp

                            <article class="flex min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white">

                                {{-- Artifact Preview --}}
                                <div class="bg-slate-50 p-3">

                                    @if($isImage)

                                        <button
                                            type="button"
                                            class="group block w-full overflow-hidden rounded-lg border border-slate-200 bg-white"
                                            data-artifact-gallery-index="{{ $galleryIndex }}"
                                            aria-label="View {{ $artifact->title }}"
                                        >
                                            <img
                                                src="{{ route('product-specification-artifacts.preview', $artifact) }}"
                                                alt="{{ $artifact->title }}"
                                                class="h-40 w-full object-contain transition duration-200 group-hover:scale-[1.02]"
                                            >

                                            <div class="border-t border-slate-100 px-3 py-2 text-center text-xs font-medium text-slate-600">
                                                View image
                                            </div>
                                        </button>

                                    @elseif($isPdf)

                                        <div class="flex h-40 w-full flex-col items-center justify-center rounded-lg border border-slate-200 bg-white text-center">

                                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                                                <svg
                                                    class="h-6 w-6 text-slate-600"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="1.7"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M7 3.75h7.25L19 8.5v11.75A1.75 1.75 0 0117.25 22h-10.5A1.75 1.75 0 015 20.25V5.5A1.75 1.75 0 017 3.75z"
                                                    />
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M14 3.75V8.5h4.75"
                                                    />
                                                </svg>
                                            </div>

                                            <div class="mt-2 text-sm font-semibold text-slate-700">
                                                PDF Document
                                            </div>

                                            <div class="mt-1 max-w-full truncate px-4 text-xs text-slate-500">
                                                {{ $artifact->original_filename }}
                                            </div>

                                        </div>

                                    @else

                                        <div class="flex h-40 w-full flex-col items-center justify-center rounded-lg border border-slate-200 bg-white text-center">

                                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                                                <svg
                                                    class="h-6 w-6 text-slate-500"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="1.7"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M14 3.75H7A1.75 1.75 0 005.25 5.5v13A1.75 1.75 0 007 20.25h10a1.75 1.75 0 001.75-1.75V8.5L14 3.75z"
                                                    />
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M14 3.75V8.5h4.75"
                                                    />
                                                </svg>
                                            </div>

                                            <div class="mt-2 text-sm font-semibold text-slate-700">
                                                File
                                            </div>

                                            <div class="mt-1 max-w-full truncate px-4 text-xs text-slate-500">
                                                {{ $artifact->original_filename }}
                                            </div>

                                        </div>

                                    @endif

                                </div>

                                {{-- Artifact Details --}}
                                <div class="flex flex-1 flex-col p-4">

                                    <div class="flex flex-wrap items-center gap-2">

                                        <span class="oy-badge oy-badge-neutral">
                                            {{ str_replace('_', ' ', ucfirst($artifact->artifact_type)) }}
                                        </span>

                                        @if($artifact->is_current)

                                            <span class="oy-badge oy-badge-success">
                                                Current
                                            </span>

                                        @else

                                            <span class="oy-badge oy-badge-neutral">
                                                Previous Version
                                            </span>

                                        @endif

                                    </div>

                                    <h3 class="mt-3 break-words text-base font-semibold text-slate-900">
                                        {{ $artifact->title }}
                                    </h3>

                                    @if($artifact->description)

                                        <div class="mt-2 line-clamp-3 whitespace-pre-line text-sm leading-5 text-slate-600">
                                            {{ $artifact->description }}
                                        </div>

                                    @endif

                                    <dl class="mt-4 space-y-2 text-xs">

                                        <div class="flex items-start justify-between gap-3">
                                            <dt class="text-slate-500">File</dt>
                                            <dd class="min-w-0 max-w-[65%] truncate text-right font-medium text-slate-700">
                                                {{ $artifact->original_filename }}
                                            </dd>
                                        </div>

                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-slate-500">Size</dt>
                                            <dd class="font-medium text-slate-700">
                                                {{ number_format(($artifact->file_size ?? 0) / 1024, 1) }} KB
                                            </dd>
                                        </div>

                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-slate-500">Uploaded</dt>
                                            <dd class="font-medium text-slate-700">
                                                {{ $artifact->created_at?->format('d M Y') }}
                                            </dd>
                                        </div>

                                        @if($artifact->uploadedBy)

                                            <div class="flex items-start justify-between gap-3">
                                                <dt class="text-slate-500">By</dt>
                                                <dd class="min-w-0 max-w-[65%] truncate text-right font-medium text-slate-700">
                                                    {{ $artifact->uploadedBy->name }}
                                                </dd>
                                            </div>

                                        @endif

                                    </dl>

                                    <div class="mt-auto flex flex-wrap gap-2 pt-5">

                                        @can('view', $productSpecification)

                                            @if($isImage)

                                                <button
                                                    type="button"
                                                    class="oy-btn oy-btn-secondary oy-btn-sm"
                                                    data-artifact-gallery-index="{{ $galleryIndex }}"
                                                >
                                                    View
                                                </button>

                                            @endif

                                            <a
                                                href="{{ route('product-specification-artifacts.download', $artifact) }}"
                                                class="oy-btn oy-btn-secondary oy-btn-sm"
                                            >
                                                Download
                                            </a>

                                        @endcan

                                        @can('update', $productSpecification)

                                            <form
                                                method="POST"
                                                action="{{ route('product-specification-artifacts.destroy', $artifact) }}"
                                                onsubmit="return confirm('Remove this artifact? The historical record will be soft deleted.');"
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

                                        @endcan

                                    </div>

                                </div>

                            </article>

                        @endforeach

                    </div>

                @endif

            </div>

        <div
            id="artifact-gallery-modal"
            class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/90 p-4"
            aria-hidden="true"
        >
            <div class="flex min-h-full items-center justify-center">

                <div class="relative w-full max-w-5xl">

                    <button
                        type="button"
                        data-artifact-gallery-close
                        class="absolute right-2 top-2 z-10 rounded-full bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow"
                        aria-label="Close image gallery"
                    >
                        Close
                    </button>

                    @foreach($imageArtifacts as $index => $artifact)

                        <div
                            class="oy-artifact-gallery-item {{ $index === 0 ? '' : 'hidden' }}"
                            data-gallery-item="{{ $index }}"
                        >
                            <div class="rounded-xl bg-white p-3 shadow-xl">

                                <img
                                    src="{{ route('product-specification-artifacts.preview', $artifact) }}"
                                    alt="{{ $artifact->title }}"
                                    class="max-h-[75vh] w-full rounded-lg object-contain bg-slate-100"
                                >

                                <div class="mt-3">
                                    <div class="font-medium text-slate-900">
                                        {{ $artifact->title }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $artifact->original_filename }}
                                        · {{ number_format($artifact->file_size / 1024, 1) }} KB
                                    </div>
                                </div>

                            </div>
                        </div>

                    @endforeach

                </div>

            </div>
        </div>

    @endcan

    @if($productSpecification->notes)

        <div class="oy-card oy-section">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Internal Notes</h2>
            </div>

            <div class="oy-card-body whitespace-pre-line text-sm leading-6 text-slate-700">
                {{ $productSpecification->notes }}
            </div>
        </div>

    @endif

</div>
@endsection
