@extends('layouts.app')

@section('content')
    <div class="oy-page">

        <div class="oy-page-header">
            <div class="oy-page-header-content">
                <h2 class="oy-page-title">
                    Procurement
                </h2>

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
                                class="oy-btn oy-btn-secondary"
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

        {{-- Procurement List --}}
        <div class="oy-card">

            @if($procurements->count())
                <div class="overflow-x-auto">
                    <table class="oy-table">
                        <thead>
                            <tr>
                                <th>Requirement</th>
                                <th>Order</th>
                                <th>Quantity</th>
                                <th>Required By</th>
                                <th>Offers</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($procurements as $procurement)
                                <tr>
                                    <td>
                                        @php
                                            $firstReferencePhoto = $procurement->attachments
                                                ->first(function ($attachment) {
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
                                                });
                                        @endphp

                                        <div class="flex items-center gap-3">
                                            @if($firstReferencePhoto)
                                                <img
                                                    src="{{ route('procurements.attachments.show', [$procurement, $firstReferencePhoto]) }}"
                                                    alt="{{ $firstReferencePhoto->original_name ?: 'Procurement reference photo' }}"
                                                    class="h-10 w-10 shrink-0 rounded-lg border border-slate-200 object-cover"
                                                >
                                            @else
                                                <div
                                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-400"
                                                    aria-hidden="true"
                                                >
                                                    —
                                                </div>
                                            @endif

                                            <div class="min-w-0">
                                                <div class="font-medium text-slate-900">
                                                    {{ $procurement->item_name }}
                                                </div>

                                                @if($procurement->description)
                                                    <div class="mt-1 max-w-md text-xs text-slate-500">
                                                        {{ \Illuminate\Support\Str::limit($procurement->description, 100) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                    </td>

                                    <td>
                                        @if($procurement->order)
                                            <a
                                                href="{{ route('orders.show', $procurement->order) }}"
                                                class="font-medium text-slate-900 hover:underline"
                                            >
                                                {{ $procurement->order->order_number }}
                                            </a>
                                        @else
                                            <span class="text-slate-500">
                                                Independent
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $procurement->quantity }}
                                        {{ $procurement->unit }}
                                    </td>

                                    <td>
                                        @if($procurement->required_by)
                                            {{ $procurement->required_by->format('M j, Y') }}
                                        @else
                                            <span class="text-slate-500">
                                                —
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="font-medium text-slate-900">
                                            {{ $procurement->offers_count }}
                                        </span>

                                        <span class="text-xs text-slate-500">
                                            {{ \Illuminate\Support\Str::plural('offer', $procurement->offers_count) }}
                                        </span>
                                    </td>

                                    <td>
                                        @php
                                            $priorityClass = match ($procurement->priority) {
                                                'urgent' => 'oy-badge oy-badge-danger',
                                                'high' => 'oy-badge oy-badge-warning',
                                                'low' => 'oy-badge oy-badge-neutral',
                                                default => 'oy-badge oy-badge-neutral',
                                            };
                                        @endphp

                                        <span class="{{ $priorityClass }}">
                                            {{ ucfirst($procurement->priority) }}
                                        </span>
                                    </td>

                                    <td>
                                        @php
                                            $statusClass = match ($procurement->status) {
                                                'fulfilled' => 'oy-badge oy-badge-success',
                                                'cancelled' => 'oy-badge oy-badge-danger',
                                                'ready' => 'oy-badge oy-badge-warning',
                                                'in_progress' => 'oy-badge oy-badge-warning',
                                                default => 'oy-badge oy-badge-neutral',
                                            };
                                        @endphp

                                        <span class="{{ $statusClass }}">
                                            {{ str_replace('_', ' ', ucfirst($procurement->status)) }}
                                        </span>
                                    </td>

                                    <td class="text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
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
                                                        class="oy-btn oy-btn-primary"
                                                    >
                                                        Submit Offer
                                                    </a>
                                                @endcan
                                            @endif

                                            <a
                                                href="{{ route('procurements.show', $procurement) }}"
                                                class="oy-btn oy-btn-secondary"
                                            >
                                                View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="oy-card-footer">
                    {{ $procurements->links() }}
                </div>
            @else
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
            @endif

        </div>

    </div>
@endsection
