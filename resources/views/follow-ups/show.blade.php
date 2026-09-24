@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-page-title-row">
                <h1 class="oy-page-title">Follow-up</h1>

                @if($followUp->status === 'completed')
                    <span class="oy-badge oy-badge-success">Completed</span>
                @elseif($followUp->status === 'cancelled')
                    <span class="oy-badge oy-badge-neutral">Cancelled</span>
                @else
                    <span class="oy-badge oy-badge-warning">Open</span>
                @endif
            </div>

            <p class="oy-page-description">
                {{ $followUp->organization->name }}
                <span class="oy-code">
                    ({{ $followUp->organization->organization_code }})
                </span>
            </p>
        </div>

        <div class="oy-page-actions">
            <a
                href="{{ route('organizations.show', $followUp->organization) }}"
                class="oy-btn oy-btn-secondary"
            >
                Organization
            </a>

            @if($followUp->contact)
                @can('view', $followUp->contact)
                    <a
                        href="{{ route('contacts.show', $followUp->contact) }}"
                        class="oy-btn oy-btn-secondary"
                    >
                        Contact
                    </a>
                @endcan
            @endif

            @can('update', $followUp)
                <a
                    href="{{ route('follow-ups.edit', $followUp) }}"
                    class="oy-btn oy-btn-primary"
                >
                    Edit Follow-up
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="oy-alert oy-alert-success mb-6">
            {{ session('success') }}
        </div>
    @endif

    <div class="oy-card">

        <div class="oy-card-header">
            <h2 class="oy-card-title">Interaction</h2>
        </div>

        <div class="oy-card-body">
            <div class="grid gap-6 sm:grid-cols-2">

                <div>
                    <div class="oy-meta">Organization</div>
                    <div class="mt-1 font-medium text-slate-900">
                        {{ $followUp->organization->name }}
                    </div>
                    <div class="oy-code mt-1">
                        {{ $followUp->organization->organization_code }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Contact</div>
                    <div class="mt-1 text-sm text-slate-900">
                        @if($followUp->contact)
                            {{ $followUp->contact->first_name }}
                            {{ $followUp->contact->middle_name }}
                            {{ $followUp->contact->last_name }}
                        @else
                            Organization-level follow-up
                        @endif
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Follow-up Date</div>
                    <div class="mt-1 text-sm text-slate-900">
                        {{ $followUp->follow_up_date->format('d M Y, h:i A') }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Type</div>
                    <div class="mt-1 text-sm capitalize text-slate-900">
                        {{ $followUp->type }}
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <div class="oy-meta">Subject</div>
                    <div class="mt-1 text-sm font-medium text-slate-900">
                        {{ $followUp->subject }}
                    </div>
                </div>

            </div>
        </div>

        <div class="oy-card-header">
            <h2 class="oy-card-title">Outcome</h2>
        </div>

        <div class="oy-card-body">
            <div class="space-y-6">

                <div>
                    <div class="oy-meta">Outcome</div>
                    <div class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                        {{ $followUp->outcome }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Next Action</div>
                    <div class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                        {{ $followUp->next_action ?: '—' }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Next Follow-up</div>
                    <div class="mt-1 text-sm text-slate-700">
                        {{ $followUp->next_follow_up_date
                            ? $followUp->next_follow_up_date->format('d M Y, h:i A')
                            : '—' }}
                    </div>
                </div>

                <div>
                    <div class="oy-meta">Internal Notes</div>
                    <div class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                        {{ $followUp->notes ?: '—' }}
                    </div>
                </div>

            </div>
        </div>

        <div class="oy-card-footer">
            <div class="text-xs text-slate-500">
                Recorded by:
                {{ $followUp->recordedBy?->name ?? 'Staff member removed' }}
            </div>
        </div>

    </div>

</div>
@endsection
