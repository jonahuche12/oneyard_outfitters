@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">Follow-ups</h1>
                <span class="oy-badge oy-badge-neutral">
                    {{ $followUps->total() }}
                </span>
            </div>

            <p class="oy-page-description">
                Track organization interactions, outcomes and the next operational action.
            </p>
        </div>

        @can('create', App\Models\FollowUp::class)
            <div class="oy-page-actions">
                <a href="{{ route('follow-ups.create') }}" class="oy-btn oy-btn-primary">
                    Record Follow-up
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
        <form method="GET" action="{{ route('follow-ups.index') }}" class="oy-filter">
            <div class="oy-filter-row">
                <div class="oy-filter-grow">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search organization, contact, subject, outcome..."
                        class="oy-input"
                    >
                </div>

                <button type="submit" class="oy-btn oy-btn-secondary">
                    Search
                </button>

                @if($search !== '')
                    <a href="{{ route('follow-ups.index') }}" class="oy-btn oy-btn-ghost">
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
                        <th>Date</th>
                        <th>Type</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($followUps as $followUp)
                        <tr>
                            <td>
                                <div class="oy-table-primary">
                                    {{ $followUp->organization->name }}
                                </div>

                                <div class="oy-table-secondary oy-code">
                                    {{ $followUp->organization->organization_code }}
                                </div>

                                @if($followUp->contact)
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $followUp->contact->first_name }}
                                        {{ $followUp->contact->last_name }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                {{ $followUp->follow_up_date->format('d M Y, h:i A') }}
                            </td>

                            <td>
                                <span class="capitalize">
                                    {{ $followUp->type }}
                                </span>
                            </td>

                            <td>
                                <div class="oy-table-primary">
                                    {{ $followUp->subject }}
                                </div>

                                <div class="mt-1 line-clamp-1 text-xs text-slate-500">
                                    {{ $followUp->outcome }}
                                </div>
                            </td>

                            <td>
                                @if($followUp->status === 'completed')
                                    <span class="oy-badge oy-badge-success">Completed</span>
                                @elseif($followUp->status === 'cancelled')
                                    <span class="oy-badge oy-badge-neutral">Cancelled</span>
                                @else
                                    <span class="oy-badge oy-badge-warning">Open</span>
                                @endif
                            </td>

                            <td>
                                <div class="oy-action-group">
                                    @can('view', $followUp)
                                        <a
                                            href="{{ route('follow-ups.show', $followUp) }}"
                                            class="oy-btn oy-btn-secondary oy-btn-sm"
                                        >
                                            View
                                        </a>
                                    @endcan

                                    @can('update', $followUp)
                                        <a
                                            href="{{ route('follow-ups.edit', $followUp) }}"
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
                                        No follow-ups found
                                    </div>

                                    <div class="oy-empty-state-description">
                                        @if($search !== '')
                                            No follow-up matched your search.
                                        @else
                                            No organization follow-ups have been recorded yet.
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($followUps->hasPages())
            <div class="oy-card-footer">
                {{ $followUps->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
