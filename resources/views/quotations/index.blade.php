@extends('layouts.app')

@section('content')
<div class="oy-page">

    {{-- Page Header --}}
    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">Quotations</h1>

                <span id="quotations-total-badge" data-list-total-badge class="oy-badge oy-badge-neutral">
                    {{ $quotations->total() }}
                </span>
            </div>

            <p class="oy-page-description">
                Manage commercial offers issued to organizations. Quotations are created from an organization's account.
            </p>
        </div>

        @can('viewAny', App\Models\Organization::class)
            <div class="oy-page-actions">
                <a href="{{ route('organizations.index') }}" class="oy-btn oy-btn-secondary">
                    Open Organizations
                </a>
            </div>
        @endcan
    </div>

    {{-- Feedback --}}
    @if (session('success'))
        <div class="oy-alert oy-alert-success mb-5">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search / Filters --}}
    <div class="oy-card mb-6">
        <div class="oy-card-body">
            <form
                id="quotations-filter-form"
                data-ajax-list-form
                method="GET"
                action="{{ route('quotations.index') }}"
                class="oy-form"
            >
                <div class="oy-filter-row">
                    <div class="oy-filter-grow">
                        <label for="quotation-search" class="oy-form-label">Search</label>

                        <input
                            id="quotation-search"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search by quotation number, organization name or code..."
                            autocomplete="off"
                            class="oy-input"
                        >
                    </div>

                    <div class="sm:w-56">
                        <label for="quotation-status" class="oy-form-label">Status</label>

                        <select id="quotation-status" name="status" class="oy-select">
                            <option value="">All statuses</option>

                            @foreach ($statuses as $quotationStatus)
                                <option
                                    value="{{ $quotationStatus }}"
                                    @selected($status === $quotationStatus)
                                >
                                    {{ ucfirst($quotationStatus) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="oy-page-actions sm:mt-7">
                        <button
                            type="button"
                            id="quotations-clear-button"
                            data-list-clear
                            data-list-clear-toggle
                            class="oy-btn oy-btn-secondary"
                            @if ($search === '' && ! $status) style="display: none;" @endif
                        >
                            Clear
                        </button>

                        <button type="submit" class="oy-btn oy-btn-primary">
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Results (refreshed in place by resources/js/quotations-index.js) --}}
    <div id="quotations-results" data-ajax-list-results>
        @include('quotations.partials.table')
    </div>

</div>
@endsection
