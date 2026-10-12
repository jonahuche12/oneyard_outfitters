@extends('layouts.app')

@section('title', 'Leads')

@section('content')
<div class="oy-page">

    {{-- Header --}}
    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Leads</h1>
            <p class="oy-page-description">
                Manage enquiries and move qualified prospects into organizations.
            </p>
        </div>

        @can('create', App\Models\Lead::class)
            <div class="oy-page-actions">
                <a href="{{ route('leads.create') }}" class="oy-btn oy-btn-primary">
                    Add Lead
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

    @if (session('error'))
        <div class="oy-alert oy-alert-danger mb-5">
            {{ session('error') }}
        </div>
    @endif

    {{-- Search --}}
    <div class="oy-card mb-6">
        <div class="oy-card-body">
            <form method="GET" action="{{ route('leads.index') }}" class="oy-form">
                <div class="oy-filter-row">
                    <div class="oy-filter-grow">
                        <label for="lead-search" class="oy-form-label">Search</label>

                        <input
                            id="lead-search"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Name, email, phone, organization or category..."
                            class="oy-input"
                        >
                    </div>

                    <div class="oy-page-actions sm:mt-7">
                        @if ($search !== '')
                            <a href="{{ route('leads.index') }}" class="oy-btn oy-btn-secondary">
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

    {{-- Table --}}
    <div class="oy-table-wrapper">
        <div class="oy-table-scroll">
            <table class="oy-table">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Organization</th>
                        <th>Status</th>
                        <th>Assigned</th>
                        <th>Updated</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($leads as $lead)
                        @php
                            $statusBadge = match (strtolower((string) $lead->status)) {
                                'new' => 'oy-badge-info',
                                'contacted', 'qualified', 'in_progress' => 'oy-badge-warning',
                                'converted', 'won' => 'oy-badge-success',
                                'lost', 'rejected', 'closed' => 'oy-badge-danger',
                                default => 'oy-badge-neutral',
                            };
                        @endphp

                        <tr>
                            <td>
                                <a
                                    href="{{ route('leads.show', $lead) }}"
                                    class="oy-table-primary hover:underline"
                                >
                                    {{ $lead->name }}
                                </a>

                                <div class="oy-table-secondary">
                                    {{ $lead->email ?: $lead->phone }}
                                </div>

                                @if ($lead->category)
                                    <div class="oy-table-secondary">
                                        {{ $lead->category }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                {{ $lead->organization_name ?: '—' }}
                            </td>

                            <td>
                                <span class="oy-badge {{ $statusBadge }}">
                                    <span class="oy-status-dot"></span>
                                    {{ ucfirst($lead->status) }}
                                </span>
                            </td>

                            <td>
                                @if ($lead->assignedTo)
                                    {{ $lead->assignedTo->name }}
                                @else
                                    <span class="text-slate-400">Unassigned</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap">
                                {{ $lead->updated_at?->format('d M Y, h:i A') }}
                            </td>

                            <td>
                                <div class="oy-action-group">
                                    <a
                                        href="{{ route('leads.show', $lead) }}"
                                        class="oy-btn oy-btn-secondary oy-btn-sm"
                                    >
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="oy-empty-state">
                                    <div class="oy-empty-state-title">No leads found</div>
                                    <div class="oy-empty-state-description">
                                        @if ($search !== '')
                                            Nothing matches your search. Try different words or clear the search.
                                        @else
                                            New enquiries from the website and manually added leads will appear here.
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($leads->hasPages())
            <div class="oy-card-footer">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
