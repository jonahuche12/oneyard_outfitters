@extends('layouts.app')

@section('content')
<div class="oy-page">

    {{-- Page Header --}}
    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">Organizations</h1>

                <span class="oy-badge oy-badge-neutral">
                    {{ $organizations->total() }}
                </span>
            </div>

            <p class="oy-page-description">
                Manage schools, businesses and other organizations served by Oneyard Outfitters.
            </p>
        </div>

        @can('create', App\Models\Organization::class)
            <div class="oy-page-actions">
                <a href="{{ route('organizations.create') }}" class="oy-btn oy-btn-primary">
                    + Add Organization
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

    {{-- Search --}}
    <div class="oy-card mb-6">
        <div class="oy-card-body">
            <form method="GET" action="{{ route('organizations.index') }}" class="oy-form">
                <div class="oy-filter-row">
                    <div class="oy-filter-grow">
                        <label for="organization-search" class="oy-form-label">Search</label>

                        <input
                            id="organization-search"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search by code, name, type, city or state..."
                            autocomplete="off"
                            class="oy-input"
                        >
                    </div>

                    <div class="oy-page-actions sm:mt-7">
                        @if ($search !== '')
                            <a href="{{ route('organizations.index') }}" class="oy-btn oy-btn-secondary">
                                Clear
                            </a>
                        @endif

                        <button type="submit" class="oy-btn oy-btn-primary">
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Organization Table --}}
    <div class="oy-table-wrapper">

        <div class="oy-card-header">
            <h2 class="oy-card-title">Organization Records</h2>
            <p class="oy-card-description">
                View and manage organization information from the actions on each record.
            </p>
        </div>

        <div class="oy-table-scroll">
            <table class="oy-table">
                <thead>
                    <tr>
                        <th scope="col">Organization</th>
                        <th scope="col">Type</th>
                        <th scope="col">Location</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($organizations as $organization)
                        <tr>
                            {{-- Organization --}}
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="oy-avatar">
                                        {{ strtoupper(substr($organization->name, 0, 1)) }}
                                    </span>

                                    <div class="min-w-0">
                                        <div class="oy-table-primary truncate">
                                            {{ $organization->name }}
                                        </div>

                                        <div class="oy-code truncate">
                                            {{ $organization->organization_code }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Type --}}
                            <td>
                                <span class="oy-badge oy-badge-neutral capitalize">
                                    {{ str_replace('_', ' ', $organization->type) }}
                                </span>
                            </td>

                            {{-- Location --}}
                            <td>
                                <div>{{ $organization->city ?: '—' }}</div>

                                @if ($organization->state)
                                    <div class="oy-table-secondary">{{ $organization->state }}</div>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="whitespace-nowrap">
                                @if ($organization->is_active)
                                    <span class="oy-badge oy-badge-success">
                                        <span class="oy-status-dot"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="oy-badge oy-badge-danger">
                                        <span class="oy-status-dot"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td>
                                <div class="oy-action-group">

                                    @can('view', $organization)
                                        <a
                                            href="{{ route('organizations.show', $organization) }}"
                                            class="oy-btn oy-btn-secondary oy-btn-sm"
                                        >
                                            View
                                        </a>
                                    @endcan

                                    @can('update', $organization)
                                        <a
                                            href="{{ route('organizations.edit', $organization) }}"
                                            class="oy-btn oy-btn-secondary oy-btn-sm"
                                        >
                                            Edit
                                        </a>
                                    @endcan

                                    @can('activate', $organization)
                                        @if (! $organization->is_active)
                                            <form
                                                method="POST"
                                                action="{{ route('organizations.activate', $organization) }}"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit" class="oy-btn oy-btn-success oy-btn-sm">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M16.704 4.153a.75.75 0 01.143 1.052l-7.5 9.5a.75.75 0 01-1.127.075l-4-4a.75.75 0 011.06-1.06l3.403 3.402 6.97-8.828a.75.75 0 011.051-.141z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>

                                                    Activate
                                                </button>
                                            </form>
                                        @endif
                                    @endcan

                                    @can('deactivate', $organization)
                                        @if ($organization->is_active)
                                            <form
                                                method="POST"
                                                action="{{ route('organizations.deactivate', $organization) }}"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit" class="oy-btn oy-btn-danger oy-btn-sm">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M4.25 9.25a.75.75 0 01.75-.75h10a.75.75 0 010 1.5H5a.75.75 0 01-.75-.75z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>

                                                    Deactivate
                                                </button>
                                            </form>
                                        @endif
                                    @endcan

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="oy-empty-state">
                                    <div class="oy-empty-state-title">No organizations found.</div>

                                    @if ($search !== '')
                                        <p class="oy-empty-state-description">
                                            Try another search or clear the current filter.
                                        </p>

                                        <a href="{{ route('organizations.index') }}" class="oy-btn oy-btn-secondary oy-btn-sm mt-4">
                                            Clear search
                                        </a>
                                    @else
                                        <p class="oy-empty-state-description">
                                            Register the first organization to begin managing your institutional relationships.
                                        </p>

                                        @can('create', App\Models\Organization::class)
                                            <a href="{{ route('organizations.create') }}" class="oy-btn oy-btn-primary oy-btn-sm mt-4">
                                                Add Organization
                                            </a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($organizations->hasPages())
            <div class="oy-card-footer">
                {{ $organizations->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
