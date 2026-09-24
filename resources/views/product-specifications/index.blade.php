@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">Product Specifications</h1>
                <span class="oy-badge oy-badge-neutral">
                    {{ $specifications->total() }}
                </span>
            </div>

            <p class="oy-page-description">
                Manage organization-specific production requirements, pricing, designs, materials and supporting artifacts.
            </p>
        </div>

        @can('create', App\Models\ProductSpecification::class)
            <div class="oy-page-actions">
                <a href="{{ route('product-specifications.create') }}" class="oy-btn oy-btn-primary">
                    New Specification
                </a>
            </div>
        @endcan
    </div>

    @if(session('success'))
        <div class="oy-alert oy-alert-success mb-6">
            {{ session('success') }}
        </div>
    @endif

    <div class="oy-card mb-6">
        <form method="GET" action="{{ route('product-specifications.index') }}" class="oy-filter">
            <div class="oy-filter-row">
                <div class="oy-filter-grow">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search organization, code, item, material, type..."
                        class="oy-input"
                    >
                </div>

                <button type="submit" class="oy-btn oy-btn-secondary">
                    Search
                </button>

                @if($search !== '')
                    <a href="{{ route('product-specifications.index') }}" class="oy-btn oy-btn-ghost">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="oy-table-wrapper">
        <div class="oy-table-scroll">
            <table class="oy-table">
                <thead>
                    <tr>
                        <th>Organization</th>
                        <th>Specification</th>
                        <th>Date</th>
                        <th>Unit Price</th>
                        <th>Artifacts</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($specifications as $specification)
                        <tr>
                            <td>
                                <div class="oy-table-primary">
                                    {{ $specification->organization->name }}
                                </div>

                                <div class="oy-table-secondary oy-code">
                                    {{ $specification->organization->organization_code }}
                                </div>
                            </td>

                            <td>
                                <div class="oy-table-primary">
                                    {{ $specification->item_name }}
                                </div>

                                <div class="oy-table-secondary">
                                    {{ str_replace('_', ' ', ucfirst($specification->product_type)) }}
                                    · {{ $specification->unit }}
                                </div>
                            </td>

                            <td>
                                {{ $specification->specification_date->format('d M Y') }}
                            </td>

                            <td>
                                ₦{{ number_format((float) $specification->unit_price, 2) }}
                            </td>

                            <td>
                                <span class="oy-badge oy-badge-neutral">
                                    {{ $specification->artifacts_count }}
                                </span>
                            </td>

                            <td>
                                @if($specification->status === 'confirmed')
                                    <span class="oy-badge oy-badge-success">Confirmed</span>
                                @elseif($specification->status === 'cancelled')
                                    <span class="oy-badge oy-badge-danger">Cancelled</span>
                                @else
                                    <span class="oy-badge oy-badge-warning">Draft</span>
                                @endif
                            </td>

                            <td>
                                <div class="oy-action-group">
                                    <a
                                        href="{{ route('product-specifications.show', $specification) }}"
                                        class="oy-btn oy-btn-secondary oy-btn-sm"
                                    >
                                        View
                                    </a>

                                    @can('update', $specification)
                                        <a
                                            href="{{ route('product-specifications.edit', $specification) }}"
                                            class="oy-btn oy-btn-ghost oy-btn-sm"
                                        >
                                            Edit
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="oy-empty-state">
                                    <div class="oy-empty-state-title">
                                        No product specifications found
                                    </div>

                                    <div class="oy-empty-state-description">
                                        @if($search !== '')
                                            No specification matched your search.
                                        @else
                                            No production specifications have been recorded yet.
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($specifications->hasPages())
            <div class="oy-card-footer">
                {{ $specifications->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
