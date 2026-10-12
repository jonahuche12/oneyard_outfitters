@extends('layouts.app')

@section('content')
    <div class="oy-page">

        <div class="oy-page-header">
            <div class="oy-page-header-content">
                <h1 class="oy-page-title">
                    Procurement
                </h1>

                <p class="oy-page-description">
                    Manage procurement requirements and supplier sourcing needs.
                </p>
            </div>

            @can('create', App\Models\Procurement::class)
                <div class="flex shrink-0">
                    <a
                        href="{{ route('procurements.create') }}"
                        class="oy-btn oy-btn-primary"
                    >
                        Create Procurement
                    </a>
                </div>
            @endcan
        </div>

        {{-- Feedback --}}
        @foreach (['status', 'success'] as $flashKey)
            @if (session($flashKey))
                <div class="oy-alert oy-alert-success mb-6">
                    {{ session($flashKey) }}
                </div>
            @endif
        @endforeach

        {{-- Search / Filters --}}
        <div class="oy-card mb-6">
            <div class="oy-card-body">
                <form
                    method="GET"
                    action="{{ route('procurements.index') }}"
                    class="oy-form"
                >
                    <div class="grid gap-4 md:grid-cols-3">

                        <div class="oy-form-group">
                            <label
                                for="search"
                                class="oy-form-label"
                            >
                                Search
                            </label>

                            <input
                                id="search"
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Requirement or order number"
                                class="oy-input"
                            >
                        </div>

                        <div class="oy-form-group">
                            <label
                                for="status"
                                class="oy-form-label"
                            >
                                Status
                            </label>

                            <select
                                id="status"
                                name="status"
                                class="oy-select"
                            >
                                <option value="">All statuses</option>

                                @foreach($statuses as $procurementStatus)
                                    <option
                                        value="{{ $procurementStatus }}"
                                        @selected($status === $procurementStatus)
                                    >
                                        {{ str_replace('_', ' ', ucfirst($procurementStatus)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <button
                                type="submit"
                                class="oy-btn oy-btn-primary"
                            >
                                Filter
                            </button>

                            @if($search !== '' || $status)
                                <a
                                    href="{{ route('procurements.index') }}"
                                    class="oy-btn oy-btn-secondary"
                                >
                                    Clear
                                </a>
                            @endif
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- Procurement Cards --}}
        @if($procurements->count())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                @foreach($procurements as $procurement)
                    @php
                        $referencePhotos = $procurement->attachments
                            ->filter(function ($attachment) {
                                return $attachment->product_specification_artifact_id === null
                                    && in_array(
                                        $attachment->mime_type,
                                        [
                                            'image/jpeg',
                                            'image/jpg',
                                            'image/png',
                                            'image/webp',
                                        ],
                                        true
                                    );
                            })
                            ->values();

                        $firstReferencePhoto = $referencePhotos->first();

                        $referencePhotoData = $referencePhotos->map(function ($photo) use ($procurement) {
                            return [
                                'src' => route('procurements.attachments.show', [$procurement, $photo]),
                                'alt' => $photo->original_name ?: 'Procurement reference photo',
                            ];
                        })->values();

                        $priorityClass = match ($procurement->priority) {
                            'urgent' => 'oy-badge oy-badge-danger',
                            'high' => 'oy-badge oy-badge-warning',
                            default => 'oy-badge oy-badge-neutral',
                        };

                        $statusClass = match ($procurement->status) {
                            'fulfilled' => 'oy-badge oy-badge-success',
                            'cancelled' => 'oy-badge oy-badge-danger',
                            'ready', 'in_progress' => 'oy-badge oy-badge-warning',
                            default => 'oy-badge oy-badge-neutral',
                        };
                    @endphp

                    <div
                        class="oy-card flex h-full flex-col overflow-hidden"
                        data-procurement-card="{{ $procurement->id }}"
                    >
                        {{-- Photo --}}
                        @if($firstReferencePhoto)
                            <button
                                type="button"
                                class="group relative block aspect-[4/3] w-full overflow-hidden bg-slate-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-slate-400"
                                data-procurement-photo-modal
                                data-photo-index="0"
                                data-photo-images='@json($referencePhotoData)'
                                aria-label="View reference photos for {{ $procurement->item_name }}"
                            >
                                <img
                                    src="{{ route('procurements.attachments.show', [$procurement, $firstReferencePhoto]) }}"
                                    alt="{{ $firstReferencePhoto->original_name ?: 'Procurement reference photo' }}"
                                    class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                                    loading="lazy"
                                >

                                @if($referencePhotos->count() > 1)
                                    <span class="absolute bottom-2 right-2 rounded-full bg-slate-900/70 px-2 py-0.5 text-xs font-semibold text-white">
                                        +{{ $referencePhotos->count() - 1 }}
                                    </span>
                                @endif
                            </button>
                        @else
                            <div
                                class="flex aspect-[4/3] w-full items-center justify-center bg-slate-100 text-xs text-slate-400"
                                aria-hidden="true"
                            >
                                No reference photo
                            </div>
                        @endif

                        {{-- Details --}}
                        <div class="flex flex-1 flex-col p-4">
                            <div class="flex flex-wrap gap-2">
                                <span class="{{ $statusClass }}">
                                    {{ str_replace('_', ' ', ucfirst($procurement->status)) }}
                                </span>

                                <span class="{{ $priorityClass }}">
                                    {{ ucfirst($procurement->priority) }}
                                </span>
                            </div>

                            <a
                                href="{{ route('procurements.show', $procurement) }}"
                                class="mt-3 block font-semibold text-slate-900 hover:underline"
                            >
                                {{ $procurement->item_name }}
                            </a>

                            @if($procurement->description)
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ \Illuminate\Support\Str::limit($procurement->description, 80) }}
                                </p>
                            @endif

                            <dl class="mt-4 space-y-2 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Quantity</dt>
                                    <dd class="text-right font-medium text-slate-900">
                                        {{ rtrim(rtrim(number_format((float) $procurement->quantity, 2), '0'), '.') }}
                                        {{ $procurement->unit }}
                                    </dd>
                                </div>

                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Order</dt>
                                    <dd class="text-right">
                                        @if($procurement->order)
                                            @can('view', $procurement->order)
                                                <a
                                                    href="{{ route('orders.show', $procurement->order) }}"
                                                    class="font-medium text-slate-900 hover:underline"
                                                >
                                                    {{ $procurement->order->order_number }}
                                                </a>
                                            @else
                                                <span class="font-medium text-slate-900">
                                                    {{ $procurement->order->order_number }}
                                                </span>
                                            @endcan
                                        @else
                                            <span class="text-slate-500">Independent</span>
                                        @endif
                                    </dd>
                                </div>

                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Max unit price</dt>
                                    <dd class="text-right font-medium text-slate-900">
                                        ₦{{ number_format((float) $procurement->maximum_unit_price, 2) }}
                                    </dd>
                                </div>

                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Required by</dt>
                                    <dd class="text-right font-medium text-slate-900">
                                        {{ $procurement->required_by?->format('M j, Y') ?? '—' }}
                                    </dd>
                                </div>

                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Offers</dt>
                                    <dd class="text-right">
                                        <span class="font-medium text-slate-900">
                                            {{ $procurement->offers_count }}
                                        </span>

                                        <span class="text-xs text-slate-500">
                                            {{ \Illuminate\Support\Str::plural('offer', $procurement->offers_count) }}
                                        </span>

                                        @if($procurement->accepted_offers_count > 0)
                                            <span class="oy-badge oy-badge-success ml-1">
                                                {{ $procurement->accepted_offers_count }} accepted
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>

                            {{-- Actions --}}
                            <div class="mt-auto flex flex-wrap gap-2 pt-4">
                                @if(
                                    $procurement->status === \App\Models\Procurement::STATUS_READY
                                    && (
                                        $procurement->offer_deadline === null
                                        || $procurement->offer_deadline->isFuture()
                                    )
                                )
                                    @can('submitOffer', $procurement)
                                        <a
                                            href="{{ route('procurements.offers.create', $procurement) }}"
                                            class="oy-btn oy-btn-primary oy-btn-sm"
                                        >
                                            Submit Offer
                                        </a>
                                    @endcan
                                @endif

                                <a
                                    href="{{ route('procurements.show', $procurement) }}"
                                    class="oy-btn oy-btn-secondary oy-btn-sm"
                                >
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $procurements->links() }}
            </div>
        @else
            <div class="oy-card">
                <div class="oy-empty-state">
                    <div class="oy-empty-state-title">
                        No procurement requirements found
                    </div>

                    <div class="oy-empty-state-description">
                        @if($search !== '' || $status)
                            Try adjusting your search or filter.
                        @else
                            Procurement requirements will appear here once they are created.
                        @endif
                    </div>

                    @can('create', App\Models\Procurement::class)
                        @if($search === '' && ! $status)
                            <div class="mt-4">
                                <a
                                    href="{{ route('procurements.create') }}"
                                    class="oy-btn oy-btn-primary"
                                >
                                    Create Procurement
                                </a>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>
        @endif


        {{-- Procurement Reference Photo Modal --}}
        <div
            id="procurement-photo-modal"
            class="fixed inset-0 z-50 hidden"
            aria-hidden="true"
        >
            <div
                class="absolute inset-0 bg-slate-950/80"
                data-procurement-photo-close
            ></div>

            <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                <div
                    class="relative w-full max-w-5xl rounded-2xl bg-white p-3 shadow-2xl sm:p-5"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="procurement-photo-modal-title"
                >
                    <div class="mb-3 flex items-center justify-between gap-4">
                        <div>
                            <h2
                                id="procurement-photo-modal-title"
                                class="text-sm font-semibold text-slate-900"
                            >
                                Reference Photo
                            </h2>

                            <p
                                id="procurement-photo-modal-count"
                                class="mt-1 text-xs text-slate-500"
                            ></p>
                        </div>

                        <button
                            type="button"
                            class="oy-btn oy-btn-secondary"
                            data-procurement-photo-close
                            aria-label="Close photo viewer"
                        >
                            Close
                        </button>
                    </div>

                    <div class="overflow-hidden rounded-xl bg-slate-100">
                        <img
                            id="procurement-photo-modal-image"
                            src=""
                            alt=""
                            class="mx-auto max-h-[75vh] w-auto max-w-full object-contain"
                        >
                    </div>

                    <div
                        id="procurement-photo-modal-thumbnails"
                        class="mt-3 flex gap-2 overflow-x-auto"
                    ></div>
                </div>
            </div>
        </div>

    </div>
@endsection
