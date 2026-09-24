@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">Assessments</h1>
                <span class="oy-badge oy-badge-neutral">
                    {{ $assessments->total() }}
                </span>
            </div>

            <p class="oy-page-description">
                Review and manage organization assessments and commercial intelligence.
            </p>
        </div>

        @can('create', App\Models\Assessment::class)
            <div class="oy-page-actions">
                <a href="{{ route('assessments.create') }}" class="oy-btn oy-btn-primary">
                    New Assessment
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
        <form method="GET" action="{{ route('assessments.index') }}" class="oy-filter">
            <div class="oy-filter-row">
                <div class="oy-filter-grow">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search organization, code, supplier, decision maker..."
                        class="oy-input"
                    >
                </div>

                <button type="submit" class="oy-btn oy-btn-secondary">
                    Search
                </button>

                @if($search !== '')
                    <a href="{{ route('assessments.index') }}" class="oy-btn oy-btn-ghost">
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
                        <th>Assessment Date</th>
                        <th>Assessed By</th>
                        <th>Budget</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($assessments as $assessment)
                        <tr>
                            <td>
                                <div class="oy-table-primary">
                                    {{ $assessment->organization->name }}
                                </div>
                                <div class="oy-table-secondary oy-code">
                                    {{ $assessment->organization->organization_code }}
                                </div>
                            </td>

                            <td>
                                {{ $assessment->assessment_date->format('d M Y') }}
                            </td>

                            <td>
                                {{ $assessment->assessedBy?->name ?? 'Staff member removed' }}
                            </td>

                            <td>
                                {{ $assessment->estimated_budget !== null
                                    ? '₦' . number_format((float) $assessment->estimated_budget, 2)
                                    : '—' }}
                            </td>

                            <td>
                                @if($assessment->status === 'completed')
                                    <span class="oy-badge oy-badge-success">Completed</span>
                                @else
                                    <span class="oy-badge oy-badge-warning">Draft</span>
                                @endif
                            </td>

                            <td>
                                <div class="oy-action-group">
                                    <a
                                        href="{{ route('assessments.show', $assessment) }}"
                                        class="oy-btn oy-btn-secondary oy-btn-sm"
                                    >
                                        View
                                    </a>

                                    @can('update', $assessment)
                                        <a
                                            href="{{ route('assessments.edit', $assessment) }}"
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
                            <td colspan="6">
                                <div class="oy-empty-state">
                                    <div class="oy-empty-state-title">
                                        No assessments found
                                    </div>

                                    <div class="oy-empty-state-description">
                                        @if($search !== '')
                                            No assessment matched your search.
                                        @else
                                            No organization assessments have been recorded yet.
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($assessments->hasPages())
            <div class="oy-card-footer">
                {{ $assessments->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
