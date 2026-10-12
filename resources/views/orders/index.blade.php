@extends('layouts.app')

@section('content')
<div class="oy-page">

    {{-- Page Header --}}
    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">Orders</h1>

                <span id="orders-total-badge" data-list-total-badge class="oy-badge oy-badge-neutral">
                    {{ $orders->total() }}
                </span>
            </div>

            <p class="oy-page-description">
                Manage accepted customer orders through fulfillment.
            </p>
        </div>
    </div>

    {{-- Feedback (the layout already shows "status" messages and the first error) --}}
    @if (session('success'))
        <div class="oy-alert oy-alert-success mb-5">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search / Filters --}}
    <div class="oy-card mb-6">
        <div class="oy-card-body">
            <form
                id="orders-filter-form"
                data-ajax-list-form
                method="GET"
                action="{{ route('orders.index') }}"
                class="oy-form"
            >
                <div class="oy-filter-row">
                    <div class="oy-filter-grow">
                        <label for="order-search" class="oy-form-label">Search</label>

                        <input
                            id="order-search"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search by order number, organization or quotation..."
                            autocomplete="off"
                            class="oy-input"
                        >
                    </div>

                    <div class="sm:w-64">
                        <label for="order-status" class="oy-form-label">Status</label>

                        <select id="order-status" name="status" class="oy-select">
                            <option value="">Active orders (excluding delivered)</option>

                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="oy-page-actions sm:mt-7">
                        <button
                            type="button"
                            id="orders-clear-button"
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

    {{-- Results (refreshed in place by resources/js/ajax-list.js) --}}
    <div id="orders-results" data-ajax-list-results>
        @include('orders.partials.table')
    </div>

</div>
@endsection
